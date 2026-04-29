import { useState, useEffect } from "react";
import { useParams, Link, useNavigate } from "react-router-dom";
import { useLanguage } from "@/i18n/LanguageContext";
import { useAuth } from "@/contexts/AuthContext";
import api from "@/lib/api";

import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Progress } from "@/components/ui/progress";
import { toast } from "@/hooks/use-toast";
import { motion } from "framer-motion";

import {
  ArrowLeft,
  Bookmark,
  BookmarkCheck,
  DollarSign,
  TrendingUp,
  Users,
  Shield,
  Target,
  Clock,
  MapPin,
  Loader2,
  Sparkles,
  BarChart3,
  Lock,
  CheckCircle,
  AlertTriangle,
  XCircle,
  MessageCircle,
} from "lucide-react";

interface IdeaData {
  id: string;
  title: string;
  description: string;
  sector: string;
  location: string;
  founder_id: string;
  created_at: string;

  capital_required?: string;
  expected_revenue?: string;
  team_size?: string;
  team_experience?: string;
  competitors?: string;
  competitive_advantage?: string;
  target_audience?: string;
  timeline?: string;

  ai_score: number;
  market_score: number;
  innovation_score: number;
  execution_score: number;
  investment_score: number;
  risk_score: number;

  decision?: string;
  ai_evaluation?: string;
  ai_recommendations?: string;

  profiles?: {
    full_name: string;
  } | null;
}

