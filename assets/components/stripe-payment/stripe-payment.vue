<script>
import Message from '@wexample/symfony-design-system/components/message/message.vue';
import Spinner from '@wexample/symfony-design-system/components/spinner/spinner.vue';
import { AssetsServiceEvents } from '@wexample/symfony-loader/js/Services/AssetsService';

const T = 'WexampleSymfonyPaymentDsBundle.components.stripe-payment.stripe-payment::';

// Stripe.js is loaded from Stripe, as Stripe asks: once for the page.
const STRIPE_JS = 'https://js.stripe.com/v3/';
let stripeJsLoading = null;

function loadStripeJs() {
  stripeJsLoading ??= new Promise((resolve, reject) => {
    if (window.Stripe) {
      resolve(window.Stripe);

      return;
    }

    const script = document.createElement('script');
    script.src = STRIPE_JS;
    script.onload = () => resolve(window.Stripe);
    script.onerror = () => reject(new Error('Stripe.js could not be loaded.'));
    document.head.appendChild(script);
  });

  return stripeJsLoading;
}

// A colour of the page as Stripe reads one: whatever the design system wrote
// it in — oklch, color-mix —, drawn once and read back as rgb.
function stripeColor(value) {
  const canvas = document.createElement('canvas');
  canvas.width = canvas.height = 1;
  const context = canvas.getContext('2d');
  context.fillStyle = value;
  context.fillRect(0, 0, 1, 1);
  const [r, g, b] = context.getImageData(0, 0, 1, 1).data;

  return `rgb(${r}, ${g}, ${b})`;
}

// A Stripe payment confirmed by its payer: Stripe's own form (Payment
// Element) — the card, and its bank's check when it asks one —, drawn with
// the public key and the payment's client secret. Stripe then sends the payer
// back to `returnUrl`, which waits for the outcome its webhook tells.
export default {
  template: '#vue-template-wexample-symfony-payment-ds-bundle-components-stripe-payment-stripe-payment',

  components: {
    Message,
    Spinner
  },

  props: {
    publicKey: {
      type: String,
      required: true
    },
    clientSecret: {
      type: String,
      required: true
    },
    returnUrl: {
      type: String,
      required: true
    }
  },

  data() {
    return {
      ready: false,
      busy: false,
      error: null
    };
  },

  async mounted() {
    try {
      const Stripe = await loadStripeJs();
      this.stripe = Stripe(this.publicKey);
      this.elements = this.stripe.elements({ clientSecret: this.clientSecret, appearance: this.appearance() });
      this.app.services.events.listen(AssetsServiceEvents.USAGE_CHANGE, this.onUsageChange);
      const element = this.elements.create('payment');
      element.on('ready', () => {
        this.ready = true;
      });
      element.mount(this.$refs.element);
    } catch {
      this.error = this.t('unavailable');
    }
  },

  beforeUnmount() {
    this.app.services.events.forget(AssetsServiceEvents.USAGE_CHANGE, this.onUsageChange);
  },

  methods: {
    // Stripe's form dressed as the page around it: its dark theme on a dark
    // scheme, the accent, the surfaces, the inks, the font and the corners
    // of the design system — read again when the theme changes.
    appearance() {
      const style = getComputedStyle(this.$el);
      const read = (name, fallback) => style.getPropertyValue(name).trim() || fallback;
      const dark = !!this.$el.closest('.usage-color-scheme-dark');

      return {
        theme: dark ? 'night' : 'stripe',
        variables: {
          colorPrimary: stripeColor(read('--accent-9', '#2f7de1')),
          colorBackground: stripeColor(read('--surface-1', dark ? '#1f1f1f' : '#ffffff')),
          colorText: stripeColor(read('--text-color-default', dark ? '#eeeeee' : '#222222')),
          colorTextSecondary: stripeColor(read('--text-color-muted', '#888888')),
          colorDanger: stripeColor(read('--error-9', '#df1b41')),
          fontFamily: read('--font-family-base', 'system-ui, sans-serif'),
          borderRadius: read('--radius-control', '6px')
        }
      };
    },

    onUsageChange() {
      this.elements?.update({ appearance: this.appearance() });
    },

    t(key) {
      return this.trans(`${T}${key}`);
    },

    async pay() {
      this.busy = true;
      this.error = null;

      const { error } = await this.stripe.confirmPayment({
        elements: this.elements,
        confirmParams: { return_url: this.returnUrl }
      });

      // Only an error comes back here: a confirmed payment leaves the page.
      this.error = error?.message ?? this.t('failed');
      this.busy = false;
    }
  }
};
</script>
