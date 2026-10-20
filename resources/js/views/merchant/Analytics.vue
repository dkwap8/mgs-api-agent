<template>
    <div class="space-y-6">
        <section class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-950 dark:text-white">
                    Analytics
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Collections, payouts and growth for the selected business.
                </p>
            </div>

            <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black shadow-sm hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900"
                :disabled="loading"
                @click="fetchAnalytics"
            >
                {{ loading ? 'Refreshing…' : '↻ Refresh' }}
            </button>
        </section>

        <section
            v-if="errorMessage"
            class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
        >
            {{ errorMessage }}
        </section>

        <section
            v-if="!loading && !analytics.business.is_approved"
            class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900/50 dark:bg-amber-950/30"
        >
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-black text-amber-900 dark:text-amber-200">
                        ⚠️ Activate this business
                    </p>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                        Complete verification for {{ analytics.business.name || 'this business' }} to accept live payments.
                    </p>
                </div>

                <router-link
                    to="/user/activate"
                    class="rounded-xl bg-amber-600 px-5 py-3 text-center text-sm font-black text-white hover:bg-amber-700"
                >
                    Complete verification →
                </router-link>
            </div>
        </section>

        <section
            v-else-if="!loading"
            class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900/50 dark:bg-emerald-950/30"
        >
            <p class="font-black text-emerald-900 dark:text-emerald-200">
                ✅ Verified Business
            </p>
            <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-300">
                {{ analytics.business.name }} is active and ready to accept live payments.
            </p>
        </section>

        <section class="rounded-3xl bg-gradient-to-r from-indigo-600 to-violet-700 p-5 text-white shadow-xl sm:p-6">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-semibold text-indigo-100">
                        Available balance
                    </p>
                    <p class="mt-2 text-4xl font-black">
                        ₦{{ formatCurrency(analytics.balance) }}
                    </p>
                    <p class="mt-2 text-sm text-indigo-100">
                        Reserved: ₦{{ formatCurrency(analytics.reserved_balance) }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <MetricMini label="All-time collections" :value="money(analytics.collections_total)" />
                    <MetricMini label="All-time payouts" :value="money(analytics.payouts_total)" />
                    <MetricMini label="All-time purchases" :value="money(analytics.purchases_total)" />
                    <MetricMini label="Customers" :value="String(analytics.customers_total)" />
                </div>
            </div>
        </section>

        <section class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                class="rounded-xl px-4 py-2.5 text-sm font-black transition"
                :class="period === tab.key
                    ? 'bg-indigo-600 text-white'
                    : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                @click="changePeriod(tab.key)"
            >
                {{ tab.label }}
            </button>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
            <StatCard label="Total Collections" :value="money(analytics.total_collections)" :growth="analytics.growth.volume_percent" />
            <StatCard label="Total Payouts" :value="money(analytics.total_payouts)" :growth="analytics.growth.payouts_percent" />
            <StatCard label="Total Fees" :value="money(analytics.total_fees)" />
            <StatCard label="Successful Txns" :value="String(analytics.successful_transactions)" />
            <StatCard label="New Virtual Accounts" :value="String(analytics.new_virtual_accounts)" :growth="analytics.growth.virtual_accounts_percent" />
            <StatCard label="New Customers" :value="String(analytics.new_customers)" :growth="analytics.growth.customers_percent" />
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-black">
                        Collections vs Payouts
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ analytics.period.from }} to {{ analytics.period.to }}
                    </p>
                </div>

                <div class="flex gap-4 text-xs font-bold">
                    <span>● Collections</span>
                    <span>● Payouts</span>
                </div>
            </div>

            <div v-if="loading" class="py-24 text-center text-sm text-slate-500">
                Loading analytics…
            </div>

            <div v-else-if="chartMax === 0" class="mt-6 rounded-2xl border-2 border-dashed border-slate-200 py-20 text-center text-sm text-slate-500 dark:border-slate-700">
                No activity for this period.
            </div>

            <div v-else class="mt-8 overflow-x-auto pb-2">
                <div class="flex min-w-max items-end gap-3" style="height: 260px">
                    <div
                        v-for="(label, index) in analytics.chart_labels"
                        :key="`${label}-${index}`"
                        class="flex w-10 shrink-0 flex-col items-center justify-end"
                        style="height: 100%"
                    >
                        <div class="flex h-[220px] items-end gap-1">
                            <div
                                class="w-3 rounded-t bg-indigo-600"
                                :style="{ height: barHeight(analytics.chart_collections[index]) }"
                                :title="`Collections: ${money(analytics.chart_collections[index])}`"
                            ></div>
                            <div
                                class="w-3 rounded-t bg-emerald-500"
                                :style="{ height: barHeight(analytics.chart_payouts[index]) }"
                                :title="`Payouts: ${money(analytics.chart_payouts[index])}`"
                            ></div>
                        </div>
                        <span class="mt-2 -rotate-45 whitespace-nowrap text-[10px] text-slate-500">
                            {{ label }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <AttentionCard label="Failed Workflows" :value="analytics.attention.failed_workflows" />
            <AttentionCard label="Awaiting Settlement" :value="analytics.attention.awaiting_settlement" />
            <AttentionCard label="Pending Refunds" :value="analytics.attention.pending_refunds" />
            <AttentionCard label="Open Disputes" :value="analytics.attention.open_disputes" />
        </section>
    </div>
</template>

<script>
import {
    computed,
    defineComponent,
    h,
    onMounted,
    ref,
} from 'vue';
import axios from 'axios';

const MetricMini = defineComponent({
    props: {
        label: String,
        value: String,
    },
    setup(props) {
        return () => h('div', {
            class: 'rounded-xl bg-white/10 p-3 backdrop-blur-sm',
        }, [
            h('p', {
                class: 'text-[11px] font-semibold text-indigo-100',
            }, props.label),
            h('p', {
                class: 'mt-1 text-sm font-black',
            }, props.value),
        ]);
    },
});

const StatCard = defineComponent({
    props: {
        label: String,
        value: String,
        growth: {
            type: Number,
            default: null,
        },
    },
    setup(props) {
        return () => h('article', {
            class: 'rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900',
        }, [
            h('p', {
                class: 'text-[11px] font-black uppercase tracking-wider text-slate-400',
            }, props.label),
            h('p', {
                class: 'mt-3 text-xl font-black',
            }, props.value),
            props.growth !== null
                ? h('p', {
                    class: [
                        'mt-2 text-xs font-black',
                        props.growth >= 0
                            ? 'text-emerald-600'
                            : 'text-red-600',
                    ],
                }, `${props.growth >= 0 ? '+' : ''}${props.growth}%`)
                : null,
        ]);
    },
});

const AttentionCard = defineComponent({
    props: {
        label: String,
        value: [String, Number],
    },
    setup(props) {
        return () => h('article', {
            class: 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900',
        }, [
            h('p', {
                class: 'text-sm font-semibold text-slate-500',
            }, props.label),
            h('p', {
                class: 'mt-2 text-2xl font-black',
            }, String(props.value ?? 0)),
        ]);
    },
});

export default {
    name: 'MerchantAnalytics',

    components: {
        MetricMini,
        StatCard,
        AttentionCard,
    },

    setup() {
        const loading = ref(false);
        const errorMessage = ref('');
        const period = ref('30days');

        const analytics = ref({
            period: {
                key: '30days',
                from: '',
                to: '',
            },
            business: {
                id: null,
                name: '',
                verification_status: 'pending',
                is_verified: false,
                is_approved: false,
            },
            balance: 0,
            reserved_balance: 0,
            ledger_balance: 0,
            collections_total: 0,
            payouts_total: 0,
            purchases_total: 0,
            customers_total: 0,
            total_collections: 0,
            total_payouts: 0,
            total_fees: 0,
            successful_transactions: 0,
            new_virtual_accounts: 0,
            new_customers: 0,
            growth: {
                virtual_accounts_percent: 0,
                customers_percent: 0,
                volume_percent: 0,
                payouts_percent: 0,
                revenue_growth_percent: 0,
            },
            attention: {
                failed_workflows: 0,
                awaiting_settlement: 0,
                pending_refunds: 0,
                open_disputes: 0,
            },
            chart_labels: [],
            chart_collections: [],
            chart_payouts: [],
        });

        const tabs = [
            { key: 'today', label: 'Today' },
            { key: 'yesterday', label: 'Yesterday' },
            { key: '7days', label: '7 days' },
            { key: '30days', label: '30 days' },
            { key: 'month', label: 'This month' },
        ];

        const chartMax = computed(() => {
            return Math.max(
                0,
                ...analytics.value.chart_collections.map(Number),
                ...analytics.value.chart_payouts.map(Number)
            );
        });

        const fetchAnalytics = async () => {
            loading.value = true;
            errorMessage.value = '';

            try {
                const response = await axios.get('/api/v1/analytics', {
                    params: {
                        period: period.value,
                    },
                });

                analytics.value = {
                    ...analytics.value,
                    ...(response.data?.data || {}),
                    business: {
                        ...analytics.value.business,
                        ...(response.data?.data?.business || {}),
                    },
                    growth: {
                        ...analytics.value.growth,
                        ...(response.data?.data?.growth || {}),
                    },
                    attention: {
                        ...analytics.value.attention,
                        ...(response.data?.data?.attention || {}),
                    },
                };
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The analytics data could not be loaded.';
            } finally {
                loading.value = false;
            }
        };

        const changePeriod = async (value) => {
            period.value = value;
            await fetchAnalytics();
        };

        const formatCurrency = (value) => {
            return new Intl.NumberFormat('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(value || 0));
        };

        const money = (value) => {
            return `₦${formatCurrency(value)}`;
        };

        const barHeight = (value) => {
            if (chartMax.value <= 0) {
                return '0px';
            }

            const height = Math.max(
                (Number(value || 0) / chartMax.value) * 210,
                Number(value || 0) > 0 ? 4 : 0
            );

            return `${height}px`;
        };

        onMounted(fetchAnalytics);

        return {
            loading,
            errorMessage,
            period,
            analytics,
            tabs,
            chartMax,
            fetchAnalytics,
            changePeriod,
            formatCurrency,
            money,
            barHeight,
        };
    },
};
</script>
