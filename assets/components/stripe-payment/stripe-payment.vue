<script>
import Message from '@wexample/symfony-design-system/components/message/message.vue';
import Spinner from '@wexample/symfony-design-system/components/spinner/spinner.vue';

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
      this.elements = this.stripe.elements({ clientSecret: this.clientSecret });
      const element = this.elements.create('payment');
      element.on('ready', () => {
        this.ready = true;
      });
      element.mount(this.$refs.element);
    } catch {
      this.error = this.t('unavailable');
    }
  },

  methods: {
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
