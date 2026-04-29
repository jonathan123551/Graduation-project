import { useEffect, useRef, useState } from "react";
import {
  RecaptchaVerifier,
  signInWithPhoneNumber,
  type ConfirmationResult,
} from "firebase/auth";
import api from "@/lib/api";
import { getApiErrorMessage } from "@/lib/errors";
import { getFirebaseAuth } from "@/lib/firebase";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Loader2, Phone, ShieldCheck } from "lucide-react";
import { toast } from "@/hooks/use-toast";

interface Props {
  initialPhone?: string;
  onVerified: (phone: string) => void;
  isAr?: boolean;
}

declare global {
  interface Window {
    __recaptchaVerifier?: RecaptchaVerifier;
  }
}

/**
 * Phone OTP verification using Firebase Phone Auth.
 *
 * Flow:
 *   1. User enters phone number (international format).
 *   2. reCAPTCHA challenge (invisible) runs, Firebase sends real SMS.
 *   3. User types the 6-digit code → we confirm with Firebase → get ID token.
 *   4. ID token is POSTed to Laravel /api/phone/verify-firebase-token,
 *      which verifies the token, extracts the phone, and marks the profile
 *      as phone_verified.
 */
export default function PhoneOtpVerify({
  initialPhone = "+20",
  onVerified,
  isAr = true,
}: Props) {
  const [phone, setPhone] = useState(initialPhone);
  const [step, setStep] = useState<"enter" | "code">("enter");
  const [code, setCode] = useState("");
  const [busy, setBusy] = useState(false);
  const confirmationRef = useRef<ConfirmationResult | null>(null);

  // Clean up reCAPTCHA verifier on unmount so re-mounting the component works.
  useEffect(() => {
    return () => {
      try {
        window.__recaptchaVerifier?.clear();
      } catch {
        /* noop */
      }
      window.__recaptchaVerifier = undefined;
    };
  }, []);

  const ensureRecaptcha = (): RecaptchaVerifier => {
    if (window.__recaptchaVerifier) return window.__recaptchaVerifier;
    const auth = getFirebaseAuth();
    const verifier = new RecaptchaVerifier(auth, "recaptcha-container", {
      size: "invisible",
    });
    window.__recaptchaVerifier = verifier;
    return verifier;
  };

  const send = async () => {
    if (!/^\+\d{8,15}$/.test(phone.trim())) {
      toast({
        title: isAr ? "خطأ" : "Error",
        description: isAr
          ? "أدخل رقماً بصيغة دولية مثل +201234567890"
          : "Use international format e.g. +201234567890",
        variant: "destructive",
      });
      return;
    }

    setBusy(true);
    try {
      const auth = getFirebaseAuth();
      const verifier = ensureRecaptcha();
      const confirmation = await signInWithPhoneNumber(
        auth,
        phone.trim(),
        verifier,
      );
      confirmationRef.current = confirmation;
      setStep("code");
      toast({
        title: isAr ? "تم الإرسال" : "Sent",
        description: isAr
          ? "تم إرسال الكود إلى هاتفك"
          : "Code sent to your phone",
      });
    } catch (error) {
      toast({
        title: isAr ? "خطأ" : "Error",
        description: getApiErrorMessage(error, "Failed to send code"),
        variant: "destructive",
      });
      try {
        window.__recaptchaVerifier?.clear();
      } catch {
        /* noop */
      }
      window.__recaptchaVerifier = undefined;
    } finally {
      setBusy(false);
    }
  };

  const verify = async () => {
    if (!/^\d{4,6}$/.test(code)) {
      toast({
        title: isAr ? "كود غير صحيح" : "Invalid code",
        variant: "destructive",
      });
      return;
    }
    if (!confirmationRef.current) {
      toast({
        title: isAr ? "خطأ" : "Error",
        description: isAr
          ? "أعد إرسال الكود"
          : "Please request a new code",
        variant: "destructive",
      });
      setStep("enter");
      return;
    }

    setBusy(true);
    try {
      const result = await confirmationRef.current.confirm(code);
      const idToken = await result.user.getIdToken();
      await api.post("/phone/verify-firebase-token", {
        firebase_id_token: idToken,
        phone: phone.trim(),
      });
      toast({
        title: isAr ? "تم التحقق" : "Verified",
        description: isAr ? "تم تأكيد رقم الهاتف" : "Phone verified",
      });
      onVerified(phone.trim());
    } catch (error) {
      toast({
        title: isAr ? "فشل التحقق" : "Verification failed",
        description: getApiErrorMessage(error, "Invalid code"),
        variant: "destructive",
      });
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="space-y-3">
      <div id="recaptcha-container" />

      {step === "enter" ? (
        <>
          <Label className="flex items-center gap-1.5">
            <Phone className="h-3.5 w-3.5" />
            {isAr ? "رقم الهاتف *" : "Phone number *"}
          </Label>
          <Input
            value={phone}
            onChange={(e) => setPhone(e.target.value)}
            placeholder="+201234567890"
          />
          <Button
            type="button"
            onClick={send}
            disabled={busy}
            className="w-full"
          >
            {busy ? (
              <Loader2 className="h-4 w-4 animate-spin" />
            ) : isAr ? (
              "إرسال كود التحقق"
            ) : (
              "Send verification code"
            )}
          </Button>
        </>
      ) : (
        <>
          <Label>
            {isAr
              ? `أدخل الكود المرسل إلى ${phone}`
              : `Enter the code sent to ${phone}`}
          </Label>
          <Input
            value={code}
            onChange={(e) =>
              setCode(e.target.value.replace(/\D/g, "").slice(0, 6))
            }
            placeholder="123456"
            inputMode="numeric"
          />
          <div className="flex gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                setStep("enter");
                confirmationRef.current = null;
              }}
            >
              {isAr ? "تغيير الرقم" : "Change"}
            </Button>
            <Button
              type="button"
              onClick={verify}
              disabled={busy}
              className="flex-1 gradient-primary border-0 text-primary-foreground"
            >
              {busy ? (
                <Loader2 className="h-4 w-4 animate-spin" />
              ) : (
                <>
                  <ShieldCheck className="h-4 w-4 me-2" />
                  {isAr ? "تأكيد" : "Verify"}
                </>
              )}
            </Button>
          </div>
        </>
      )}
    </div>
  );
}
