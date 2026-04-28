import api from "@/lib/api";

interface LoginData {
  email: string;
  password: string;
}

interface RegisterData {
  full_name: string;
  email: string;
  phone: string;
  password: string;
  password_confirmation: string;
  role: "entrepreneur" | "investor" | "explorer";
}

export const authService = {
  async login(data: LoginData) {
    const res = await api.post("/login", data);

    localStorage.setItem("auth_token", res.data.token);
    localStorage.setItem("auth_user", JSON.stringify(res.data.user));

    return res.data;
  },

  async register(data: RegisterData) {
    const res = await api.post("/register", data);

    localStorage.setItem("auth_token", res.data.token);
    localStorage.setItem("auth_user", JSON.stringify(res.data.user));

    return res.data;
  },

  async logout() {
    await api.post("/logout");

    localStorage.removeItem("auth_token");
    localStorage.removeItem("auth_user");
  },

  async me() {
    const res = await api.get("/me");
    return res.data;
  },
};