/**
 * Extract a human-readable error message from an Axios/unknown error.
 *
 * Falls back to the provided default when the error doesn't expose a message
 * on `response.data.message`.
 */
export function getApiErrorMessage(err: unknown, fallback: string): string {
  if (err && typeof err === "object") {
    const e = err as {
      response?: { data?: { message?: unknown } };
      message?: unknown;
    };

    const msg = e.response?.data?.message;
    if (typeof msg === "string" && msg.length > 0) {
      return msg;
    }

    if (typeof e.message === "string" && e.message.length > 0) {
      return e.message;
    }
  }

  return fallback;
}
