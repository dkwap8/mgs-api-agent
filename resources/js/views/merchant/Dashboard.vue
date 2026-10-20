<template>
    <div class="space-y-6">
        <div v-if="dashboardError" role="alert" class="rounded-xl bg-red-50 p-4 text-red-700 dark:bg-red-950 dark:text-red-200">{{ dashboardError }} <button type="button" class="ml-3 underline" @click="fetchData">Retry</button></div>
        <!-- Greeting -->
        <section>
            <h1 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                {{ greeting }}, {{ firstName }}
                <span class="inline-block">👋</span>
            </h1>

            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Here is what is happening with {{ currentBusiness.name || 'your business' }} today.
            </p>
        </section>

        <!-- Status notice -->
        <section
            v-if="verificationStatus !== 'approved'"
            class="flex flex-col justify-between gap-4 rounded-2xl border p-4 sm:flex-row sm:items-center"
            :class="verificationStatus === 'rejected'
                ? 'border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-950/30'
                : 'border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/30'"
        >
            <div>
                <p
                    class="font-bold"
                    :class="verificationStatus === 'rejected'
                        ? 'text-red-800 dark:text-red-300'
                        : 'text-amber-800 dark:text-amber-300'"
                >
                    {{
                        verificationStatus === 'under_review'
                            ? 'Your activation application is under review'
                            : verificationStatus === 'rejected'
                                ? 'Your activation application needs attention'
                                : 'Activate your business'
                    }}
                </p>

                <p
                    class="mt-1 text-sm"
                    :class="verificationStatus === 'rejected'
                        ? 'text-red-700 dark:text-red-400'
                        : 'text-amber-700 dark:text-amber-400'"
                >
                    {{
                        verificationStatus === 'under_review'
                            ? 'The administrator is reviewing the information you submitted.'
                            : 'Complete your business verification to unlock live payment features.'
                    }}
                </p>
            </div>

            <router-link
                v-if="verificationStatus !== 'under_review'"
                to="/user/activate"
                class="shrink-0 rounded-xl bg-indigo-600 px-5 py-3 text-center text-sm font-bold text-white hover:bg-indigo-700"
            >
                Complete verification
            </router-link>
        </section>

        <!-- Balance and quick actions -->
        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(340px,1fr)]">
            <!-- Horizontal balance card -->
            <div
                class="relative overflow-hidden rounded-3xl border border-indigo-200 bg-gradient-to-br from-white via-indigo-50 to-violet-100 p-6 shadow-sm dark:border-indigo-900/60 dark:from-slate-900 dark:via-indigo-950/40 dark:to-violet-950/40"
            >
                <div
                    class="pointer-events-none absolute -bottom-24 -right-20 h-72 w-72 rounded-full border-[45px] border-indigo-500/5"
                ></div>

                <div class="relative z-10">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <div class="flex items-center gap-3">
                                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">
                                    Available Balance
                                </p>

                                <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                    🇳🇬 NGN
                                </span>
                            </div>

                            <p class="mt-3 text-4xl font-black tracking-tight text-slate-950 dark:text-white sm:text-5xl">
                                ₦{{ formatCurrency(balance) }}
                            </p>

                            <div class="mt-5 space-y-2">
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="text-indigo-600">◷</span>

                                    <span class="text-slate-500 dark:text-slate-400">
                                        Pending Settlement:
                                    </span>

                                    <strong class="text-slate-900 dark:text-white">
                                        ₦{{ formatCurrency(pendingSettlement) }}
                                    </strong>
                                </div>

                                <div class="flex items-center gap-2 text-sm">
                                    <span class="text-indigo-600">✓</span>

                                    <span class="text-slate-500 dark:text-slate-400">
                                        Last Payout:
                                    </span>

                                    <strong class="text-slate-900 dark:text-white">
                                        {{ lastPayout }}
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-4 lg:items-end">
                            <div class="text-left lg:text-right">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                    Settlement account
                                </p>

                                <p class="mt-1 font-semibold text-slate-700 dark:text-slate-300">
                                    {{ settlementAccount }}
                                </p>
                            </div>

                            <router-link
                                to="/user/payouts"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700"
                            >
                                <span>▣</span>
                                Withdraw
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick actions -->
            <div class="grid grid-cols-2 gap-4">
                <button
                    v-for="action in quickActions"
                    :key="action.label"
                    type="button"
                    class="group flex min-h-[135px] flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white p-4 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-700"
                    @click="runQuickAction(action)"
                >
                    <span
                        class="flex h-12 w-12 items-center justify-center rounded-2xl text-xl transition group-hover:scale-105"
                        :class="action.iconClass"
                    >
                        {{ action.icon }}
                    </span>

                    <span class="mt-3 text-sm font-bold text-slate-900 dark:text-white">
                        {{ action.label }}
                    </span>
                </button>
            </div>
        </section>

        <!-- Overview and filters -->
        <section
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6"
        >
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-950 dark:text-white">
                        Overview
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Performance summary for the selected period.
                    </p>
                </div>

                <div class="flex max-w-full overflow-x-auto rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
                    <button
                        v-for="tab in filterTabs"
                        :key="tab.key"
                        type="button"
                        class="whitespace-nowrap rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wide transition"
                        :class="selectedFilter === tab.key
                            ? 'bg-white text-indigo-700 shadow-sm dark:bg-slate-950 dark:text-indigo-300'
                            : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                        @click="selectedFilter = tab.key"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <!-- Statistics -->
            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="stat in statistics"
                    :key="stat.label"
                    class="flex min-h-[145px] flex-col justify-between rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-indigo-200 hover:bg-white hover:shadow-md dark:border-slate-800 dark:bg-slate-950/50 dark:hover:border-indigo-900 dark:hover:bg-slate-950"
                >
                    <div class="flex items-start justify-between">
                        <span
                            class="flex h-11 w-11 items-center justify-center rounded-2xl text-xl"
                            :class="stat.iconClass"
                        >
                            {{ stat.icon }}
                        </span>

                        <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:bg-slate-900">
                            {{ selectedFilterLabel }}
                        </span>
                    </div>

                    <div class="mt-5">
                        <p class="text-sm font-semibold text-slate-500">
                            {{ stat.label }}
                        </p>

                        <p class="mt-1 text-2xl font-black text-slate-950 dark:text-white">
                            {{ stat.value }}
                        </p>
                    </div>
                </article>
            </div>
        </section>

        <!-- Activity -->
        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,1fr)]">
            <!-- Performance -->
            <div
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-black">
                            Collections overview
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Collections and payout activity.
                        </p>
                    </div>

                    <router-link
                        to="/user/analytics"
                        class="text-sm font-bold text-indigo-600 hover:underline dark:text-indigo-400"
                    >
                        View analytics
                    </router-link>
                </div>

                <div
                    class="mt-6 flex min-h-[230px] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 dark:border-slate-700 dark:bg-slate-950/50"
                >
                    <div class="text-center">
                        <span
                            class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-100 text-2xl dark:bg-indigo-950"
                        >
                            ◩
                        </span>

                        <p class="mt-4 font-bold">
                            {{ transactions.length ? 'Transaction data available' : 'No activity yet' }}
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Your collection performance will appear here.
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/50">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                            Collections
                        </p>

                        <p class="mt-2 text-lg font-black">
                            ₦{{ formatCurrency(totalCollections) }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/50">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                            Payouts
                        </p>

                        <p class="mt-2 text-lg font-black">
                            ₦{{ formatCurrency(totalPayouts) }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/50">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                            Net balance
                        </p>

                        <p class="mt-2 text-lg font-black">
                            ₦{{ formatCurrency(balance) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Recent activity -->
            <div
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-black">
                        Recent activity
                    </h2>

                    <router-link
                        to="/user/transactions"
                        class="text-sm font-bold text-indigo-600 hover:underline dark:text-indigo-400"
                    >
                        View all
                    </router-link>
                </div>

                <div
                    v-if="loading"
                    class="py-12 text-center text-sm text-slate-500"
                >
                    Loading activity…
                </div>

                <div
                    v-else-if="!transactions.length"
                    class="py-12 text-center"
                >
                    <span
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-xl dark:bg-slate-800"
                    >
                        ↻
                    </span>

                    <p class="mt-4 font-bold">
                        No transactions yet
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        New transactions will appear here.
                    </p>
                </div>

                <div
                    v-else
                    class="mt-5 space-y-4"
                >
                    <div
                        v-for="transaction in transactions.slice(0, 5)"
                        :key="transaction.id || transaction.reference"
                        class="flex items-center justify-between gap-4 border-b border-slate-100 pb-4 last:border-0 dark:border-slate-800"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                :class="transaction.type === 'credit'
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                    : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'"
                            >
                                {{ transaction.type === 'credit' ? '↓' : '↑' }}
                            </span>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold">
                                    {{
                                        transaction.description ||
                                        transaction.reference ||
                                        'Transaction'
                                    }}
                                </p>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ formatDate(transaction.created_at) }}
                                </p>
                            </div>
                        </div>

                        <p
                            class="shrink-0 text-sm font-black"
                            :class="transaction.type === 'credit'
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-red-600 dark:text-red-400'"
                        >
                            {{ transaction.type === 'credit' ? '+' : '-' }}₦{{
                                formatCurrency(transaction.amount)
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script>
import {
    computed,
    onMounted,
    watch,
    ref,
} from 'vue';

import {
    useRouter,
} from 'vue-router';

import axios from 'axios';

export default {
    name: 'Dashboard',

    setup() {
        const router = useRouter();

        const user = JSON.parse(localStorage.getItem('user') || '{}');

        const loading = ref(false);
        const balance = ref(0);
        const pendingSettlement = ref(0);
        const totalCustomers = ref(0);
        const totalAccounts = ref(0);
        const transactions = ref([]);
        const currentBusiness = ref({});
        const verificationStatus = ref('pending');
        const selectedFilter = ref('today');
        const dashboardSummary = ref({});
        const fetchSummary = async () => { const {data}=await axios.get('/api/v1/merchant/dashboard/summary',{params:{period:selectedFilter.value}}); dashboardSummary.value=data.data; totalCustomers.value=data.data.total_customers; totalAccounts.value=data.data.total_virtual_accounts; };

        const settlementAccount = ref('No account added');
        const lastPayout = ref('None');

        const filterTabs = [
            {
                key: 'today',
                label: 'Today',
            },
            {
                key: 'yesterday',
                label: 'Yesterday',
            },
            {
                key: '7days',
                label: 'Last 7 days',
            },
            {
                key: '30days',
                label: 'Last 30 days',
            },
            {
                key: 'all',
                label: 'All',
            },
        ];

        const quickActions = [
            {
                label: 'Send Money',
                icon: '➤',
                iconClass:
                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
                route: '/user/payouts',
            },
            {
                label: 'Request Money',
                icon: '＋',
                iconClass:
                    'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
                route: '/user/accounts',
            },
            {
                label: 'Send Invoice',
                icon: '▤',
                iconClass:
                    'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300',
                route: '/user/transactions',
            },
            {
                label: 'Payment Link',
                icon: '🔗',
                iconClass:
                    'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-950 dark:text-fuchsia-300',
                route: '/user/documentation',
            },
        ];

        const firstName = computed(() =>
            (user.name || 'Merchant').split(' ')[0]
        );

        const greeting = computed(() => {
            const hour = new Date().getHours();

            if (hour < 12) {
                return 'Good morning';
            }

            if (hour < 17) {
                return 'Good afternoon';
            }

            return 'Good evening';
        });

        const selectedFilterLabel = computed(() => {
            return (
                filterTabs.find(
                    (tab) => tab.key === selectedFilter.value
                )?.label || 'Today'
            );
        });

        const formatCurrency = (amount) => {
            return new Intl.NumberFormat('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(amount || 0));
        };

        const formatDate = (date) => {
            if (!date) {
                return 'N/A';
            }

            return new Date(date).toLocaleString('en-NG', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        };

        const filterTransaction = (transaction) => {
            if (
                selectedFilter.value === 'all' ||
                !transaction.created_at
            ) {
                return selectedFilter.value === 'all';
            }

            const date = new Date(transaction.created_at);
            const now = new Date();

            const today = new Date(
                now.getFullYear(),
                now.getMonth(),
                now.getDate()
            );

            const transactionDay = new Date(
                date.getFullYear(),
                date.getMonth(),
                date.getDate()
            );

            if (selectedFilter.value === 'today') {
                return transactionDay.getTime() === today.getTime();
            }

            if (selectedFilter.value === 'yesterday') {
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);

                return transactionDay.getTime() === yesterday.getTime();
            }

            const days =
                selectedFilter.value === '7days'
                    ? 7
                    : 30;

            const start = new Date(today);
            start.setDate(start.getDate() - (days - 1));

            return date >= start;
        };

        const filteredTransactions = computed(() =>
            transactions.value.filter(filterTransaction)
        );

        const todayRevenue = computed(() => dashboardSummary.value.today_net_collections || '0.00');
        const totalCollections = computed(() => dashboardSummary.value.collections || '0.00');
        const totalPayouts = computed(() => dashboardSummary.value.payouts || '0.00');

        const statistics = computed(() => [
            { label:'Success Rate', value:(dashboardSummary.value.success_rate || 0)+'%', icon:'%',iconClass:'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' },
            { label:'API Access', value:dashboardSummary.value.api_status || 'disabled',icon:'API',iconClass:'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' },
            {
                label: 'Total Customers',
                value: totalCustomers.value,
                icon: '♟',
                iconClass:
                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
            },
            {
                label: 'Virtual Accounts',
                value: totalAccounts.value,
                icon: '▰',
                iconClass:
                    'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
            },
            {
                label: 'Transactions',
                value: dashboardSummary.value.total_transactions || 0,
                icon: '↻',
                iconClass:
                    'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300',
            },
            {
                label: 'Today Net Collections',
                value: `₦${formatCurrency(todayRevenue.value)}`,
                icon: '₦',
                iconClass:
                    'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-950 dark:text-fuchsia-300',
            },
        ]);

        const runQuickAction = (action) => {
            router.push(action.route);
        };

        const fetchProfile = async () => {
            const response = await axios.get(
                '/api/v1/merchant/profile'
            );

            const data = response.data?.data || {};

            currentBusiness.value =
                data.current_business || {};

            verificationStatus.value =
                currentBusiness.value.verification_status ||
                'pending';

            const wallet = data.wallet || {};
            balance.value = Number(wallet.available_balance || 0);
            pendingSettlement.value = Number(wallet.reserved_balance || 0);
        };

        const fetchWallet = async () => {
            const response = await axios.get(
                '/api/v1/wallet/balance'
            );

            const wallet = response.data?.data || {};

            balance.value = Number(
                wallet.available_balance ||
                wallet.balance ||
                0
            );

            pendingSettlement.value = Number(
                wallet.pending_settlement ||
                wallet.reserved_balance ||
                0
            );
        };

        const fetchCustomers = async () => {
            const response = await axios.get(
                '/api/v1/customers'
            );

            const data = response.data?.data;

            totalCustomers.value = Number(
                data?.total ||
                data?.length ||
                0
            );
        };

        const fetchAccounts = async () => {
            const response = await axios.get(
                '/api/v1/virtual-accounts'
            );

            const data = response.data?.data;
            const accounts = data?.data || data || [];

            totalAccounts.value = Array.isArray(accounts)
                ? accounts.length
                : Number(data?.total || 0);

            const firstAccount = Array.isArray(accounts)
                ? accounts[0]
                : null;

            if (firstAccount) {
                settlementAccount.value =
                    firstAccount.account_number ||
                    firstAccount.account_name ||
                    'Account available';
            }
        };

        const fetchTransactions = async () => {
            const response = await axios.get(
                '/api/v1/wallet/transactions?limit=100'
            );

            const data = response.data?.data;
            const items = data?.data || data || [];

            transactions.value = Array.isArray(items)
                ? items
                : [];

            const payout = transactions.value.find(
                (transaction) =>
                    transaction.type === 'debit'
            );

            if (payout?.created_at) {
                lastPayout.value = formatDate(payout.created_at);
            }
        };

        const dashboardError = ref('');
        const fetchData = async () => {
            dashboardError.value = '';
            totalCustomers.value = 0;
            loading.value = true;

            try {
                await fetchProfile();

                await Promise.all([
                    fetchWallet(),
                    verificationStatus.value === 'approved' ? fetchCustomers() : Promise.resolve(),
                    fetchAccounts(),
                    fetchTransactions(),
                    fetchSummary(),
                ]);
            } catch (error) {
                dashboardError.value = error.response?.data?.message || 'Dashboard could not load. Please retry.';
            } finally {
                loading.value = false;
            }
        };

        onMounted(fetchData);
        watch(selectedFilter,async()=>{try{await fetchSummary();}catch(e){dashboardError.value=e.response?.data?.message||'Summary could not load.';}});

        return {
            loading,
            dashboardError,
            fetchData,
            currentBusiness,
            verificationStatus,
            balance,
            pendingSettlement,
            settlementAccount,
            lastPayout,
            totalCustomers,
            totalAccounts,
            transactions,
            selectedFilter,
            filterTabs,
            quickActions,
            firstName,
            greeting,
            selectedFilterLabel,
            todayRevenue,
            totalCollections,
            totalPayouts,
            statistics,
            formatCurrency,
            formatDate,
            runQuickAction,
        };
    },
};
</script>