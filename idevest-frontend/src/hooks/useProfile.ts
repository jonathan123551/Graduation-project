import { useEffect, useState } from "react";
import { profileService } from "@/services/profileService";

export function useProfile() {
  const [profile, setProfile] = useState<unknown>(null);
  const [loading, setLoading] = useState(true);

  const loadProfile = async () => {
    try {
      const data = await profileService.me();
      setProfile(data);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadProfile();
  }, []);

  return {
    profile,
    loading,
    reload: loadProfile,
  };
}