export default function IdeaDetail() {
  const { id } = useParams();
  const { t } = useLanguage();
  const { user } = useAuth();
  const navigate = useNavigate();

  const [idea, setIdea] = useState<IdeaData | null>(null);
  const [loading, setLoading] = useState(true);
  const [saved, setSaved] = useState(false);
  const [accessStatus, setAccessStatus] = useState<string | null>(null);

  useEffect(() => {
    if (!id) return;

    loadIdea();
  }, [id, user]);

  const loadIdea = async () => {
    try {
      setLoading(true);

      const res = await api.get(`/ideas/${id}`);
      const data = res.data.data ?? res.data;

      setIdea(data);

      if (user) {
        try {
          const savedRes = await api.get(`/saved-ideas/check/${id}`);
          setSaved(savedRes.data.saved ?? false);
        } catch {
          /* ignore: saved-state check is best-effort */
        }

        try {
          const accessRes = await api.get(`/access-requests/check/${id}`);
          setAccessStatus(accessRes.data.status ?? null);
        } catch {
          /* ignore: access-request check is best-effort */
        }
      }
    } catch {
      setIdea(null);
    } finally {
      setLoading(false);
    }
  };

  const toggleSave = async () => {
    if (!user || !id) return;

    try {
      if (saved) {
        await api.delete(`/saved-ideas/${id}`);
        setSaved(false);
      } else {
        await api.post("/saved-ideas", { idea_id: id });
        setSaved(true);
      }
    } catch {
      toast({
        title: "Error",
        description: "Failed to update save status",
        variant: "destructive",
      });
    }
  };

  const requestAccess = async () => {
    if (!idea || !id) return;

    try {
      await api.post("/access-requests", {
        idea_id: id,
        founder_id: idea.founder_id,
      });

      setAccessStatus("pending");

      toast({
        title: "Success",
        description: t.ideaDetail.accessRequested,
      });
    } catch {
      toast({
        title: "Error",
        description: "Failed to request access",
        variant: "destructive",
      });
    }
  };

  if (loading) {
    return (
      <div className="min-h-[60vh] flex items-center justify-center">
        <Loader2 className="h-6 w-6 animate-spin text-primary" />
      </div>
    );
  }

  if (!idea) {
    return (
      <div className="container mx-auto px-4 py-20 text-center">
        <p className="mb-4">{t.ideaDetail.notFound}</p>

        <Link to="/marketplace">
          <Button variant="outline">
            <ArrowLeft className="h-4 w-4 me-2" />
            {t.ideaDetail.backToMarketplace}
          </Button>
        </Link>
      </div>
    );
  }

  const isOwner = user?.id === idea.founder_id;
  const hasFullAccess = isOwner || accessStatus === "approved";

  const decision = idea.decision || "pending";

  const DecisionIcon =
    decision === "accepted"
      ? CheckCircle
      : decision === "needs_improvement"
      ? AlertTriangle
      : XCircle;

  const scores = [
    { label: "Overall", value: idea.ai_score, icon: Sparkles },
    { label: "Market", value: idea.market_score, icon: BarChart3 },
    { label: "Innovation", value: idea.innovation_score, icon: TrendingUp },
    { label: "Execution", value: idea.execution_score, icon: Target },
    { label: "Investment", value: idea.investment_score, icon: DollarSign },
    { label: "Risk", value: idea.risk_score, icon: Shield },
  ];

  return (
    <div className="container mx-auto px-4 py-10 max-w-5xl">
      <Link
        to="/marketplace"
        className="inline-flex items-center mb-6 text-sm"
      >
        <ArrowLeft className="h-4 w-4 me-1" />
        {t.ideaDetail.backToMarketplace}
      </Link>

      <motion.div
        initial={{ opacity: 0, y: 15 }}
        animate={{ opacity: 1, y: 0 }}
        className="glass rounded-2xl p-6 mb-6"
      >
        <div className="flex flex-col md:flex-row justify-between gap-4">
          <div>
            <div className="flex gap-2 flex-wrap items-center mb-2">
              <h1 className="text-2xl font-bold">
                {idea.title}
              </h1>

              <Badge>{idea.sector}</Badge>

              <div className="flex items-center gap-1">
                <DecisionIcon className="h-4 w-4" />
                <span className="uppercase text-sm">
                  {decision.replace("_", " ")}
                </span>
              </div>
            </div>

            <div className="text-sm text-muted-foreground flex gap-2 flex-wrap">
              <span>
                Founder: {idea.profiles?.full_name || "—"}
              </span>

              {idea.location && (
                <span className="flex items-center gap-1">
                  <MapPin className="h-3 w-3" />
                  {idea.location}
                </span>
              )}
            </div>
          </div>

          <div className="flex gap-2 flex-wrap">
            {user && !isOwner && (
              <>
                <Button
                  size="sm"
                  variant="outline"
                  onClick={toggleSave}
                >
                  {saved ? (
                    <BookmarkCheck className="h-4 w-4 me-1" />
                  ) : (
                    <Bookmark className="h-4 w-4 me-1" />
                  )}
                  Save
                </Button>

                {!accessStatus && (
                  <Button
                    size="sm"
                    onClick={requestAccess}
                  >
                    <Lock className="h-4 w-4 me-1" />
                    Request Access
                  </Button>
                )}

                {accessStatus === "approved" && (
                  <Button
                    size="sm"
                    onClick={() =>
                      navigate(
                        `/chat-founder/${idea.founder_id}`
                      )
                    }
                  >
                    <MessageCircle className="h-4 w-4 me-1" />
                    Chat
                  </Button>
                )}
              </>
            )}
          </div>
        </div>
      </motion.div>

      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        {scores.map((s, i) => (
          <div
            key={i}
            className="glass rounded-xl p-4 text-center"
          >
            <s.icon className="h-5 w-5 mx-auto mb-2 text-primary" />

            <div className="text-xl font-bold">
              {s.value}
            </div>

            <Progress
              value={s.value}
              className="h-1.5 my-2"
            />

            <div className="text-xs">
              {s.label}
            </div>
          </div>
        ))}
      </div>

      <Tabs defaultValue="overview">
        <TabsList className="mb-5">
          <TabsTrigger value="overview">
            Overview
          </TabsTrigger>

          <TabsTrigger value="evaluation">
            AI Evaluation
          </TabsTrigger>

          {hasFullAccess && (
            <TabsTrigger value="details">
              Details
            </TabsTrigger>
          )}
        </TabsList>

        <TabsContent value="overview">
          <div className="glass rounded-xl p-6">
            {idea.description}
          </div>
        </TabsContent>

        <TabsContent value="evaluation">
          <div className="glass rounded-xl p-6 whitespace-pre-wrap">
            {idea.ai_evaluation || "No data"}
          </div>
        </TabsContent>

        {hasFullAccess && (
          <TabsContent value="details">
            <div className="glass rounded-xl p-6 space-y-3">
              <p>
                Capital: {idea.capital_required}
              </p>
              <p>
                Revenue: {idea.expected_revenue}
              </p>
              <p>
                Team Size: {idea.team_size}
              </p>
              <p>
                Timeline: {idea.timeline}
              </p>
              <p>
                Competitors: {idea.competitors}
              </p>
            </div>
          </TabsContent>
        )}
      </Tabs>
    </div>
  );
}