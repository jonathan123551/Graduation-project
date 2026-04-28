import { useAuth } from "@/contexts/AuthContext";

export default function useUserGate() {
  const { user } = useAuth();

  return {
    isLoggedIn: !!user,
    role: user?.role || null,
  };
}