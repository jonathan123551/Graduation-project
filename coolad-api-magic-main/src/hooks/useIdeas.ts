import { useEffect, useState } from "react";
import { ideaService } from "@/services/ideaService";

export function useIdeas() {
  const [ideas, setIdeas] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  const loadIdeas = async () => {
    try {
      const data = await ideaService.getAll();
      setIdeas(data);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadIdeas();
  }, []);

  return {
    ideas,
    loading,
    reload: loadIdeas,
  };
}