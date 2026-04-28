import { useEffect, useState } from "react";
import api from "@/lib/api";
import { useAuth } from "@/contexts/AuthContext";

export interface UserGateState {
  loading: boolean;
  hasPhone: boolean;
  kycStatus: "not_started" | "pending" | "approved" | "rejected";
  isAdmin: boolean;
  canTransact: boolean;
  refresh: () => Promise<void>;
}

export function useUserGate(): UserGateState {
  const { user, userRole } = useAuth();

  const [loading, setLoading] = useState(true);
  const [hasPhone, setHasPhone] = useState(false);

  const [kycStatus, setKycStatus] =
    useState<UserGateState["kycStatus"]>("not_started");

  const load = async () => {
    if (!user) {
      setLoading(false);
      return;
    }

    setLoading(true);

    try {
      const { data } = await api.get("/user/gate");

      setHasPhone(!!data?.hasPhone);

      setKycStatus(
        (data?.kycStatus as UserGateState["kycStatus"]) ||
          "not_started"
      );
    } catch {
      setHasPhone(false);
      setKycStatus("not_started");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, [user?.id]);

  const isAdmin = userRole === "admin";
  const canTransact =
    hasPhone && kycStatus === "approved";

  return {
    loading,
    hasPhone,
    kycStatus,
    isAdmin,
    canTransact,
    refresh: load,
  };
}