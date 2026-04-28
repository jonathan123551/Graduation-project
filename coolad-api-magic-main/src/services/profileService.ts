import api from "@/lib/api";

export const profileService = {
  async me() {
    const res = await api.get("/profile");
    return res.data;
  },

  async update(data: any) {
    const res = await api.put("/profile", data);
    return res.data;
  },
};