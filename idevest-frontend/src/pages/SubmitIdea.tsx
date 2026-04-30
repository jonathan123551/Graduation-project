import { useState, useRef, ComponentType } from "react";
import { useNavigate, Link } from "react-router-dom";
import { useLanguage } from "@/i18n/LanguageContext";
import { useAuth } from "@/contexts/AuthContext";
import api from "@/lib/api";
import {
  streamEvaluation,
  type ProjectData,
} from "@/lib/streamChat";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { toast } from "@/hooks/use-toast";

import {
  TrendingUp,
  DollarSign,
  Shield,
  Sparkles,
  Loader2,
  ArrowRight,
  LogIn,
  CheckCircle,
  AlertTriangle,
  XCircle,
  Target,
  FileUp,
} from "lucide-react";

interface ParsedScores {
  innovation: number;
  market: number;
  execution: number;
  investment: number;
  risk: number;
  overall: number;
  decision: string;
}

function parseScoresFromEvaluation(
  text: string
): ParsedScores {
  const getNum = (regex: RegExp) => {
    const match = text.match(regex);
    return match
      ? Math.min(parseInt(match[1]), 100)
      : 0;
  };

  // Match BOTH styles: "Innovation Score: 80" (what the system prompt asks for)
  // and "INNOVATION_SCORE: 80" (legacy underscore form). The previous regex only
  // matched the underscore form, so every idea came back with all-zero scores
  // and was auto-"rejected".
  const innovation = getNum(
    /INNOVATION[ _]SCORE:\s*(\d+)/i
  );

  const market = getNum(
    /MARKET[ _]SCORE:\s*(\d+)/i
  );

  const execution = getNum(
    /EXECUTION[ _]SCORE:\s*(\d+)/i
  );

  const investment = getNum(
    /INVESTMENT[ _]SCORE:\s*(\d+)/i
  );

  const risk = getNum(
    /RISK[ _]SCORE:\s*(\d+)/i
  );

  let overall = getNum(
    /OVERALL[ _]SCORE:\s*(\d+)/i
  );

  if (!overall) {
    overall = Math.round(
      innovation * 0.2 +
        market * 0.25 +
        execution * 0.2 +
        investment * 0.2 +
        (100 - risk) * 0.15
    );
  }

  // Honor an explicit "Decision: accepted | needs_improvement | rejected"
  // line if the model included one. Otherwise fall back to score thresholds.
  let decision = "rejected";
  const decisionMatch = text.match(
    /DECISION:\s*(accepted|needs[_ ]improvement|rejected)/i
  );
  if (decisionMatch) {
    decision = decisionMatch[1]
      .toLowerCase()
      .replace(" ", "_");
  } else if (overall >= 75) {
    decision = "accepted";
  } else if (overall >= 50) {
    decision = "needs_improvement";
  }

  return {
    innovation,
    market,
    execution,
    investment,
    risk,
    overall,
    decision,
  };
}

function DecisionBadge({
  decision,
}: {
  decision: string;
}) {
  if (decision === "accepted") {
    return (
      <Badge className="bg-primary/10 text-primary border-primary/20">
        <CheckCircle className="h-4 w-4 me-1" />
        ACCEPTED
      </Badge>
    );
  }

  if (decision === "needs_improvement") {
    return (
      <Badge className="bg-yellow-500/10 text-yellow-600 border-yellow-500/20">
        <AlertTriangle className="h-4 w-4 me-1" />
        NEEDS IMPROVEMENT
      </Badge>
    );
  }

  return (
    <Badge className="bg-destructive/10 text-destructive border-destructive/20">
      <XCircle className="h-4 w-4 me-1" />
      REJECTED
    </Badge>
  );
}

interface ScoreCardProps {
  label: string;
  value: number;
  icon: ComponentType<{ className?: string }>;
}

function ScoreCard({
  label,
  value,
  icon: Icon,
}: ScoreCardProps) {
  return (
    <div className="glass rounded-xl p-4 text-center">
      <Icon className="h-5 w-5 mx-auto mb-2 text-primary" />
      <div className="text-2xl font-bold">
        {value}
      </div>
      <Progress
        value={value}
        className="h-1.5 my-2"
      />
      <div className="text-xs text-muted-foreground">
        {label}
      </div>
    </div>
  );
}

