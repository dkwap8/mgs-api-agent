<template>
    <div class="space-y-6">
        <!-- Heading -->
        <section class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400">
                    Platform intelligence
                </p>

                <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                    Reports & Analytics
                </h2>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                    Monitor transaction volume, merchant growth, payout performance,
                    success rates and platform revenue.
                </p>
            </div>

            <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
                :disabled="loading"
                @click="fetchReport"
            >
                {{ loading ? 'Refreshing…' : '↻ Refresh report' }}
            </button>
        </section>

        <!-- Period filter -->
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="option in periodOptions"
                    :key="option.value"
                    type="button"
                    class="rounded-xl border px-4 py-2.5 text-sm font-black transition"
                    :class="filters.period === option.value
                        ? 'border-indigo-600 bg-indigo-600 text-white'
                        : 'border-slate-200 text-slate-600 hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:text-slate-300'"
                    @click="changePeriod(option.value)"
                >
                    {{ option.label }}
                </button>
            </div>

            <form
                v-if="filters.period === 'custom'"
                class="mt-5 grid gap-4 sm:grid-cols-[1fr_1fr_auto]"
                @submit.prevent="fetchReport"
            >
                <label>
                    <span class="mb-2 block text-sm font-black">
                        Start Date
                    </span>

                    <input
                        v-model="filters.start_date"
                        type="date"
                        class="report-input"
                    />
                </label>

                <label>
                    <span class="mb-2 block text-sm font-black">
                        End Date
                    </span>

                    <input
                        v-model="filters.end_date"
                        type="date"
                        class="report-input"
                    />
                </label>

                <button
                    type="submit"
                    class="self-end rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-black text-white hover:bg-indigo-700"
                >
                    Apply Dates
                </button>
            </form>
        </section>

        <section
            v-if="errorMessage"
            class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
        >
            {{ errorMessage }}
        </section>

        <!-- Financial hero -->
        <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-700 via-violet-700 to-purple-800 p-6 text-white shadow-2xl shadow-indigo-900/20 sm:p-7">
            <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full border-[48px] border-white/5"></div>

            <div class="relative z-10">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-indigo-100">
                            Successful transaction volume
                        </p>

                        <p class="mt-2 text-4xl font-black sm:text-5xl">
                            ₦{{ formatCurrency(overview.total_volume) }}
                        </p>

                        <p class="mt-2 text-sm text-indigo-100">
                            {{ periodLabel }}
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[560px]">
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-4">
                            <p class="text-xs font-black uppercase tracking-wider text-indigo-200">
                                Fees
                            </p>

                            <p class="mt-2 text-lg font-black">
                                ₦{{ formatCurrency(overview.total_fees) }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-white/15 bg-white/10 p-4">
                            <p class="text-xs font-black uppercase tracking-wider text-indigo-200">
                                Profit
                            </p>

                            <p class="mt-2 text-lg font-black">
                                {{ overview.total_profit == null ? 'Not calculated' : '₦' + formatCurrency(overview.total_profit) }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-white/15 bg-white/10 p-4">
                            <p class="text-xs font-black uppercase tracking-wider text-indigo-200">
                                Average Value
                            </p>

                            <p class="mt-2 text-lg font-black">
                                ₦{{ formatCurrency(overview.average_transaction) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main stats -->
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="summary in summaries"
                :key="summary.label"
                class="flex min-h-[135px] items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <span
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-xl"
                    :class="summary.iconClass"
                >
                    {{ summary.icon }}
                </span>

                <span class="min-w-0">
                    <span class="block truncate text-2xl font-black text-slate-950 dark:text-white">
                        {{ summary.value }}
                    </span>

                    <span class="mt-1 block text-sm font-semibold text-slate-500">
                        {{ summary.label }}
                    </span>
                </span>
            </article>
        </section>

        <!-- Trend and status -->
        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,1fr)]">
            <article class="report-card">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="report-heading">
                            Transaction Trend
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Daily successful and unsuccessful platform activity.
                        </p>
                    </div>
                </div>

                <div
                    v-if="loading"
                    class="py-20 text-center text-sm text-slate-500"
                >
                    Loading trend…
                </div>

                <div
                    v-else-if="dailyTrend.length === 0"
                    class="py-20 text-center text-sm text-slate-500"
                >
                    No transaction trend is available for this period.
                </div>

                <div v-else class="mt-7 overflow-x-auto">
                    <div class="flex min-w-[650px] items-end gap-3">
                        <div
                            v-for="item in dailyTrend"
                            :key="item.date"
                            class="flex min-w-10 flex-1 flex-col items-center"
                        >
                            <p class="mb-2 text-[10px] font-bold text-slate-400">
                                ₦{{ compactCurrency(item.amount) }}
                            </p>

                            <div class="flex h-48 w-full items-end rounded-xl bg-slate-100 p-1 dark:bg-slate-950">
                                <div
                                    class="w-full rounded-lg bg-gradient-to-t from-indigo-600 to-violet-500 transition-all"
                                    :style="{
                                        height: `${trendHeight(item.amount)}%`,
                                        minHeight: item.amount > 0 ? '8px' : '0',
                                    }"
                                ></div>
                            </div>

                            <p class="mt-2 text-[10px] font-bold text-slate-500">
                                {{ shortDate(item.date) }}
                            </p>
                        </div>
                    </div>
                </div>
            </article>

            <article class="report-card">
                <h3 class="report-heading">
                    Transaction Status
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Breakdown by processing status.
                </p>

                <div class="mt-6 space-y-5">
                    <div
                        v-for="item in transactionStatuses"
                        :key="item.status"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-2">
                                <span
                                    class="h-3 w-3 rounded-full"
                                    :class="statusDotClass(item.status)"
                                ></span>

                                <span class="text-sm font-bold capitalize">
                                    {{ formatStatus(item.status) }}
                                </span>
                            </div>

                            <span class="text-sm font-black">
                                {{ item.total }}
                            </span>
                        </div>

                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div
                                class="h-full rounded-full bg-indigo-600"
                                :style="{
                                    width: `${statusWidth(item.total)}%`,
                                }"
                            ></div>
                        </div>

                        <p class="mt-1 text-right text-xs text-slate-500">
                            ₦{{ formatCurrency(item.amount) }}
                        </p>
                    </div>

                    <p
                        v-if="transactionStatuses.length === 0"
                        class="py-10 text-center text-sm text-slate-500"
                    >
                        No transaction status information.
                    </p>
                </div>
            </article>
        </section>

        <!-- Merchants and types -->
        <section class="grid gap-5 xl:grid-cols-2">
            <article class="report-card">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="report-heading">
                            Top Merchants
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Highest successful transaction volume.
                        </p>
                    </div>

                    <router-link
                        to="/admin-panel/merchants"
                        class="text-sm font-black text-indigo-600 hover:underline dark:text-indigo-400"
                    >
                        View merchants
                    </router-link>
                </div>

                <div
                    v-if="topMerchants.length === 0"
                    class="py-14 text-center text-sm text-slate-500"
                >
                    No merchant performance data.
                </div>

                <div v-else class="mt-5 divide-y divide-slate-100 dark:divide-slate-800">
                    <div
                        v-for="(merchant, index) in topMerchants"
                        :key="merchant.merchant_id"
                        class="flex items-center justify-between gap-4 py-4"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-sm font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                {{ index + 1 }}
                            </span>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-black">
                                    {{ merchant.business_name }}
                                </p>

                                <p class="mt-1 truncate text-xs text-slate-500">
                                    {{ merchant.total_transactions }} transactions
                                </p>
                            </div>
                        </div>

                        <p class="shrink-0 text-sm font-black">
                            ₦{{ compactCurrency(merchant.total_volume) }}
                        </p>
                    </div>
                </div>
            </article>

            <article class="report-card">
                <h3 class="report-heading">
                    Transaction Types
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Volume distributed across platform transaction types.
                </p>

                <div
                    v-if="transactionTypes.length === 0"
                    class="py-14 text-center text-sm text-slate-500"
                >
                    No transaction type data.
                </div>

                <div v-else class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div
                        v-for="item in transactionTypes"
                        :key="item.type"
                        class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="rounded-xl bg-indigo-100 px-3 py-2 text-xs font-black uppercase text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                {{ formatType(item.type) }}
                            </span>

                            <span class="text-sm font-black">
                                {{ item.total }}
                            </span>
                        </div>

                        <p class="mt-4 text-xl font-black">
                            ₦{{ formatCurrency(item.amount) }}
                        </p>
                    </div>
                </div>
            </article>
        </section>

        <!-- Payout and merchants -->
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="metric-card">
                <p class="metric-label">Total Payout Value</p>
                <p class="metric-value">₦{{ formatCurrency(overview.total_payout_value) }}</p>
            </article>

            <article class="metric-card">
                <p class="metric-label">Successful Payouts</p>
                <p class="metric-value">₦{{ formatCurrency(overview.successful_payout_value) }}</p>
            </article>

            <article class="metric-card">
                <p class="metric-label">Pending Payouts</p>
                <p class="metric-value">₦{{ formatCurrency(overview.pending_payout_value) }}</p>
            </article>

            <article class="metric-card">
                <p class="metric-label">Verified Merchants</p>
                <p class="metric-value">{{ overview.verified_merchants }}</p>
            </article>
        </section>

        <!-- Recent -->
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-5 dark:border-slate-800">
                <div>
                    <h3 class="text-lg font-black">
                        Recent Activity
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Latest transactions from the selected period.
                    </p>
                </div>

                <router-link
                    to="/admin-panel/transactions"
                    class="text-sm font-black text-indigo-600 hover:underline dark:text-indigo-400"
                >
                    View all
                </router-link>
            </div>

            <div
                v-if="recentActivity.length === 0"
                class="py-16 text-center text-sm text-slate-500"
            >
                No recent activity.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
                        <tr class="text-left text-[11px] font-black uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-4">Reference</th>
                            <th class="px-5 py-4">Merchant</th>
                            <th class="px-5 py-4">Amount</th>
                            <th class="px-5 py-4">Type</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4">Date</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr
                            v-for="transaction in recentActivity"
                            :key="transaction.id"
                        >
                            <td class="px-5 py-4 font-mono text-sm font-black">
                                {{ transaction.reference || `TXN-${transaction.id}` }}
                            </td>

                            <td class="px-5 py-4 text-sm font-bold">
                                {{ transaction.merchant?.business_name || 'N/A' }}
                            </td>

                            <td class="px-5 py-4 text-sm font-black">
                                ₦{{ formatCurrency(transaction.amount) }}
                            </td>

                            <td class="px-5 py-4 text-sm capitalize">
                                {{ formatType(transaction.type) }}
                            </td>

                            <td class="px-5 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-black"
                                    :class="statusClass(transaction.status)"
                                >
                                    {{ formatStatus(transaction.status) }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">
                                {{ formatDateTime(transaction.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<script>
import {
    computed,
    onMounted,
    ref,
} from 'vue';

import axios from 'axios';

export default {
    name: 'AdminReports',

    setup() {
        const loading = ref(false);
        const errorMessage = ref('');

        const overview = ref({
            total_merchants: 0,
            pending_merchants: 0,
            verified_merchants: 0,
            total_transactions: 0,
            successful_transactions: 0,
            pending_transactions: 0,
            failed_transactions: 0,
            success_rate: 0,
            average_transaction: 0,
            total_volume: 0,
            total_fees: 0,
            total_profit: 0,
            total_payout_value: 0,
            successful_payout_value: 0,
            pending_payout_value: 0,
        });

        const dailyTrend = ref([]);
        const transactionTypes = ref([]);
        const transactionStatuses = ref([]);
        const topMerchants = ref([]);
        const recentActivity = ref([]);

        const filters = ref({
            period: '30days',
            start_date: '',
            end_date: '',
        });

        const periodOptions = [
            { value: 'today', label: 'Today' },
            { value: 'yesterday', label: 'Yesterday' },
            { value: '7days', label: 'Last 7 Days' },
            { value: '30days', label: 'Last 30 Days' },
            { value: 'month', label: 'This Month' },
            { value: 'year', label: 'This Year' },
            { value: 'all', label: 'All Time' },
            { value: 'custom', label: 'Custom' },
        ];

        const summaries = computed(() => [
            {
                label: 'Transactions',
                value: overview.value.total_transactions,
                icon: '↻',
                iconClass:
                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
            },
            {
                label: 'Successful',
                value: overview.value.successful_transactions,
                icon: '✓',
                iconClass:
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            },
            {
                label: 'Success Rate',
                value: `${Number(overview.value.success_rate || 0).toFixed(2)}%`,
                icon: '%',
                iconClass:
                    'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
            },
            {
                label: 'New Merchants',
                value: overview.value.total_merchants,
                icon: '🏪',
                iconClass:
                    'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300',
            },
        ]);

        const periodLabel = computed(() => {
            return periodOptions.find(
                (option) =>
                    option.value === filters.value.period
            )?.label || 'Report period';
        });

        const maxTrendAmount = computed(() => {
            return Math.max(
                ...dailyTrend.value.map(
                    (item) => Number(item.amount || 0)
                ),
                1
            );
        });

        const maxStatusTotal = computed(() => {
            return Math.max(
                ...transactionStatuses.value.map(
                    (item) => Number(item.total || 0)
                ),
                1
            );
        });

        const fetchReport = async () => {
            if (
                filters.value.period === 'custom' &&
                (
                    !filters.value.start_date ||
                    !filters.value.end_date
                )
            ) {
                errorMessage.value =
                    'Please select both custom report dates.';

                return;
            }

            loading.value = true;
            errorMessage.value = '';

            try {
                const params = {
                    period: filters.value.period,
                };

                if (filters.value.period === 'custom') {
                    params.start_date =
                        filters.value.start_date;

                    params.end_date =
                        filters.value.end_date;
                }

                const response = await axios.get(
                    '/api/v1/admin/reports',
                    { params }
                );

                const data = response.data?.data || {};

                overview.value = {
                    ...overview.value,
                    ...(data.overview || {}),
                };

                dailyTrend.value =
                    data.daily_trend || [];

                transactionTypes.value =
                    data.transaction_types || [];

                transactionStatuses.value =
                    data.transaction_statuses || [];

                topMerchants.value =
                    data.top_merchants || [];

                recentActivity.value =
                    data.recent_activity || [];
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The report could not be loaded.';
            } finally {
                loading.value = false;
            }
        };

        const changePeriod = async (period) => {
            filters.value.period = period;

            if (period !== 'custom') {
                filters.value.start_date = '';
                filters.value.end_date = '';

                await fetchReport();
            }
        };

        const formatCurrency = (value) => {
            return new Intl.NumberFormat('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(value || '0.00');
        };

        const compactCurrency = (value) => {
            return new Intl.NumberFormat('en-NG', {
                notation: 'compact',
                maximumFractionDigits: 1,
            }).format(value || '0.00');
        };

        const shortDate = (value) => {
            if (!value) {
                return '';
            }

            return new Date(value).toLocaleDateString(
                'en-NG',
                {
                    day: '2-digit',
                    month: 'short',
                }
            );
        };

        const formatDateTime = (value) => {
            if (!value) {
                return 'N/A';
            }

            return new Date(value).toLocaleString(
                'en-NG',
                {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                }
            );
        };

        const trendHeight = (value) => {
            return Math.max(
                4,
                Math.round(
                    (
                        Number(value || 0) /
                        maxTrendAmount.value
                    ) * 100
                )
            );
        };

        const statusWidth = (value) => {
            return Math.max(
                3,
                Math.round(
                    (
                        Number(value || 0) /
                        maxStatusTotal.value
                    ) * 100
                )
            );
        };

        const formatStatus = (status) => {
            return String(status || 'unknown')
                .replaceAll('_', ' ')
                .replace(/\b\w/g, (letter) =>
                    letter.toUpperCase()
                );
        };

        const formatType = (type) => {
            return String(type || 'transaction')
                .replaceAll('_', ' ')
                .replace(/\b\w/g, (letter) =>
                    letter.toUpperCase()
                );
        };

        const normalizedStatus = (status) => {
            return String(status || '').toLowerCase();
        };

        const statusClass = (status) => {
            const value = normalizedStatus(status);

            if (
                [
                    'success',
                    'successful',
                    'completed',
                    'approved',
                    'paid',
                ].includes(value)
            ) {
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
            }

            if (
                [
                    'failed',
                    'rejected',
                    'cancelled',
                    'reversed',
                ].includes(value)
            ) {
                return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';
            }

            return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
        };

        const statusDotClass = (status) => {
            const value = normalizedStatus(status);

            if (
                [
                    'success',
                    'successful',
                    'completed',
                    'approved',
                    'paid',
                ].includes(value)
            ) {
                return 'bg-emerald-500';
            }

            if (
                [
                    'failed',
                    'rejected',
                    'cancelled',
                    'reversed',
                ].includes(value)
            ) {
                return 'bg-red-500';
            }

            return 'bg-amber-500';
        };

        onMounted(fetchReport);

        return {
            loading,
            errorMessage,
            overview,
            dailyTrend,
            transactionTypes,
            transactionStatuses,
            topMerchants,
            recentActivity,
            filters,
            periodOptions,
            summaries,
            periodLabel,
            fetchReport,
            changePeriod,
            formatCurrency,
            compactCurrency,
            shortDate,
            formatDateTime,
            trendHeight,
            statusWidth,
            formatStatus,
            formatType,
            statusClass,
            statusDotClass,
        };
    },
};
</script>

<style scoped>
.report-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid rgb(226 232 240);
    background: white;
    padding: 0.75rem 1rem;
    outline: none;
}

.report-input:focus {
    border-color: rgb(99 102 241);
    box-shadow: 0 0 0 4px rgb(99 102 241 / 0.1);
}

.report-card {
    border: 1px solid rgb(226 232 240);
    border-radius: 1.5rem;
    background: white;
    padding: 1.25rem;
    box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
}

.report-heading {
    font-size: 1.125rem;
    font-weight: 900;
    color: rgb(15 23 42);
}

.metric-card {
    min-height: 120px;
    border: 1px solid rgb(226 232 240);
    border-radius: 1.5rem;
    background: white;
    padding: 1.25rem;
}

.metric-label {
    font-size: 0.75rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgb(148 163 184);
}

.metric-value {
    margin-top: 0.75rem;
    font-size: 1.5rem;
    font-weight: 900;
    color: rgb(15 23 42);
}

:global(.dark) .report-input {
    border-color: rgb(51 65 85);
    background: rgb(2 6 23);
    color: white;
}

:global(.dark) .report-card,
:global(.dark) .metric-card {
    border-color: rgb(30 41 59);
    background: rgb(15 23 42);
}

:global(.dark) .report-heading,
:global(.dark) .metric-value {
    color: white;
}
</style>
