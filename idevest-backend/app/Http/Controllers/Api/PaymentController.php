<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Paymob escrow payments.
 *
 * Flow:
 *   1. POST /api/payments/start
 *        ↓ auth with Paymob  → auth_token
 *        ↓ create order      → paymob_order_id
 *        ↓ get payment_key   (auto_capture=false — funds are *authorized*
 *                             but not yet captured)
 *        ↓ build iframe URL  using PAYMOB_IFRAME_ID
 *      → returns { iframe_url, payment_key, transaction_id (local) }
 *
 *   2. Frontend loads the iframe; user completes the card flow. Paymob
 *      hits our webhook with the result.
 *
 *   3. POST /api/payments/webhook (public)
 *        ↓ record provider_transaction_id + status=authorized on the row
 *
 *   4. POST /api/payments/{id}/capture (after KYC passes)
 *        ↓ hits Paymob /acceptance/capture → sets status=captured
 *
 *   5. POST /api/payments/{id}/void (after KYC fails)
 *        ↓ hits Paymob /acceptance/void_refund/void → sets status=voided
 */
class PaymentController extends Controller
{
    protected const PAYMOB_BASE = 'https://accept.paymob.com/api';

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'min:1'],
            'currency'    => ['nullable', 'string', 'size:3'],
            'deal_id'     => ['nullable', 'integer', 'exists:deals,id'],
            'first_name'  => ['nullable', 'string', 'max:50'],
            'last_name'   => ['nullable', 'string', 'max:50'],
            'email'       => ['nullable', 'email'],
            'phone'       => ['nullable', 'string', 'max:20'],
        ]);

        $apiKey        = config('services.paymob.api_key');
        $integrationId = (int) config('services.paymob.integration_id');
        $iframeId      = (int) config('services.paymob.iframe_id');

        if (!$apiKey || !$integrationId) {
            return response()->json([
                'message' => 'Payment gateway is not configured on the server',
            ], 503);
        }

        $user = $request->user();
        $amountCents = (int) round(((float) $data['amount']) * 100);
        $currency    = strtoupper($data['currency'] ?? 'EGP');

        try {
            // Step 1: auth
            $auth = Http::timeout(15)
                ->post(self::PAYMOB_BASE . '/auth/tokens', [
                    'api_key' => $apiKey,
                ])
                ->throw()
                ->json();

            $authToken = $auth['token'] ?? null;
            if (!$authToken) {
                return response()->json(['message' => 'Paymob auth failed'], 502);
            }

            // Step 2: create order
            $order = Http::timeout(15)
                ->post(self::PAYMOB_BASE . '/ecommerce/orders', [
                    'auth_token'      => $authToken,
                    'delivery_needed' => false,
                    'amount_cents'    => $amountCents,
                    'currency'        => $currency,
                    'items'           => [],
                ])
                ->throw()
                ->json();

            $orderId = $order['id'] ?? null;
            if (!$orderId) {
                return response()->json(['message' => 'Paymob order creation failed'], 502);
            }

            // Step 3: get payment key (auto_capture=false → escrow)
            $billing = [
                'first_name'  => $data['first_name']  ?? ($user->full_name ?? 'User'),
                'last_name'   => $data['last_name']   ?? '—',
                'email'       => $data['email']       ?? ($user->email ?? 'user@example.com'),
                'phone_number'=> $data['phone']       ?? 'NA',
                'apartment'   => 'NA',
                'floor'       => 'NA',
                'street'      => 'NA',
                'building'    => 'NA',
                'shipping_method' => 'NA',
                'postal_code' => 'NA',
                'city'        => 'NA',
                'country'     => 'EG',
                'state'       => 'NA',
            ];

            $paymentKey = Http::timeout(15)
                ->post(self::PAYMOB_BASE . '/acceptance/payment_keys', [
                    'auth_token'     => $authToken,
                    'amount_cents'   => $amountCents,
                    'currency'       => $currency,
                    'expiration'     => 3600,
                    'order_id'       => $orderId,
                    'integration_id' => $integrationId,
                    'auto_capture'   => false,
                    'billing_data'   => $billing,
                ])
                ->throw()
                ->json();

            $paymentToken = $paymentKey['token'] ?? null;
            if (!$paymentToken) {
                return response()->json(['message' => 'Paymob payment-key failed'], 502);
            }
        } catch (Throwable $e) {
            Log::error('Paymob start failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Payment gateway temporarily unavailable',
                'detail'  => $e->getMessage(),
            ], 502);
        }

        $transaction = $this->buildTransaction([
            'user_id'                => $user->id,
            'deal_id'                => $data['deal_id'] ?? null,
            'amount'                 => $data['amount'],
            'currency'               => $currency,
            'payment_gateway'        => 'paymob',
            'provider_order_id'      => (string) $orderId,
            'provider_payment_token' => $paymentToken,
            'status'                 => 'pending',
            'metadata'               => [
                'amount_cents'    => $amountCents,
                'integration_id'  => $integrationId,
                'iframe_id'       => $iframeId,
            ],
        ]);

        return response()->json([
            'transaction_id' => $transaction->id,
            'order_id'       => $orderId,
            'payment_key'    => $paymentToken,
            'iframe_url'     => $iframeId
                ? "https://accept.paymob.com/api/acceptance/iframes/{$iframeId}?payment_token={$paymentToken}"
                : null,
        ]);
    }

    public function capture(Request $request, int $id): JsonResponse
    {
        return $this->finalize($request, $id, 'capture');
    }

    public function void(Request $request, int $id): JsonResponse
    {
        return $this->finalize($request, $id, 'void');
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $obj     = $payload['obj'] ?? $payload;

        $orderId       = data_get($obj, 'order.id')
            ?? data_get($obj, 'order_id');
        $transactionId = data_get($obj, 'id');
        $success       = (bool) data_get($obj, 'success', false);
        $pending       = (bool) data_get($obj, 'pending', false);

        if (!$orderId) {
            return response()->json(['message' => 'missing order id'], 400);
        }

        $row = Transaction::query()
            ->where('provider_order_id', (string) $orderId)
            ->first();

        if (!$row) {
            Log::warning('Paymob webhook for unknown order ' . $orderId);
            return response()->json(['message' => 'order not found'], 404);
        }

        if ($transactionId) {
            $row->transaction_id = (string) $transactionId;
        }

        // Funds authorized but not captured (because auto_capture=false).
        if ($success && !$pending) {
            $row->status = Schema::hasColumn('transactions', 'metadata')
                ? 'authorized'
                : 'pending';
        } elseif ($pending) {
            $row->status = 'pending';
        } else {
            $row->status = 'failed';
        }

        if (Schema::hasColumn('transactions', 'metadata')) {
            $row->metadata = array_merge((array) $row->metadata, [
                'webhook' => $obj,
                'seen_at' => now()->toIso8601String(),
            ]);
        }

        $row->save();

        return response()->json(['message' => 'ok']);
    }

    /**
     * Capture or void a previously-authorized Paymob transaction.
     */
    protected function finalize(Request $request, int $id, string $mode): JsonResponse
    {
        $row = Transaction::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!in_array($mode, ['capture', 'void'], true)) {
            abort(400);
        }

        $apiKey = config('services.paymob.api_key');
        if (!$apiKey) {
            return response()->json(['message' => 'Payment gateway not configured'], 503);
        }

        $providerTxn = $row->transaction_id;
        if (!$providerTxn) {
            return response()->json([
                'message' => "Transaction has not been authorized yet",
            ], 409);
        }

        try {
            $auth = Http::timeout(15)
                ->post(self::PAYMOB_BASE . '/auth/tokens', ['api_key' => $apiKey])
                ->throw()
                ->json();
            $authToken = $auth['token'] ?? null;

            $url = $mode === 'capture'
                ? self::PAYMOB_BASE . '/acceptance/capture'
                : self::PAYMOB_BASE . '/acceptance/void_refund/void';

            $resp = Http::timeout(15)
                ->withToken($authToken, 'Bearer')
                ->post($url, [
                    'transaction_id' => $providerTxn,
                    'amount_cents'   => (int) round(((float) $row->amount) * 100),
                ])
                ->throw()
                ->json();

            DB::transaction(function () use ($row, $mode, $resp) {
                $row->status = $mode === 'capture' ? 'captured' : 'voided';
                if (Schema::hasColumn('transactions', 'metadata')) {
                    $row->metadata = array_merge((array) $row->metadata, [
                        $mode . '_response' => $resp,
                        $mode . '_at'       => now()->toIso8601String(),
                    ]);
                }
                $row->save();
            });
        } catch (Throwable $e) {
            Log::error("Paymob {$mode} failed: " . $e->getMessage());
            return response()->json([
                'message' => "Paymob {$mode} failed",
                'detail'  => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'message'     => "Transaction {$mode}d",
            'transaction' => $row->fresh(),
        ]);
    }

    /**
     * Build a Transaction row, only setting columns that actually exist.
     * This lets the migration roll out independently of the code deploy
     * (e.g. if Railway picks up code before running migrate).
     */
    protected function buildTransaction(array $attrs): Transaction
    {
        $row = new Transaction();
        foreach ($attrs as $key => $value) {
            if (Schema::hasColumn('transactions', $key)) {
                if ($key === 'metadata' && is_array($value)) {
                    // Cast to array on the model; JSON columns.
                    $row->metadata = $value;
                } else {
                    $row->{$key} = $value;
                }
            }
        }
        $row->save();
        return $row;
    }
}
