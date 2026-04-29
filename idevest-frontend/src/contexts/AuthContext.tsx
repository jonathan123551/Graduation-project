import {
  createContext,
  useContext,
  useEffect,
  useState,
  ReactNode,
} from "react";

import { authService } from "@/services/authService";

export interface AuthUser {
  id?: string | number;
  role?: string;
  [key: string]: unknown;
}

interface AuthContextType {
  user: AuthUser | null;
  userRole: string | null;
  loading: boolean;
  isAuthenticated: boolean;
  setUser: (user: AuthUser | null) => void;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | null>(null);

export function AuthProvider({
  children,
}: {
  children: ReactNode;
}) {
  const [user, setUserState] = useState<AuthUser | null>(null);
  const [loading, setLoading] = useState(true);

  // Keep userRole derived from user so callers can set user (e.g. after login)
  // without having to also remember to set the role.
  const userRole = user?.role ?? null;

  const setUser = (next: AuthUser | null) => {
    setUserState(next);
  };

  const loadUser = async () => {
    const token = localStorage.getItem("auth_token");

    if (!token) {
      setLoading(false);
      return;
    }

    try {
      const data = (await authService.me()) as AuthUser;

      setUserState(data);
    } catch {
      localStorage.removeItem("auth_token");
      localStorage.removeItem("auth_user");
    }

    setLoading(false);
  };

  useEffect(() => {
    loadUser();
  }, []);

  const logout = async () => {
    try {
      await authService.logout();
    } catch {
      /* swallow: logout failure shouldn't block local cleanup */
    }

    setUserState(null);
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        userRole,
        loading,
        isAuthenticated: !!user,
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
    throw new Error("useAuth must be inside AuthProvider");
  }

  return ctx;
}