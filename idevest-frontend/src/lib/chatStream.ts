/**
 * Streaming helper for the AI chat page.
 *
 * Talks to the Laravel backend (`POST /api/chat/stream`) which in turn
 * forwards an OpenAI-compatible SSE stream from the configured provider
 * (defaults to Groq).
 */
const API_BASE =
  import.meta.env.VITE_API_URL ||
  "https://backend-idevest.up.railway.app/api";

export interface ChatMessage {
  role: "user" | "assistant";
  content: string;
}

export async function streamChat({
  messages,
  onDelta,
  onDone,
  onError,
}: {
  messages: ChatMessage[];
  onDelta: (text: string) => void;
  onDone: () => void;
  onError: (error: string) => void;
}) {
  const token = localStorage.getItem("auth_token");

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
    let errMsg = "Failed to start chat";
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
        "AI provider is not configured on the server yet. Ask the admin to set GROQ_API_KEY.";
    onError(errMsg);
    return;
  }

  const reader = resp.body.getReader();
  const decoder = new TextDecoder();
  let textBuffer = "";
  let streamDone = false;

  const flushLine = (line: string) => {
    if (line.endsWith("\r")) line = line.slice(0, -1);
    if (line.startsWith(":") || line.trim() === "") return false;
    if (!line.startsWith("data: ")) return false;
    const jsonStr = line.slice(6).trim();
    if (jsonStr === "[DONE]") return true;
    try {
      const parsed = JSON.parse(jsonStr);
      const content = parsed.choices?.[0]?.delta?.content as
        | string
        | undefined;
      if (content) onDelta(content);
      if (parsed.error) onError(String(parsed.error));
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

  onDone();
}
