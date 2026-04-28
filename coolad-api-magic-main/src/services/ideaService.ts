import api from "@/lib/api";

export const ideaService = {
  async getAll() {
    const res = await api.get("/ideas");
    return res.data;
  },

  async getById(id: string) {
    const res = await api.get(`/ideas/${id}`);
    return res.data;
  },

  async create(data: any) {
    const res = await api.post("/ideas", data);
    return res.data;
  },

  async update(id: string, data: any) {
    const res = await api.put(`/ideas/${id}`, data);
    return res.data;
  },

  async delete(id: string) {
    const res = await api.delete(`/ideas/${id}`);
    return res.data;
  },
};