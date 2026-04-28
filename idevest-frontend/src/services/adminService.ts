import api from "@/lib/api";

export const adminService = {
  async users() {
    const res = await api.get("/admin/users");
    return res.data;
  },

  async ideas() {
    const res = await api.get("/admin/ideas");
    return res.data;
  },

  async blockUser(id: string) {
    const res = await api.post(`/admin/users/${id}/block`);
    return res.data;
  },

  async unblockUser(id: string) {
    const res = await api.post(`/admin/users/${id}/unblock`);
    return res.data;
  },
};