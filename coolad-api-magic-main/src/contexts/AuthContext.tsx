import {
  createContext,
  useContext,
  useEffect,
  useState,
  ReactNode,
} from "react";

import { authService } from "@/services/authService";

interface AuthContextType {
  user: any;
  loading: boolean;
  userRole: string | null;
  setUser: (user: any) => void;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [userRole, setUserRole] = useState<string | null>(null);

  useEffect(() => {
    const stored = localStorage.getItem("user");

    if (stored) {
      const parsed = JSON.parse(stored);
      setUser(parsed);
      setUserRole(parsed?.role || null);
    }

    authService
      .me()
      .then((data) => {
        setUser(data);
        setUserRole(data?.role || null);
        localStorage.setItem("user", JSON.stringify(data));
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  const logout = async () => {
    await authService.logout();
    setUser(null);
    setUserRole(null);
    localStorage.removeItem("user");
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        loading,
        userRole,
        setUser,
        logout,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);

  if (!ctx) {
    throw new Error("useAuth must be used inside AuthProvider");
  }

  return ctx;
}