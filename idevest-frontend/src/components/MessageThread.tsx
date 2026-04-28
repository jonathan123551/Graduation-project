import { useState, useEffect, useRef } from "react";
import { useAuth } from "@/contexts/AuthContext";
import { useLanguage } from "@/i18n/LanguageContext";
import api from "@/lib/api";

import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { toast } from "@/hooks/use-toast";

import {
  Send,
  Loader2,
  ArrowLeft,
  User,
} from "lucide-react";

import {
  analyzeMessage,
  BLOCKED_MESSAGE_EN,
  BLOCKED_MESSAGE_AR,
} from "@/lib/chatFilter";

interface Message {
  id: string;
  sender_id: string;
  receiver_id: string;
  content: string;
  created_at: string;
  read: boolean;
}

interface MessageThreadProps {
  otherUserId: string;
  otherUserName: string;
  ideaId?: string;
  onBack: () => void;
}

export default function MessageThread({
  otherUserId,
  otherUserName,
  ideaId,
  onBack,
}: MessageThreadProps) {
  const { user } = useAuth();
  const { t } = useLanguage();

  const [messages, setMessages] = useState<Message[]>([]);
  const [input, setInput] = useState("");
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);

  const bottomRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!user) return;

    const load = async () => {
      try {
        const { data } = await api.get(
          `/messages/thread/${otherUserId}`
        );

        setMessages(data.data ?? data ?? []);

        await api.post("/messages/read", {
          other_user_id: otherUserId,
        });
      } catch (error) {
        console.log(error);
      } finally {
        setLoading(false);
      }
    };

    load();
  }, [user, otherUserId]);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({
      behavior: "smooth",
    });
  }, [messages]);

  const handleSend = async () => {
    if (!input.trim() || !user || sending)
      return;

    const analysis = analyzeMessage(input);

    if (analysis.blocked) {
      toast({
        title: "⚠️",
        description:
          document.documentElement.lang === "ar"
            ? BLOCKED_MESSAGE_AR
            : BLOCKED_MESSAGE_EN,
        variant: "destructive",
      });

      return;
    }

    setSending(true);

    try {
      const { data } = await api.post(
        "/messages/send",
        {
          receiver_id: otherUserId,
          content: input.trim(),
          idea_id: ideaId || null,
        }
      );

      const newMsg =
        data.data ??
        data.message ??
        data;

      if (newMsg) {
        setMessages((prev) => [
          ...prev,
          newMsg,
        ]);
      }

      setInput("");
    } catch (error: any) {
      toast({
        title: t.common.error,
        description:
          error?.response?.data?.message ||
          "Failed to send",
        variant: "destructive",
      });
    } finally {
      setSending(false);
    }
  };

  return (
    <div className="flex flex-col h-full">
      {/* Header */}
      <div className="flex items-center gap-3 p-4 border-b border-border/50">
        <Button
          variant="ghost"
          size="icon"
          onClick={onBack}
        >
          <ArrowLeft className="h-4 w-4" />
        </Button>

        <div className="w-8 h-8 rounded-full bg-muted flex items-center justify-center">
          <User className="h-4 w-4 text-muted-foreground" />
        </div>

        <span className="font-medium text-foreground">
          {otherUserName}
        </span>
      </div>

      {/* Messages */}
      <div className="flex-1 overflow-y-auto p-4 space-y-3">
        {loading ? (
          <div className="flex justify-center py-10">
            <Loader2 className="h-5 w-5 animate-spin text-primary" />
          </div>
        ) : messages.length === 0 ? (
          <p className="text-center text-muted-foreground py-10">
            {t.dashboard.noMessages}
          </p>
        ) : (
          messages.map((msg) => (
            <div
              key={msg.id}
              className={`flex ${
                msg.sender_id === user?.id
                  ? "justify-end"
                  : "justify-start"
              }`}
            >
              <div
                className={`max-w-[70%] rounded-2xl px-4 py-2.5 text-sm ${
                  msg.sender_id === user?.id
                    ? "gradient-primary text-primary-foreground rounded-ee-md"
                    : "bg-muted text-foreground rounded-es-md"
                }`}
              >
                <p className="whitespace-pre-wrap">
                  {msg.content}
                </p>

                <span className="text-[10px] opacity-60 mt-1 block">
                  {new Date(
                    msg.created_at
                  ).toLocaleTimeString([], {
                    hour: "2-digit",
                    minute: "2-digit",
                  })}
                </span>
              </div>
            </div>
          ))
        )}

        <div ref={bottomRef} />
      </div>

      {/* Input */}
      <div className="p-3 border-t border-border/50 flex gap-2 items-end">
        <Textarea
          value={input}
          onChange={(e) =>
            setInput(e.target.value)
          }
          onKeyDown={(e) => {
            if (
              e.key === "Enter" &&
              !e.shiftKey
            ) {
              e.preventDefault();
              handleSend();
            }
          }}
          placeholder={t.chat.placeholder}
          className="min-h-[44px] max-h-24 resize-none bg-transparent border-0 focus-visible:ring-0 text-sm"
          rows={1}
        />

        <Button
          onClick={handleSend}
          disabled={
            !input.trim() || sending
          }
          size="icon"
          className="gradient-primary border-0 text-primary-foreground shrink-0 h-10 w-10"
        >
          {sending ? (
            <Loader2 className="h-4 w-4 animate-spin" />
          ) : (
            <Send className="h-4 w-4" />
          )}
        </Button>
      </div>
    </div>
  );
}