<script>
import AbstractEntityTable from '@wexample/symfony-design-system/components/collection/table/abstract-entity-table/abstract-entity-table.vue';
import { numberFormatMoney } from '@wexample/symfony-design-system/js/Helper/NumberHelper';
import Payment from '@wexample/symfony-payment/Entity/Payment';
import { PAYMENT_STATUS_TONES, paymentStatusTone } from '../../js/Helper/PaymentStatusHelper';

const T = 'WexampleSymfonyPaymentDsBundle.components.payment-table.payment-table::';
const COMMON = 'WexampleSymfonyPaymentDsBundle.common.payment::';

// Payments as a list: the reader's own (`scope: 'mine'`), or — for the
// administration — everybody's (`scope: 'all'`), searched and narrowed by
// status. Each row opens its payment.
export default {
  extends: AbstractEntityTable,

  template: '#vue-template-wexample-symfony-payment-ds-bundle-components-payment-table-payment-table',

  props: {
    scope: {
      type: String,
      default: 'mine'
    }
  },

  data() {
    const all = this.scope === 'all';

    return {
      fill: true,
      sticky: true,
      searchable: all,
      showCount: all,
      pageLength: 20,
      defaultSort: { key: 'dateCreated', direction: 'desc' },
      barFilters: all
        ? [{
          key: 'status',
          label: this.trans(`${T}column.status`),
          multiple: true,
          options: Object.keys(PAYMENT_STATUS_TONES).map((status) => ({ value: status, label: this.trans(`${COMMON}status.${status}`) }))
        }]
        : []
    };
  },

  methods: {
    getEntityClass() {
      return Payment;
    },

    getCollectionQuery() {
      return { ...AbstractEntityTable.methods.getCollectionQuery.call(this), scope: this.scope };
    },

    getStateKey() {
      return `payments.${this.scope}`;
    },

    getColumnsConfiguration() {
      const t = (key) => this.trans(`${T}${key}`);
      const all = this.scope === 'all';

      return [
        { key: 'dateCreated', label: t('column.date'), cell: 'date', sortable: true },
        {
          key: 'description',
          label: t('column.description'),
          format: (value) => value || t('no_description'),
          href: {
            route: all ? 'payment_admin_show' : 'payments_show',
            parameters: (row) => ({ id: row.id })
          }
        },
        ...(all ? [{ key: 'customerEmail', label: t('column.customer'), secondary: true }] : []),
        {
          key: 'amount',
          label: t('column.amount'),
          align: 'right',
          sortable: true,
          format: (value, row) => numberFormatMoney(value, row.currencyCode)
        },
        { key: 'method', label: t('column.method'), format: (value) => this.trans(`${COMMON}method.${value}`) },
        ...(all ? [{ key: 'provider', label: t('column.provider'), secondary: true, sortable: true }] : []),
        {
          key: 'status',
          label: t('column.status'),
          cell: 'status',
          sortable: true,
          format: (value) => ({ type: paymentStatusTone(value), label: this.trans(`${COMMON}status.${value}`) })
        }
      ];
    }
  }
};
</script>
