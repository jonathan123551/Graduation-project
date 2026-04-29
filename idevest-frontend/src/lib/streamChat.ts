/**
 * Streams an AI evaluation of a startup idea using the same Laravel
 * `/api/chat/stream` pipeline that the AI Mentor chat uses.
 *
 * Previously this file pointed at a Supabase edge function
 * (`/functions/v1/evaluate-project`) that no longer exists — this repo
 * migrated off Supabase to Laravel + an OpenAI-compatible provider.
 */
const API_BASE =
  import.meta.env.VITE_API_URL ||
  "https://backend-idevest.up.railway.app/api";

export interface ProjectData {
  name: string;
  description: string;
  sector: string;
  location: string;
  capital: string;
  expectedRevenue: string;
  teamSize: string;
  teamExperience: string;
  competitors: string;
  competitiveAdvantage: string;
  targetAudience: string;
  timeline: string;
  additionalInfo?: string;
  documentContent?: string;
}

const SYSTEM_PROMPT = `You are an experienced startup investor and product evaluator. Given a startup idea, produce a concise evaluation in English with clear section headers. Always include these exact lines (each on its own line) so a downstream parser can extract numeric scores:
- Overall Score: <0-100>
- Market Score: <0-100>
- Risk Score: <0-100>
- Innovation Score: <0-100>
- Execution Score: <0-100>
- Investment Score: <0-100>
- Decision: accepted | needs_improvement | rejected

Then write 3–5 short paragraphs covering: market opportunity, competitive landscape, team/execution risk, financial viability, and a final recommendation. Keep the whole response under 500 words.`;

function buildUserPrompt(p: ProjectData): string {
  return [
    `Project name: ${p.name}`,
    `Description: ${p.description}`,
    `Sector: ${p.sector}`,
    `Location: ${p.location || "N/A"}`,
    `Capital required: ${p.capital || "N/A"}`,
    `Expected revenue: ${p.expectedRevenue || "N/A"}`,
    `Team size: ${p.teamSize || "N/A"}`,
    `Team experience: ${p.teamExperience || "N/A"}`,
    `Competitors: ${p.competitors || "N/A"}`,
    `Competitive advantage: ${p.competitiveAdvantage || "N/A"}`,
    `Target audience: ${p.targetAudience || "N/A"}`,
    `Timeline: ${p.timeline || "N/A"}`,
    p.additionalInfo ? `Additional info: ${p.additionalInfo}` : "",
    p.documentContent ? `Attached document content: ${p.documentContent.slice(0, 4000)}` : "",
  ]
    .filter(Boolean)
    .join("\n");
}

export async function streamEvaluation({
  projectData,
  onDelta,
  onDone,
  onError,
}: {
  projectData: ProjectData;
  onDelta: (text: string) => void;
  onDone: () => void;
  onError: (error: string) => void;
}) {
  const token = localStorage.getItem("auth_token");

  const messages = [
    { role: "system", content: SYSTEM_PROMPT },
    { role: "user", content: buildUserPrompt(projectData) },
  ];

  const resp = await fetch(`${API_BASE}/chat/stream`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "text/event-stream",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: JSON.stringify({ messages }),
  });

  if (!resp.ok || !resp.body) {
    let errMsg = "Evaluation failed to start";
    try {
      const body = await resp.json();
      if (body?.error || body?.message) errMsg = body.error ?? body.message;
    } catch {
      /* fall through with default message */
    }
    if (resp.status === 429) errMsg = "Rate limited, try again later.";
    if (resp.status === 401) errMsg = "Please sign in again.";
    if (resp.status === 503)
      errMsg =
        "AI provider is not configured on the server yet. Ask the admin to set AI_API_KEY.";
    onError(errMsg);
    return;
  }

  const reader = resp.body.getReader();
  const decoder = new TextDecoder();
  let textBuffer = "";
  let streamDone = false;
  let errored = false;

  const flushLine = (line: string) => {
    if (line.endsWith("\r")) line = line.slice(0, -1);
    if (line.startsWith(":") || line.trim() === "") return false;
    if (!line.startsWith("data: ")) return false;
    const jsonStr = line.slice(6).trim();
    if (jsonStr === "[DONE]") return true;
    try {
      const parsed = JSON.parse(jsonStr);
      const content = parsed.choices?.[0]?.delta?.content as string | undefined;
      if (content) onDelta(content);
      if (parsed.error) {
        errored = true;
        onError(String(parsed.error));
      }
    } catch {
      /* ignore bad frame */
    }
    return false;
  };

  while (!streamDone) {
    const { done, value } = await reader.read();
    if (done) break;
    textBuffer += decoder.decode(value, { stream: true });

    let newlineIndex: number;
    while ((newlineIndex = textBuffer.indexOf("\n")) !== -1) {
      const line = textBuffer.slice(0, newlineIndex);
      textBuffer = textBuffer.slice(newlineIndex + 1);
      if (flushLine(line)) {
        streamDone = true;
        break;
      }
    }
  }

  if (textBuffer.trim()) {
    for (const raw of textBuffer.split("\n")) {
      if (!raw) continue;
      if (flushLine(raw)) break;
    }
  }

  if (!errored) onDone();
}