export default function SubmitIdea() {
  const { t } = useLanguage();
  const { user } = useAuth();
  const navigate = useNavigate();

  const [isLoading, setIsLoading] =
    useState(false);

  const [showResult, setShowResult] =
    useState(false);

  const [parsedScores, setParsedScores] =
    useState<ParsedScores | null>(null);

  const [documentFile, setDocumentFile] =
    useState<File | null>(null);

  const fileInputRef =
    useRef<HTMLInputElement>(null);

  const [form, setForm] =
    useState<ProjectData>({
      name: "",
      description: "",
      sector: "",
      location: "",
      capital: "",
      expectedRevenue: "",
      teamSize: "",
      teamExperience: "",
      competitors: "",
      competitiveAdvantage: "",
      targetAudience: "",
      timeline: "",
      additionalInfo: "",
    });

  const set = (
    key: keyof ProjectData,
    value: string
  ) => {
    setForm((prev) => ({
      ...prev,
      [key]: value,
    }));
  };

  if (!user) {
    return (
      <div className="min-h-[60vh] flex items-center justify-center">
        <div className="glass rounded-2xl p-10 text-center">
          <LogIn className="h-10 w-10 mx-auto mb-4 text-primary" />

          <h2>{t.submit.loginRequired}</h2>

          <Link to="/login">
            <Button className="mt-4">
              {t.auth.signIn}
              <ArrowRight className="h-4 w-4 ms-2" />
            </Button>
          </Link>
        </div>
      </div>
    );
  }

  const handleSubmit = async (
    e: React.FormEvent
  ) => {
    e.preventDefault();

    setIsLoading(true);
    setShowResult(true);

    try {
      let uploadedFileUrl = null;

      if (documentFile) {
        const data = new FormData();

        data.append(
          "file",
          documentFile
        );

        const upload =
          await api.post(
            "/upload",
            data,
            {
              headers: {
                "Content-Type":
                  "multipart/form-data",
              },
            }
          );

        uploadedFileUrl =
          upload.data.url;
      }

      let finalText = "";

      await streamEvaluation({
        projectData: form,

        onDelta: (chunk) => {
          finalText += chunk;
        },

        onDone: async () => {
          const scores =
            parseScoresFromEvaluation(
              finalText
            );

          setParsedScores(scores);

          await api.post("/ideas", {
            title: form.name,
            description:
              form.description,
            sector: form.sector,
            location:
              form.location,
            capital_required:
              form.capital,
            expected_revenue:
              form.expectedRevenue,
            team_size:
              form.teamSize,
            team_experience:
              form.teamExperience,
            competitors:
              form.competitors,
            competitive_advantage:
              form.competitiveAdvantage,
            target_audience:
              form.targetAudience,
            timeline:
              form.timeline,
            additional_info:
              form.additionalInfo,
            document_url:
              uploadedFileUrl,

            ai_score:
              scores.overall,
            market_score:
              scores.market,
            risk_score:
              scores.risk,
            innovation_score:
              scores.innovation,
            execution_score:
              scores.execution,
            investment_score:
              scores.investment,
            decision:
              scores.decision,
            ai_evaluation:
              finalText,
          });

          toast({
            title: "Success",
            description:
              "Idea submitted successfully",
          });

          setIsLoading(false);
        },

        onError: () => {
          setIsLoading(false);

          toast({
            title: "Error",
            description:
              "AI Evaluation failed",
            variant:
              "destructive",
          });
        },
      });
    } catch {
      setIsLoading(false);

      toast({
        title: "Error",
        description:
          "Submit failed",
        variant:
          "destructive",
      });
    }
  };

  const isValid =
    form.name &&
    form.description &&
    form.sector &&
    form.capital;

  return (
    <div className="container mx-auto px-4 py-10 max-w-4xl">
      <h1 className="text-3xl font-bold mb-8">
        {t.submit.title}
      </h1>

      <form
        onSubmit={handleSubmit}
        className="space-y-4"
      >
        <Input
          placeholder="Project Name"
          value={form.name}
          onChange={(e) =>
            set(
              "name",
              e.target.value
            )
          }
        />

        <Textarea
          placeholder="Description"
          value={form.description}
          onChange={(e) =>
            set(
              "description",
              e.target.value
            )
          }
        />

        <Input
          placeholder="Sector"
          value={form.sector}
          onChange={(e) =>
            set(
              "sector",
              e.target.value
            )
          }
        />

        <Input
          placeholder="Capital Required"
          value={form.capital}
          onChange={(e) =>
            set(
              "capital",
              e.target.value
            )
          }
        />

        <input
          ref={fileInputRef}
          type="file"
          hidden
          onChange={(e) =>
            setDocumentFile(
              e.target.files?.[0] ||
                null
            )
          }
        />

        <div className="flex items-center gap-3">
          <Button
            type="button"
            variant="outline"
            onClick={() =>
              fileInputRef.current?.click()
            }
          >
            <FileUp className="h-4 w-4 me-2" />
            {documentFile
              ? "Change File"
              : "Upload File"}
          </Button>
          {documentFile && (
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <span className="truncate max-w-xs">
                {documentFile.name}
              </span>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                className="text-destructive hover:text-destructive"
                onClick={() => {
                  setDocumentFile(null);
                  if (fileInputRef.current) {
                    fileInputRef.current.value =
                      "";
                  }
                }}
              >
                Remove
              </Button>
            </div>
          )}
        </div>

        <Button
          type="submit"
          disabled={
            !isValid || isLoading
          }
          className="w-full"
        >
          {isLoading ? (
            <>
              <Loader2 className="h-4 w-4 animate-spin me-2" />
              Submitting...
            </>
          ) : (
            "Submit Idea"
          )}
        </Button>
      </form>

      {showResult &&
        parsedScores && (
          <div className="mt-10">
            <DecisionBadge
              decision={
                parsedScores.decision
              }
            />

            <div className="grid md:grid-cols-5 gap-3 mt-6">
              <ScoreCard
                label="Innovation"
                value={
                  parsedScores.innovation
                }
                icon={Sparkles}
              />

              <ScoreCard
                label="Market"
                value={
                  parsedScores.market
                }
                icon={TrendingUp}
              />

              <ScoreCard
                label="Execution"
                value={
                  parsedScores.execution
                }
                icon={Target}
              />

              <ScoreCard
                label="Investment"
                value={
                  parsedScores.investment
                }
                icon={DollarSign}
              />

              <ScoreCard
                label="Risk"
                value={
                  parsedScores.risk
                }
                icon={Shield}
              />
            </div>

            <Button
              className="mt-6"
              onClick={() =>
                navigate(
                  "/dashboard"
                )
              }
            >
              Go Dashboard
            </Button>
          </div>
        )}
    </div>
  );
}