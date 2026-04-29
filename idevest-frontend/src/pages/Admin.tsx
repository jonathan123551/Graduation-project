/**
 * Admin Dashboard
 * Laravel API Version
 */

import { useEffect, useState } from "react";
import { Navigate } from "react-router-dom";
import { useAuth } from "@/contexts/AuthContext";
import { useLanguage } from "@/i18n/LanguageContext";
import api from "@/lib/api";

import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/components/ui/tabs";

import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Textarea } from "@/components/ui/textarea";
import { toast } from "@/hooks/use-toast";

import {
  Loader2,
  Shield,
  Users as UsersIcon,
  FileCheck,
  Lightbulb,
  Eye,
  Check,
  X,
  AlertCircle,
} from "lucide-react";

type KycRow = {
  id: string;
  user_id: string;
  status: string;
  full_legal_name: string | null;
  national_id: string | null;
  date_of_birth: string | null;
  phone_number: string | null;
  id_card_front_url: string | null;
  id_card_back_url: string | null;
  ai_verification_result: unknown;
  created_at: string;
};

type ProfileFull = {
  id: string;
  full_name: string;
  phone_number?: string | null;
  created_at: string;
  is_blocked?: boolean;
};

type IdeaRow = {
  id: string;
  title: string;
  sector: string;
  status: string;
  ai_score: number | null;
};

