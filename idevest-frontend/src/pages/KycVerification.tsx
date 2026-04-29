import { useState, useEffect } from "react";
import { Navigate, useNavigate } from "react-router-dom";
import { useAuth } from "@/contexts/AuthContext";
import { useLanguage } from "@/i18n/LanguageContext";
import api from "@/lib/api";
import { getApiErrorMessage } from "@/lib/errors";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { toast } from "@/hooks/use-toast";

import {
  Loader2,
  ShieldCheck,
  ShieldAlert,
  ShieldQuestion,
  ArrowLeft,
  CheckCircle2,
  Clock,
  XCircle,
  Upload,
  ImageIcon,
  Sparkles,
} from "lucide-react";

type KycStatus = "not_started" | "pending" | "approved" | "rejected";

interface KycRow {
  id: string;
  status: KycStatus;
  full_legal_name: string | null;
  national_id: string | null;
  date_of_birth: string | null;
  nationality: string | null;
  address: string | null;
  phone_number: string | null;
  rejection_reason: string | null;
  id_card_front_url: string | null;
  id_card_back_url: string | null;
}

export default function KycVerification() {
  const { user, loading: authLoading } = useAuth();
  const { language } = useLanguage();
  const isAr = language === "ar";
  const navigate = useNavigate();

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [aiChecking, setAiChecking] = useState(false);

  const [kyc, setKyc] = useState<KycRow | null>(null);

  const [form, setForm] = useState({
    full_legal_name: "",
    national_id: "",
    date_of_birth: "",
    nationality: "",
    address: "",
    phone_number: "",
  });

  const [frontFile, setFrontFile] = useState<File | null>(null);
  const [backFile, setBackFile] = useState<File | null>(null);

  const [frontPreview, setFrontPreview] = useState<string | null>(null);
  const [backPreview, setBackPreview] = useState<string | null>(null);

  useEffect(() => {
    if (!user) return;

    (async () => {
      try {
        const { data } = await api.get("/kyc");
        const row = data.data ?? data;

        if (row) {
          setKyc(row);

          setForm({
            full_legal_name: row.full_legal_name || "",
            national_id: row.national_id || "",
            date_of_birth: row.date_of_birth || "",
            nationality: row.nationality || "",
            address: row.address || "",
            phone_number: row.phone_number || "",
          });
        }
      } catch (error) {
        console.log(error);
      } finally {
        setLoading(false);
      }
    })();
  }, [user]);

  if (authLoading)
    return (
      <div className="min-h-[60vh] flex items-center justify-center">
        <Loader2 className="h-6 w-6 animate-spin text-primary" />
      </div>
    );

  if (!user) return <Navigate to="/login" replace />;

  const handleFileChange = (
    side: "front" | "back",
    file: File | null
  ) => {
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
      toast({
        title: "Error",
        description: "Max file size is 5MB",
        variant: "destructive",
      });
      return;
    }

    const reader = new FileReader();

    reader.onloadend = () => {
      if (side === "front") {
        setFrontFile(file);
        setFrontPreview(reader.result as string);
      } else {
        setBackFile(file);
        setBackPreview(reader.result as string);
      }
    };

    reader.readAsDataURL(file);
  };

  const uploadImage = async (
    file: File,
    side: "front" | "back"
  ): Promise<string | null> => {
    try {
      const formData = new FormData();
      formData.append("image", file);
      formData.append("side", side);

      const { data } = await api.post("/kyc/upload", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });

      return data.path;
    } catch (error) {
      toast({
        title: "Error",
        description: getApiErrorMessage(error, "Upload failed"),
        variant: "destructive",
      });

      return null;
    }
  };

  const handleSubmit = async () => {
    if (
      !form.full_legal_name ||
      !form.national_id ||
      !form.date_of_birth ||
      !form.phone_number
    ) {
      toast({
        title: "Error",
        description: "Please fill all required fields",
        variant: "destructive",
      });
      return;
    }

    setSaving(true);

    let frontPath = kyc?.id_card_front_url || null;
    let backPath = kyc?.id_card_back_url || null;

    if (frontFile) {
      frontPath = await uploadImage(frontFile, "front");
    }

    if (backFile) {
      backPath = await uploadImage(backFile, "back");
    }

    if (!frontPath || !backPath) {
      setSaving(false);
      return;
    }

    const payload = {
      ...form,
      id_card_front_url: frontPath,
      id_card_back_url: backPath,
    };

    try {
      await api.post("/kyc", payload);
    } catch (error) {
      toast({
        title: "Error",
        description: getApiErrorMessage(error, "Failed"),
        variant: "destructive",
      });

      setSaving(false);
      return;
    }

    setSaving(false);
    setAiChecking(true);

    try {
      await api.post("/kyc/verify-ai", {
        frontPath,
        backPath,
        userEnteredNationalId: form.national_id,
      });
    } catch (error) {
      console.log(error);
    }

    try {
      const { data } = await api.get("/kyc");
      setKyc(data.data ?? data);
    } catch (error) {
      console.log(error);
    }

    setAiChecking(false);

    toast({
      title: "Success",
      description: "KYC submitted successfully",
    });
  };

  const status: KycStatus =
    kyc?.status || "not_started";

  const isLocked =
    status === "approved" || status === "pending";

  const StatusBadge = () => {
    if (status === "approved") {
      return (
        <Badge className="bg-primary/10 text-primary">
          <CheckCircle2 className="h-3 w-3 me-1" />
          Approved
        </Badge>
      );
    }

    if (status === "pending") {
      return (
        <Badge variant="outline">
          <Clock className="h-3 w-3 me-1" />
          Pending
        </Badge>
      );
    }

    if (status === "rejected") {
      return (
        <Badge variant="destructive">
          <XCircle className="h-3 w-3 me-1" />
          Rejected
        </Badge>
      );
    }

    return (
      <Badge variant="outline">
        <ShieldQuestion className="h-3 w-3 me-1" />
        Not Started
      </Badge>
    );
  };

  const FileSlot = ({
    side,
    preview,
  }: {
    side: "front" | "back";
    preview: string | null;
  }) => (
    <label className="block cursor-pointer">
      <input
        type="file"
        accept="image/*"
        className="hidden"
        disabled={isLocked}
        onChange={(e) =>
          handleFileChange(
            side,
            e.target.files?.[0] || null
          )
        }
      />

      <div className="rounded-xl border-2 border-dashed border-border/60 p-4 text-center min-h-[140px] flex flex-col items-center justify-center gap-2">
        {preview ? (
          <img
            src={preview}
            alt={side}
            className="max-h-32 rounded-lg"
          />
        ) : (
          <>
            <Upload className="h-8 w-8 text-muted-foreground" />
            <p className="text-xs text-muted-foreground">
              Upload {side} image
            </p>
          </>
        )}
      </div>
    </label>
  );

  if (loading)
    return (
      <div className="min-h-[60vh] flex items-center justify-center">
        <Loader2 className="h-6 w-6 animate-spin text-primary" />
      </div>
    );

  return (
    <div className="container mx-auto px-4 py-10 max-w-3xl">
      <button
        onClick={() => navigate(-1)}
        className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-6"
      >
        <ArrowLeft className="h-4 w-4 me-1" />
        Back
      </button>

      <div className="glass rounded-2xl p-6 shadow-glass">
        <div className="flex items-center justify-between mb-6">
          <h1 className="text-2xl font-bold">
            KYC Verification
          </h1>
          <StatusBadge />
        </div>

        {status === "rejected" &&
          kyc?.rejection_reason && (
            <div className="mb-6 p-4 rounded-xl bg-destructive/10 border border-destructive/20 flex gap-3">
              <ShieldAlert className="h-5 w-5 text-destructive" />
              <div>
                <p className="font-semibold">
                  Rejection Reason
                </p>
                <p>{kyc.rejection_reason}</p>
              </div>
            </div>
          )}

        <div className="grid md:grid-cols-2 gap-3 mb-6">
          <FileSlot side="front" preview={frontPreview} />
          <FileSlot side="back" preview={backPreview} />
        </div>

        <div className="space-y-4">
          <div>
            <Label>Full Legal Name</Label>
            <Input
              value={form.full_legal_name}
              disabled={isLocked}
              onChange={(e) =>
                setForm({
                  ...form,
                  full_legal_name: e.target.value,
                })
              }
            />
          </div>

          <div>
            <Label>National ID</Label>
            <Input
              value={form.national_id}
              disabled={isLocked}
              onChange={(e) =>
                setForm({
                  ...form,
                  national_id: e.target.value,
                })
              }
            />
          </div>

          <div>
            <Label>Date of Birth</Label>
            <Input
              type="date"
              value={form.date_of_birth}
              disabled={isLocked}
              onChange={(e) =>
                setForm({
                  ...form,
                  date_of_birth: e.target.value,
                })
              }
            />
          </div>

          <div>
            <Label>Phone</Label>
            <Input
              value={form.phone_number}
              disabled={isLocked}
              onChange={(e) =>
                setForm({
                  ...form,
                  phone_number: e.target.value,
                })
              }
            />
          </div>

          <div>
            <Label>Nationality</Label>
            <Input
              value={form.nationality}
              disabled={isLocked}
              onChange={(e) =>
                setForm({
                  ...form,
                  nationality: e.target.value,
                })
              }
            />
          </div>

          <div>
            <Label>Address</Label>
            <Textarea
              value={form.address}
              disabled={isLocked}
              onChange={(e) =>
                setForm({
                  ...form,
                  address: e.target.value,
                })
              }
            />
          </div>
        </div>

        {!isLocked && (
          <Button
            onClick={handleSubmit}
            className="w-full mt-6"
            disabled={saving || aiChecking}
          >
            {saving || aiChecking ? (
              <Loader2 className="h-4 w-4 animate-spin me-2" />
            ) : (
              <ShieldCheck className="h-4 w-4 me-2" />
            )}

            {saving
              ? "Saving..."
              : aiChecking
              ? "AI Checking..."
              : "Submit"}
          </Button>
        )}

        <div className="mt-6 p-4 rounded-xl bg-muted/30 text-xs text-muted-foreground flex gap-2">
          <Sparkles className="h-4 w-4 text-primary" />
          Your data is protected and encrypted.
        </div>
      </div>
    </div>
  );
}