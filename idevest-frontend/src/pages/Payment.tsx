import { useEffect, useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { Navigate } from "react-router-dom";

import { useAuth } from "@/contexts/AuthContext";
import { startPayment } from "@/lib/payments";
import { getApiErrorMessage } from "@/lib/errors";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "@/hooks/use-toast";
import { ArrowLeft, CreditCard, Loader2 } from "lucide-react";

/**
 * Paymob escrow payment page.
 *
 * Usage: navigate to `/pay?amount=500&deal_id=42` (or just `/pay`) and the
 * user enters an amount, clicks "Pay Now", and completes the card flow
 * inside the embedded Paymob iframe. Funds are *authorized* (held) at this
 * point — they're only captured later by /api/payments/:id/capture once
 * KYC clears.
 */
export default function Payment() {
  const { user, loading: authLoading } = useAuth();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();

  const qsAmount = Number(searchParams.get("amount")) || 0;
  const dealId = searchParams.get("deal_id");

  const [amount, setAmount] = useState<number>(qsAmount);
  const [busy, setBusy] = useState(false);
  const [iframeUrl, setIframeUrl] = useState<string | null>(null);
  const [transactionId, setTransactionId] = useState<number | null>(null);

  useEffect(() => {
    if (qsAmount && !amount) setAmount(qsAmount);
  }, [qsAmount, amount]);

  if (authLoading) {
    return (
      <div className="min-h-[60vh] flex items-center justify-center">
        <Loader2 className="h-6 w-6 animate-spin text-primary" />
      </div>
    );
  }
  if (!user) return <Navigate to="/login" replace />;

  const onPay = async () => {
    if (!amount || amount <= 0) {
      toast({
        title: "Error",
        description: "Enter a valid amount",
        variant: "destructive",
      });
      return;
    }

    setBusy(true);
    try {
      const result = await startPayment({
        amount,
        currency: "EGP",
        deal_id: dealId ? Number(dealId) : undefined,
        email: user.email ?? undefined,
        first_name: user.full_name?.split(" ")[0],
        last_name: user.full_name?.split(" ").slice(1).join(" ") || "—",
      });

      setTransactionId(result.transaction_id);

      if (!result.iframe_url) {
        toast({
          title: "Paymob iframe not configured",
          description:
            "Server returned a payment key but no iframe URL. Set PAYMOB_IFRAME_ID on the backend.",
          variant: "destructive",
        });
        return;
      }

      setIframeUrl(result.iframe_url);
    } catch (error) {
      toast({
        title: "Error",
        description: getApiErrorMessage(error, "Payment could not start"),
        variant: "destructive",
      });
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="container mx-auto px-4 py-10 max-w-3xl">
      <button
        onClick={() => navigate(-1)}
        className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-6"
      >
        <ArrowLeft className="h-4 w-4 me-1" />
        Back
      </button>

      <div className="glass rounded-2xl p-6 shadow-glass space-y-6">
        <div className="flex items-center gap-3">
          <CreditCard className="h-6 w-6 text-primary" />
          <h1 className="text-2xl font-bold">Payment</h1>
        </div>

        {!iframeUrl ? (
          <div className="space-y-4">
            <div>
              <Label>Amount (EGP)</Label>
              <Input
                type="number"
                min={1}
                step="0.01"
                value={amount}
                onChange={(e) => setAmount(Number(e.target.value))}
                placeholder="500"
              />
            </div>

            <Button onClick={onPay} disabled={busy} className="w-full">
              {busy ? (
                <Loader2 className="h-4 w-4 animate-spin me-2" />
              ) : (
                <CreditCard className="h-4 w-4 me-2" />
              )}
              {busy ? "Starting..." : "Pay Now"}
            </Button>

            <p className="text-xs text-muted-foreground">
              Funds are held in escrow and only captured after your KYC is
              verified. If verification fails, the hold is released.
            </p>
          </div>
        ) : (
          <div className="space-y-3">
            <iframe
              src={iframeUrl}
              title="Paymob checkout"
              className="w-full h-[600px] rounded-xl border border-border/60"
            />
            {transactionId !== null && (
              <p className="text-xs text-muted-foreground">
                Transaction #{transactionId}. Keep this tab open until
                Paymob confirms — we'll update the status automatically.
              </p>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
