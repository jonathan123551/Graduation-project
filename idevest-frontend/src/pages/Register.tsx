import { useState } from "react";
import {
  Link,
  useNavigate,
  Navigate,
} from "react-router-dom";

import { useLanguage } from "@/i18n/LanguageContext";
import { authService } from "@/services/authService";
import { getApiErrorMessage } from "@/lib/errors";
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

type Role =
  | "entrepreneur"
  | "investor"
  | "explorer";

export default function Register() {
  const { t, language } = useLanguage();
  const isAr = language === "ar";

  const navigate = useNavigate();

  const {
    user,
    loading: authLoading,
    setUser,
  } = useAuth();

  const [fullName, setFullName] =
    useState("");

  const [email, setEmail] =
    useState("");

  const [phone, setPhone] =
    useState("");

  const [password, setPassword] =
    useState("");

  const [role, setRole] =
    useState<Role>("explorer");

  const [loading, setLoading] =
    useState(false);

  if (!authLoading && user) {
    if (user.role === "admin") {
      return (
        <Navigate
          to="/admin"
          replace
        />
      );
    }

    if (
      user.role ===
      "entrepreneur"
    ) {
      return (
        <Navigate
          to="/dashboard"
          replace
        />
      );
    }

    return (
      <Navigate
        to="/marketplace"
        replace
      />
    );
  }

  const roles = [
    {
      value:
        "entrepreneur",
      label:
        t.auth
          .entrepreneur ||
        "Entrepreneur",
      icon: Rocket,
    },
    {
      value:
        "investor",
      label:
        t.auth
          .investor ||
        "Investor",
      icon: DollarSign,
    },
    {
      value:
        "explorer",
      label:
        t.auth
          .explorer ||
        "Explorer",
      icon: Compass,
    },
  ];

  const validatePhone = (
    p: string
  ) =>
    /^[\d+\s()-]{8,20}$/.test(
      p.trim()
    );

  const handleRegister =
    async (
      e: React.FormEvent
    ) => {
      e.preventDefault();

      if (
        password.length < 6
      ) {
        toast({
          title: "Error",
          description:
            "Password must be at least 6 characters",
          variant:
            "destructive",
        });
        return;
      }

      if (
        !validatePhone(
          phone
        )
      ) {
        toast({
          title: "Error",
          description:
            "Invalid phone number",
          variant:
            "destructive",
        });
        return;
      }

      setLoading(true);

      try {
        const data =
          await authService.register(
            {
              full_name:
                fullName,
              email,
              phone,
              password,
              password_confirmation:
                password,
              role,
            }
          );

        if (
          data?.user
        ) {
          setUser(
            data.user
          );
        }

        const roleName =
          data.user?.role;

        toast({
          title:
            "Success",
          description:
            "Account created successfully",
        });

        if (
          roleName ===
          "admin"
        ) {
          navigate(
            "/admin"
          );
        } else if (
          roleName ===
          "entrepreneur"
        ) {
          navigate(
            "/dashboard"
          );
        } else {
          navigate(
            "/marketplace"
          );
        }
      } catch (err) {
        toast({
          title: "Error",
          description: getApiErrorMessage(err, "Registration failed"),
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
            {
              t.auth
                .register
            }
          </h1>
        </div>

        <div className="glass rounded-2xl p-8 shadow-glass">
          <GoogleSignInButton
            label={
              isAr
                ? "التسجيل عبر Google قريباً"
                : "Google Soon"
            }
          />

          <form
            onSubmit={
              handleRegister
            }
            className="space-y-4 mt-6"
          >
            <div>
              <Label>
                {isAr
                  ? "الاسم الكامل"
                  : "Full Name"}
              </Label>

              <Input
                value={
                  fullName
                }
                onChange={(
                  e
                ) =>
                  setFullName(
                    e
                      .target
                      .value
                  )
                }
                required
              />
            </div>

            <div>
              <Label>
                {
                  t.auth
                    .email
                }
              </Label>

              <Input
                type="email"
                value={
                  email
                }
                onChange={(
                  e
                ) =>
                  setEmail(
                    e
                      .target
                      .value
                  )
                }
                required
              />
            </div>

            <div>
              <Label className="flex gap-1 items-center">
                <Phone className="w-4 h-4" />
                {isAr
                  ? "رقم الهاتف"
                  : "Phone"}
              </Label>

              <Input
                value={
                  phone
                }
                onChange={(
                  e
                ) =>
                  setPhone(
                    e
                      .target
                      .value
                  )
                }
                required
              />
            </div>

            <div>
              <Label>
                {
                  t.auth
                    .password
                }
              </Label>

              <Input
                type="password"
                value={
                  password
                }
                onChange={(
                  e
                ) =>
                  setPassword(
                    e
                      .target
                      .value
                  )
                }
                required
              />
            </div>

            <div className="grid gap-2">
              {roles.map(
                (r) => (
                  <button
                    key={
                      r.value
                    }
                    type="button"
                    onClick={() =>
                      setRole(
                        r.value as Role
                      )
                    }
                    className={cn(
                      "border rounded-xl p-3 text-left flex items-center gap-2 transition-colors",
                      role ===
                        r.value &&
                        "border-primary bg-primary/5"
                    )}
                  >
                    <r.icon className="w-4 h-4" />
                    {
                      r.label
                    }
                  </button>
                )
              )}
            </div>

            <Button
              type="submit"
              disabled={
                loading
              }
              className="w-full gradient-primary border-0 text-primary-foreground"
            >
              {loading ? (
                <Loader2 className="animate-spin w-4 h-4" />
              ) : isAr ? (
                "إنشاء حساب"
              ) : (
                "Create Account"
              )}
            </Button>
          </form>

          <p className="text-center mt-6 text-sm text-muted-foreground">
            {isAr
              ? "لديك حساب؟"
              : "Have account?"}{" "}
            <Link
              to="/login"
              className="text-primary hover:underline"
            >
              {isAr
                ? "تسجيل الدخول"
                : "Login"}
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
}