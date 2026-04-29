import api from "@/lib/api";

export interface StartPaymentInput {
  amount: number;
  currency?: string;
  deal_id?: number;
  first_name?: string;
  last_name?: string;
  email?: string;
  phone?: string;
}

export interface StartPaymentResult {
  transaction_id: number;
  order_id: number | string;
  payment_key: string;
  iframe_url: string | null;
}

/**
 * Kick off a Paymob escrow payment. The backend:
 *   1. authenticates with Paymob,
 *   2. creates an order,
 *   3. gets a payment_key with auto_capture=false (funds authorized, held),
 *   4. returns an iframe URL the user can open to enter card details.
 *
 * After payment completes, Paymob hits /api/payments/webhook to mark the
 * local transaction as "authorized". Call capture()/void() once KYC is
 * decided.
 */
export async function startPayment(
  input: StartPaymentInput,
): Promise<StartPaymentResult> {
  const { data } = await api.post("/payments/start", input);
  return data as StartPaymentResult;
}

export async function capturePayment(transactionId: number) {
  const { data } = await api.post(`/payments/${transactionId}/capture`, {});
  return data;
}

export async function voidPayment(transactionId: number) {
  const { data } = await api.post(`/payments/${transactionId}/void`, {});
  return data;
}
