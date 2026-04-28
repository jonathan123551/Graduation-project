import { ReactNode, useEffect, useState } from "react";
import api from "@/lib/api";
import { useAuth } from "@/contexts/AuthContext";
import { useLanguage } from "@/i18n/LanguageContext";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Loader2, ScrollText, ShieldCheck } from "lucide-react";
import { toast } from "@/hooks/use-toast";

interface NdaGateProps {
  ideaId?: string;
  otherUserId: string;
  children: ReactNode;
}

export default function NdaGate({
  ideaId,
  otherUserId,
  children,
}: NdaGateProps) {
  const { user } = useAuth();
  const { language } = useLanguage();

  const isAr = language === "ar";

  const [loading, setLoading] = useState(true);
  const [signed, setSigned] = useState(false);
  const [accepting, setAccepting] = useState(false);
  const [agree, setAgree] = useState(false);

  useEffect(() => {
    if (!user || !ideaId) {
      setSigned(true);
      setLoading(false);
      return;
    }

    const loadData = async () => {
      try {
        const { data } = await api.get(`/nda/check/${ideaId}`);

        setSigned(!!data?.signed);
      } catch {
        setSigned(false);
      } finally {
        setLoading(false);
      }
    };

    loadData();
  }, [user, ideaId, otherUserId]);

  const handleAccept = async () => {
    if (!agree || !ideaId) return;

    setAccepting(true);

    try {
      await api.post("/nda/sign", {
        idea_id: ideaId,
        other_user_id: otherUserId,
      });

      setSigned(true);

      toast({
        title: isAr ? "تم التوقيع" : "NDA Signed",
        description: isAr
          ? "يمكنك الآن بدء الدردشة"
          : "You can now start chatting.",
      });
    } catch (error: any) {
      toast({
        title: isAr ? "خطأ" : "Error",
        description:
          error?.response?.data?.message ||
          (isAr ? "فشل التوقيع" : "Failed to sign NDA"),
        variant: "destructive",
      });
    } finally {
      setAccepting(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center p-10">
        <Loader2 className="h-5 w-5 animate-spin text-primary" />
      </div>
    );
  }

  if (signed) return <>{children}</>;

  return (
    <div className="p-6 h-full flex items-center justify-center">
      <div className="max-w-md w-full">
        <div className="text-center mb-4">
          <div className="w-12 h-12 rounded-2xl gradient-primary mx-auto flex items-center justify-center mb-3">
            <ScrollText className="w-6 h-6 text-primary-foreground" />
          </div>

          <h3 className="text-lg font-bold text-foreground">
            {isAr
              ? "اتفاقية عدم الإفصاح (NDA)"
              : "Non-Disclosure Agreement"}
          </h3>

          <p className="text-xs text-muted-foreground mt-1">
            {isAr
              ? "مطلوبة قبل بدء أي مناقشة بشأن الفكرة"
              : "Required before discussing any idea details"}
          </p>
        </div>

        <div className="rounded-xl border border-border/60 bg-muted/20 p-4 max-h-48 overflow-y-auto text-xs text-muted-foreground leading-relaxed mb-4">
          {isAr
            ? "أتعهد بالحفاظ على سرية كافة المعلومات المتعلقة بالفكرة وعدم استخدامها خارج المنصة."
            : "I agree to keep all information related to the idea confidential and not use it outside the platform."}
        </div>

        <label className="flex items-center gap-2 mb-4 text-sm text-foreground cursor-pointer">
          <Checkbox
            checked={agree}
            onCheckedChange={(v) => setAgree(!!v)}
          />

          {isAr
            ? "أقر وأوافق على الشروط أعلاه"
            : "I have read and agree"}
        </label>

        <Button
          className="w-full gradient-primary border-0 text-primary-foreground"
          disabled={!agree || accepting}
          onClick={handleAccept}
        >
          {accepting ? (
            <Loader2 className="h-4 w-4 animate-spin me-2" />
          ) : (
            <ShieldCheck className="h-4 w-4 me-2" />
          )}

          {isAr
            ? "توقيع الاتفاقية والمتابعة"
            : "Sign NDA and Continue"}
        </Button>
      </div>
    </div>
  );
}