export default function Admin() {
  const { user, userRole, loading: authLoading } = useAuth();
  const { language } = useLanguage();

  const isAr = language === "ar";
  const isAdmin = userRole === "admin";

  const [loading, setLoading] = useState(true);

  const [kycList, setKycList] = useState<KycRow[]>([]);
  const [users, setUsers] = useState<ProfileFull[]>([]);
  const [ideas, setIdeas] = useState<IdeaRow[]>([]);

  const [selectedKyc, setSelectedKyc] = useState<KycRow | null>(null);
  const [rejectReason, setRejectReason] = useState("");
  const [newAdminName, setNewAdminName] = useState("");

  useEffect(() => {
    if (isAdmin) loadAll();
  }, [isAdmin]);

  const loadAll = async () => {
    try {
      setLoading(true);

      const [kycRes, usersRes, ideasRes] = await Promise.all([
        api.get("/admin/kyc"),
        api.get("/admin/users"),
        api.get("/admin/ideas"),
      ]);

      // Laravel returns plain arrays; keep the `.data.data` fallback for
      // any future paginated wrapping.
      setKycList(kycRes.data?.data ?? kycRes.data ?? []);
      setUsers(usersRes.data?.data ?? usersRes.data ?? []);
      setIdeas(ideasRes.data?.data ?? ideasRes.data ?? []);
    } catch {
      toast({
        title: "Error",
        description: "Failed loading admin data",
        variant: "destructive",
      });
    } finally {
      setLoading(false);
    }
  };

  if (authLoading) {
    return (
      <div className="min-h-[60vh] flex items-center justify-center">
        <Loader2 className="h-6 w-6 animate-spin text-primary" />
      </div>
    );
  }

  if (!user) return <Navigate to="/login" replace />;
  if (!isAdmin) return <Navigate to="/dashboard" replace />;

  const approveKyc = async (id: string) => {
    await api.post(`/admin/kyc/${id}/approve`);
    toast({ title: isAr ? "تمت الموافقة" : "Approved" });
    setSelectedKyc(null);
    loadAll();
  };

  const rejectKyc = async (id: string) => {
    if (!rejectReason.trim()) return;

    await api.post(`/admin/kyc/${id}/reject`, {
      reason: rejectReason,
    });

    toast({ title: isAr ? "تم الرفض" : "Rejected" });

    setRejectReason("");
    setSelectedKyc(null);
    loadAll();
  };

  const toggleIdea = async (id: string, status: string) => {
    await api.post(`/admin/ideas/${id}/toggle`, {
      status,
    });

    loadAll();
  };

  const blockUser = async (id: string) => {
    const reason = prompt("Reason") || "Violation";

    await api.post(`/admin/users/${id}/block`, {
      reason,
    });

    loadAll();
  };

  const unblockUser = async (id: string) => {
    await api.post(`/admin/users/${id}/unblock`);
    loadAll();
  };

  const grantAdmin = async () => {
    if (!newAdminName.trim()) return;

    await api.post("/admin/grant-role", {
      name: newAdminName,
      role: "admin",
    });

    setNewAdminName("");
    loadAll();
  };

  const StatusBadge = ({ status }: { status: string }) => {
    return (
      <Badge variant="outline">
        {status}
      </Badge>
    );
  };

  const KycModal = ({ row }: { row: KycRow }) => (
    <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
      <div className="glass rounded-2xl max-w-2xl w-full p-6">
        <div className="flex justify-between mb-4">
          <h3 className="font-bold text-lg">
            {row.full_legal_name}
          </h3>

          <Button
            variant="ghost"
            size="icon"
            onClick={() => setSelectedKyc(null)}
          >
            <X className="h-4 w-4" />
          </Button>
        </div>

        <div className="space-y-2 text-sm mb-4">
          <p>{row.national_id}</p>
          <p>{row.phone_number}</p>
          <p>{row.date_of_birth}</p>
        </div>

        <Textarea
          value={rejectReason}
          onChange={(e) =>
            setRejectReason(e.target.value)
          }
          placeholder="Reject reason..."
          className="mb-3"
        />

        <div className="flex justify-end gap-2">
          <Button
            variant="outline"
            onClick={() => rejectKyc(row.id)}
          >
            <X className="h-4 w-4 me-1" />
            Reject
          </Button>

          <Button
            onClick={() => approveKyc(row.id)}
            className="gradient-primary border-0 text-primary-foreground"
          >
            <Check className="h-4 w-4 me-1" />
            Approve
          </Button>
        </div>
      </div>
    </div>
  );

  return (
    <div className="container mx-auto px-4 py-8 max-w-6xl">
      <div className="flex items-center gap-3 mb-6">
        <div className="w-10 h-10 rounded-xl gradient-primary flex items-center justify-center">
          <Shield className="w-5 h-5 text-primary-foreground" />
        </div>

        <div>
          <h1 className="text-2xl font-bold">
            {isAr ? "لوحة التحكم" : "Admin Dashboard"}
          </h1>
        </div>
      </div>

      {loading ? (
        <div className="min-h-[40vh] flex items-center justify-center">
          <Loader2 className="h-6 w-6 animate-spin text-primary" />
        </div>
      ) : (
        <Tabs defaultValue="kyc">
          <TabsList className="grid grid-cols-3 w-full mb-6">
            <TabsTrigger value="kyc">
              <FileCheck className="h-4 w-4 me-1" />
              KYC
            </TabsTrigger>

            <TabsTrigger value="users">
              <UsersIcon className="h-4 w-4 me-1" />
              Users
            </TabsTrigger>

            <TabsTrigger value="ideas">
              <Lightbulb className="h-4 w-4 me-1" />
              Ideas
            </TabsTrigger>
          </TabsList>

          <TabsContent value="kyc" className="space-y-2">
            {kycList.map((k) => (
              <div
                key={k.id}
                className="glass rounded-xl p-4 flex justify-between"
              >
                <div>
                  <p>{k.full_legal_name}</p>
                  <p className="text-xs text-muted-foreground">
                    {k.phone_number}
                  </p>
                </div>

                <div className="flex gap-2">
                  <StatusBadge status={k.status} />

                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => setSelectedKyc(k)}
                  >
                    <Eye className="h-4 w-4 me-1" />
                    Review
                  </Button>
                </div>
              </div>
            ))}
          </TabsContent>

          <TabsContent value="users" className="space-y-2">
            <div className="glass rounded-xl p-4 mb-3 flex gap-2">
              <input
                value={newAdminName}
                onChange={(e) =>
                  setNewAdminName(e.target.value)
                }
                placeholder="Full name"
                className="flex-1 px-3 py-2 rounded-lg border"
              />

              <Button onClick={grantAdmin}>
                Grant Admin
              </Button>
            </div>

            {users.map((u) => (
              <div
                key={u.id}
                className="glass rounded-xl p-4 flex justify-between"
              >
                <div>
                  <p>{u.full_name}</p>
                </div>

                {u.is_blocked ? (
                  <Button
                    size="sm"
                    onClick={() =>
                      unblockUser(u.id)
                    }
                  >
                    Unblock
                  </Button>
                ) : (
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() =>
                      blockUser(u.id)
                    }
                  >
                    Block
                  </Button>
                )}
              </div>
            ))}
          </TabsContent>

          <TabsContent value="ideas" className="space-y-2">
            {ideas.map((i) => (
              <div
                key={i.id}
                className="glass rounded-xl p-4 flex justify-between"
              >
                <div>
                  <p>{i.title}</p>
                  <p className="text-xs text-muted-foreground">
                    {i.sector}
                  </p>
                </div>

                <Button
                  size="sm"
                  variant="outline"
                  onClick={() =>
                    toggleIdea(i.id, i.status)
                  }
                >
                  {i.status === "approved" ? (
                    <X className="h-4 w-4" />
                  ) : (
                    <Check className="h-4 w-4" />
                  )}
                </Button>
              </div>
            ))}
          </TabsContent>
        </Tabs>
      )}

      {selectedKyc && (
        <KycModal row={selectedKyc} />
      )}
    </div>
  );
}