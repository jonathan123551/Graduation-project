import axios from "axios";

const api = axios.create({
  baseURL: "https://idevest.up.railway.app/api",
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
});

export default api;