// The tone a payment's status is drawn in, a marker's — the twin of
// PaymentStatusHelper on the server side.
export const PAYMENT_STATUS_TONES: Record<string, string> = {
  created: 'neutral',
  pending: 'running',
  processing: 'running',
  succeeded: 'success',
  failed: 'error',
  canceled: 'disabled',
  partially_refunded: 'warning',
  refunded: 'info'
};

// Where a payment still waits for its provider's word.
export const PAYMENT_STATUSES_OPEN = ['created', 'pending', 'processing'];

export function paymentStatusTone(status: string): string {
  return PAYMENT_STATUS_TONES[status] ?? 'neutral';
}
