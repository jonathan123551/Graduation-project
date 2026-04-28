import { useEffect, useState } from "react";
import api from "@/lib/api";

export function useMessages() {
  const [messages, setMessages] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  const loadMessages = async () => {
    try {
      const res = await api.get("/messages");
      setMessages(res.data);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadMessages();
  }, []);

  return {
    messages,
    loading,
    reload: loadMessages,
  };
}