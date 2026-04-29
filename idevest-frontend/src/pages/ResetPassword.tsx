import { useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { authService } from "@/services/authService";
import { getApiErrorMessage } from "@/lib/errors";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "@/hooks/use-toast";

export default function ResetPassword() {
  const navigate = useNavigate();
  const [params] = useSearchParams();

  const token = params.get("token") || "";
  const email = params.get("email") || "";

  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);

  const handleReset = async (e: React.FormEvent) => {
    e.preventDefault();

    setLoading(true);

    try {
      await authService.resetPassword({
        token,
        email,
        password,
        password_confirmation: password,
      });

      toast({
        title: "Success",
        description: "Password reset successfully",
      });

      navigate("/login");
    } catch (err) {
      toast({
        title: "Error",
        description: getApiErrorMessage(err, "Reset failed"),
        variant: "destructive",
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center px-4">
      <div className="w-full max-w-md glass p-8 rounded-2xl shadow-glass">
        <h1 className="text-2xl font-bold mb-6 text-center">
          Reset Password
        </h1>

        <form onSubmit={handleReset} className="space-y-4">
          <div>
            <Label>New Password</Label>
            <Input
              type="password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
            />
          </div>

          <Button
            type="submit"
            className="w-full"
            disabled={loading}
          >
            {loading ? "Loading..." : "Reset Password"}
          </Button>
        </form>
      </div>
    </div>
  );
}