<script>
import Spinner from '@wexample/symfony-design-system/components/spinner/spinner.vue';
import { PAYMENT_STATUSES_OPEN } from '../../js/Helper/PaymentStatusHelper';

const T = 'WexampleSymfonyPaymentDsBundle.components.payment-status.payment-status::';

// How long between two readings of the status, and how long before the wait
// is said to be long.
const INTERVAL_MS = 2000;
const LONG_MS = 60000;

// The wait for a payment's outcome: its status read again and again, from
// the endpoint symfony-payment signs for the browser that started it, until
// the provider has spoken — the page is then loaded again, and says what
// happened. Nothing is shown once there is nothing to wait for.
export default {
  template: '#vue-template-wexample-symfony-payment-ds-bundle-components-payment-status-payment-status',

  components: {
    Spinner
  },

  props: {
    statusUrl: {
      type: String,
      required: true
    },
    status: {
      type: String,
      required: true
    }
  },

  data() {
    return {
      long: false
    };
  },

  computed: {
    waiting() {
      return PAYMENT_STATUSES_OPEN.includes(this.status);
    }
  },

  mounted() {
    if (!this.waiting) {
      return;
    }

    this.startedAt = Date.now();
    this.timer = setInterval(() => this.read(), INTERVAL_MS);
  },

  beforeUnmount() {
    clearInterval(this.timer);
  },

  methods: {
    t(key) {
      return this.trans(`${T}${key}`);
    },

    async read() {
      this.long = Date.now() - this.startedAt > LONG_MS;

      try {
        const response = await fetch(this.statusUrl, { headers: { Accept: 'application/json' } });
        const { status } = await response.json();

        if (status && status !== this.status) {
          clearInterval(this.timer);
          window.location.reload();
        }
      } catch {
        // A reading lost is read again at the next tick.
      }
    }
  }
};
</script>
