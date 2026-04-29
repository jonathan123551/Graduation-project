import { useState } from "react";
import api from "@/lib/api";
import { getApiErrorMessage } from "@/lib/errors";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Loader2,
  Phone,
  ShieldCheck,
} from "lucide-react";
import { toast } from "@/hooks/use-toast";

interface Props {
  initialPhone?: string;
  onVerified: (phone: string) => void;
  isAr?: boolean;
}

export default function PhoneOtpVerify({
  initialPhone = "+20",
  onVerified,
  isAr = true,
}: Props) {
  const [phone, setPhone] = useState(initialPhone);
  const [step, setStep] =
    useState<"enter" | "code">("enter");

  const [code, setCode] = useState("");
  const [busy, setBusy] = useState(false);

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
      await api.post("/phone/send-otp", {
        phone: phone.trim(),
      });

      setStep("code");

      toast({
        title: "✅",
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
    } finally {
      setBusy(false);
    }
  };

  const verify = async () => {
    if (!/^\d{4,6}$/.test(code)) {
      toast({
        title: isAr
          ? "كود غير صحيح"
          : "Invalid code",
        variant: "destructive",
      });
      return;
    }

    setBusy(true);

    try {
      await api.post("/phone/verify-otp", {
        phone: phone.trim(),
        code,
      });

      toast({
        title: "✅",
        description: isAr
          ? "تم تأكيد رقم الهاتف"
          : "Phone verified",
      });

      onVerified(phone.trim());
    } catch (error) {
      toast({
        title: isAr
          ? "فشل التحقق"
          : "Verification failed",
        description: getApiErrorMessage(error, "Invalid code"),
        variant: "destructive",
      });
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="space-y-3">
      {step === "enter" ? (
        <>
          <Label className="flex items-center gap-1.5">
            <Phone className="h-3.5 w-3.5" />
            {isAr
              ? "رقم الهاتف *"
              : "Phone number *"}
          </Label>

          <Input
            value={phone}
            onChange={(e) =>
              setPhone(e.target.value)
            }
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
              setCode(
                e.target.value
                  .replace(/\D/g, "")
                  .slice(0, 6)
              )
            }
            placeholder="123456"
            inputMode="numeric"
          />

          <div className="flex gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => setStep("enter")}
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