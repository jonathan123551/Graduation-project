import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useLanguage } from "@/i18n/LanguageContext";
import { authService } from "@/services/authService";
import { useAuth } from "@/contexts/AuthContext";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "@/hooks/use-toast";
import {
  Sparkles,
  Loader2,
  Rocket,
  DollarSign,
  Compass,
  Phone,
} from "lucide-react";
import { cn } from "@/lib/utils";
import GoogleSignInButton from "@/components/GoogleSignInButton";

type Role = "entrepreneur" | "investor" | "explorer";

export default function Register() {
  const { t, language } = useLanguage();
  const isAr = language === "ar";
  const navigate = useNavigate();
  const { setUser } = useAuth();

  const [fullName, setFullName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [role, setRole] = useState<Role>("explorer");
  const [loading, setLoading] = useState(false);

  const roles = [
    { value: "entrepreneur", label: t.auth.entrepreneur, icon: Rocket },
    { value: "investor", label: t.auth.investor, icon: DollarSign },
    { value: "explorer", label: t.auth.explorer, icon: Compass },
  ];

  const validatePhone = (p: string) =>
    /^[\d+\s()-]{8,20}$/.test(p.trim());

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();

    if (password.length < 6) {
      toast({
        title: "Error",
        description: "Password must be at least 6 characters",
        variant: "destructive",
      });
      return;
    }

    if (!validatePhone(phone)) {
      toast({
        title: "Error",
        description: "Invalid phone number",
        variant: "destructive",
      });
      return;
    }

    setLoading(true);

    try {
      const data = await authService.register({
        full_name: fullName,
        email,
        phone,
        password,
        password_confirmation: password,
        role,
      });

      setUser(data.user);

      toast({
        title: "Success",
        description: "Account created successfully",
      });

      navigate("/dashboard");
    } catch (err: any) {
      toast({
        title: "Error",
        description:
          err?.response?.data?.message || "Registration failed",
        variant: "destructive",
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-[80vh] flex items-center justify-center px-4 py-10">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <div className="inline-flex items-center gap-2 mb-4">
            <div className="w-10 h-10 rounded-xl gradient-primary flex items-center justify-center">
              <Sparkles className="w-5 h-5 text-primary-foreground" />
            </div>
          </div>

          <h1 className="text-2xl font-bold">
            {t.auth.register}
          </h1>
        </div>

        <div className="glass rounded-2xl p-8 shadow-glass">
          <GoogleSignInButton label="Google Soon" />

          <form onSubmit={handleRegister} className="space-y-4 mt-6">
            <div>
              <Label>Full Name</Label>
              <Input
                value={fullName}
                onChange={(e) => setFullName(e.target.value)}
              />
            </div>

            <div>
              <Label>Email</Label>
              <Input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
              />
            </div>

            <div>
              <Label className="flex gap-1 items-center">
                <Phone className="w-4 h-4" />
                Phone
              </Label>
              <Input
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
              />
            </div>

            <div>
              <Label>Password</Label>
              <Input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>

            <div className="grid gap-2">
              {roles.map((r: any) => (
                <button
                  key={r.value}
                  type="button"
                  onClick={() => setRole(r.value)}
                  className={cn(
                    "border rounded-xl p-3 text-left",
                    role === r.value && "border-primary"
                  )}
                >
                  {r.label}
                </button>
              ))}
            </div>

            <Button
              type="submit"
              className="w-full"
              disabled={loading}
            >
              {loading ? (
                <Loader2 className="animate-spin w-4 h-4" />
              ) : (
                "Create Account"
              )}
            </Button>
          </form>

          <p className="text-center mt-6 text-sm">
            Have account?{" "}
            <Link to="/login" className="text-primary">
              Login
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
}