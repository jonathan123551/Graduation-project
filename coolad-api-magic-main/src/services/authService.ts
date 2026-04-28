import api from "@/lib/api";

export const authService = {
  async login(email: string, password: string) {
    const { data } = await api.post("/auth/login", {
      email,
      password,
    });

    localStorage.setItem("token", data.token);
    localStorage.setItem("user", JSON.stringify(data.user));

    return data;
  },

  async register(payload: any) {
    const { data } = await api.post("/auth/register", payload);

    localStorage.setItem("token", data.token);
    localStorage.setItem("user", JSON.stringify(data.user));

    return data;
  },
  async resetPassword(payload: any) {
  const { data } = await api.post("/auth/reset-password", payload);
  return data;
},
  async logout() {
    await api.post("/auth/logout");

    localStorage.removeItem("token");
    localStorage.removeItem("user");
  },
  async forgotPassword(email: string) {
  const { data } = await api.post("/auth/forgot-password", { email });
  return data;
},
  async me() {
    const { data } = await api.get("/auth/me");
    return data;
  },
};