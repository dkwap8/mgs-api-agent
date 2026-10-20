<template>
    <div class="space-y-6">
        <section class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400">
                    Settlement operations
                </p>
                <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl">
                    Payout Management
                </h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                    Review manual withdrawals, inspect fees and bank details, approve eligible payouts, and return reserved funds safely when rejecting requests.
                </p>
            </div>

            <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
                :disabled="loading"
                @click="refreshPage"
            >
                {{ loading ? 'Refreshing…' : '↻ Refresh' }}
            </button>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="item in summaryCards"
                :key="item.label"
                class="flex min-h-[125px] items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl text-xl" :class="item.iconClass">
                    {{ item.icon }}
                </span>
                <div>
                    <p class="text-2xl font-black">{{ item.value }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-500">{{ item.label }}</p>
                </div>
            </article>
        </section>

        <section
            v-if="stats.pending_review > 0"
            class="flex flex-col justify-between gap-4 rounded-3xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900/50 dark:bg-amber-950/30 sm:flex-row sm:items-center"
        >
            <div>
                <h2 class="font-black text-amber-900 dark:text-amber-200">
                    {{ stats.pending_review }} payout{{ stats.pending_review === 1 ? '' : 's' }} awaiting manual review
                </h2>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                    Review the bank details, withdrawal rule, charge and reserved amount before approving or rejecting.
                </p>
            </div>

            <button
                type="button"
                class="rounded-xl bg-amber-600 px-5 py-3 text-sm font-black text-white hover:bg-amber-700"
                @click="showPendingReview"
            >
                Review Pending
            </button>
        </section>

        <section
            v-if="successMessage"
            class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300"
        >
            {{ successMessage }}
        </section>

        <section
            v-if="errorMessage"
            class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
        >
            {{ errorMessage }}
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <form class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_180px_180px_180px_160px_auto]" @submit.prevent="applyFilters">
                <input
                    v-model.trim="filters.search"
                    type="search"
                    class="admin-payout-input"
                    placeholder="Search reference, merchant, business or bank..."
                />

                <select v-model="filters.status" class="admin-payout-input">
                    <option value="">All statuses</option>
                    <option value="pending">Pending Review</option>
                    <option value="processing">Processing</option>
                    <option value="approved">Approved</option>
                    <option value="successful">Successful</option>
                    <option value="failed">Failed</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>

                <select v-model="filters.processing_mode" class="admin-payout-input">
                    <option value="">All modes</option>
                    <option value="automatic">Automatic</option>
                    <option value="manual">Manual</option>
                </select>

                <select v-model="filters.merchant_type" class="admin-payout-input">
                    <option value="">All merchant types</option>
                    <option value="starter">Starter Business</option>
                    <option value="registered">Registered Business</option>
                </select>

                <select v-model="filters.period" class="admin-payout-input">
                    <option value="">All periods</option>
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>

                <div class="flex gap-2">
                    <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white hover:bg-indigo-700">
                        Apply
                    </button>
                    <button type="button" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black dark:border-slate-700" @click="resetFilters">
                        Clear
                    </button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div v-if="loading" class="py-20 text-center text-sm text-slate-500">
                Loading payouts…
            </div>

            <div v-else-if="payouts.length === 0" class="py-20 text-center">
                <h3 class="text-lg font-black">No payouts found</h3>
                <p class="mt-2 text-sm text-slate-500">No payout matches the selected filters.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
                        <tr class="text-left text-[11px] font-black uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-4">Payout</th>
                            <th class="px-5 py-4">Merchant / Business</th>
                            <th class="px-5 py-4">Amount</th>
                            <th class="px-5 py-4">Bank Account</th>
                            <th class="px-5 py-4">Mode</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4">Date</th>
                            <th class="px-5 py-4 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="payout in payouts" :key="payout.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/30">
                            <td class="px-5 py-4">
                                <p class="font-mono text-sm font-black">{{ payout.reference }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ merchantTypeLabel(payout) }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="text-sm font-black">{{ merchantName(payout) }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ businessName(payout) }}</p>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="text-sm font-black">₦{{ formatCurrency(payout.amount) }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Fee ₦{{ formatCurrency(payout.fee) }} · Net ₦{{ formatCurrency(payout.net_amount ?? payout.amount) }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="text-sm font-bold">{{ payout.bank_account?.bank_name || 'N/A' }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ payout.bank_account?.account_name || 'N/A' }} · {{ maskAccount(payout.bank_account?.account_number) }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-black" :class="modeClass(payout.processing_mode)">
                                    {{ formatText(payout.processing_mode || 'manual') }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-black" :class="statusClass(payout.status)">
                                    {{ statusLabel(payout.status) }}
                                </span>
                                <p v-if="payout.refunded_at" class="mt-2 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                    Funds returned
                                </p>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">
                                {{ formatDate(payout.created_at) }}
                            </td>

                            <td class="px-5 py-4 text-right">
                                <button
                                    type="button"
                                    class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-black text-indigo-700 hover:bg-indigo-100 dark:border-indigo-900 dark:bg-indigo-950 dark:text-indigo-300"
                                    @click="openPayout(payout)"
                                >
                                    Review
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="pagination.last_page > 1" class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 px-5 py-4 dark:border-slate-800 sm:flex-row">
                <p class="text-sm text-slate-500">
                    Showing {{ pagination.from || 0 }}–{{ pagination.to || 0 }} of {{ pagination.total || 0 }}
                </p>

                <div class="flex items-center gap-2">
                    <button class="rounded-xl border px-4 py-2 text-sm font-bold disabled:opacity-40 dark:border-slate-700" :disabled="pagination.current_page <= 1" @click="fetchPayouts(pagination.current_page - 1)">
                        Previous
                    </button>
                    <span class="px-3 text-sm font-black">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
                    <button class="rounded-xl border px-4 py-2 text-sm font-bold disabled:opacity-40 dark:border-slate-700" :disabled="pagination.current_page >= pagination.last_page" @click="fetchPayouts(pagination.current_page + 1)">
                        Next
                    </button>
                </div>
            </div>
        </section>

        <Teleport to="body">
            <div
                v-if="modalOpen"
                class="fixed inset-0 z-[230] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-5"
                @click.self="closeModal"
            >
                <div class="flex max-h-[95vh] w-full max-w-5xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 shadow-2xl dark:border-slate-700 dark:bg-slate-950">
                    <header class="flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-5 dark:border-slate-800 dark:bg-slate-900 sm:px-7">
                        <div>
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full px-3 py-1 text-xs font-black" :class="statusClass(selectedPayout.status)">
                                    {{ statusLabel(selectedPayout.status) }}
                                </span>
                                <span class="rounded-full px-3 py-1 text-xs font-black" :class="modeClass(selectedPayout.processing_mode)">
                                    {{ formatText(selectedPayout.processing_mode || 'manual') }}
                                </span>
                            </div>

                            <p class="mt-3 font-mono text-sm font-black text-indigo-600 dark:text-indigo-400">
                                {{ selectedPayout.reference }}
                            </p>
                            <h3 class="mt-1 text-2xl font-black">Payout Review</h3>
                        </div>

                        <button type="button" class="flex h-11 w-11 items-center justify-center rounded-2xl text-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" :disabled="processing" @click="closeModal">
                            ✕
                        </button>
                    </header>

                    <div class="flex-1 overflow-y-auto p-4 sm:p-7">
                        <div v-if="detailsLoading" class="py-24 text-center text-sm text-slate-500">
                            Loading payout details…
                        </div>

                        <div v-else class="space-y-6">
                            <section class="grid gap-5 lg:grid-cols-2">
                                <article class="detail-card">
                                    <h4 class="detail-title">Merchant & Business</h4>
                                    <dl class="mt-5 space-y-4">
                                        <DetailRow label="Merchant" :value="merchantName(selectedPayout)" />
                                        <DetailRow label="Business" :value="businessName(selectedPayout)" />
                                        <DetailRow label="Merchant Type" :value="merchantTypeLabel(selectedPayout)" />
                                        <DetailRow label="Email" :value="selectedPayout.merchant?.user?.email || selectedPayout.merchant?.business_email" />
                                    </dl>
                                </article>

                                <article class="detail-card">
                                    <h4 class="detail-title">Bank Account</h4>
                                    <dl class="mt-5 space-y-4">
                                        <DetailRow label="Bank" :value="selectedPayout.bank_account?.bank_name" />
                                        <DetailRow label="Account Name" :value="selectedPayout.bank_account?.account_name" />
                                        <DetailRow label="Account Number" :value="selectedPayout.bank_account?.account_number" />
                                        <DetailRow label="Verified" :value="selectedPayout.bank_account?.is_verified ? 'Yes' : 'No'" />
                                    </dl>
                                </article>
                            </section>

                            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                <MetricBox label="Requested Amount" :value="`₦${formatCurrency(selectedPayout.amount)}`" />
                                <MetricBox label="Withdrawal Fee" :value="`₦${formatCurrency(selectedPayout.fee)}`" />
                                <MetricBox label="Merchant Receives" :value="`₦${formatCurrency(selectedPayout.net_amount ?? selectedPayout.amount)}`" />
                                <MetricBox label="Wallet Reserved" :value="`₦${formatCurrency(walletDebit(selectedPayout))}`" />
                            </section>

                            <section class="grid gap-5 lg:grid-cols-2">
                                <article class="detail-card">
                                    <h4 class="detail-title">Applied Withdrawal Rule</h4>
                                    <dl class="mt-5 space-y-4">
                                        <DetailRow label="Automatic Limit" :value="moneyOrNone(selectedPayout.withdrawal_setting?.automatic_withdrawal_limit)" />
                                        <DetailRow label="Daily Limit" :value="moneyOrNone(selectedPayout.withdrawal_setting?.daily_withdrawal_limit)" />
                                        <DetailRow label="Charge Type" :value="formatText(selectedPayout.withdrawal_setting?.charge_type || 'none')" />
                                        <DetailRow label="Charge Cap" :value="moneyOrNone(selectedPayout.withdrawal_setting?.maximum_charge)" />
                                    </dl>
                                </article>

                                <article class="detail-card">
                                    <h4 class="detail-title">Processing Information</h4>
                                    <dl class="mt-5 space-y-4">
                                        <DetailRow label="Processing Mode" :value="formatText(selectedPayout.processing_mode || 'manual')" />
                                        <DetailRow label="Risk Level" :value="formatText(selectedPayout.risk_level || 'N/A')" />
                                        <DetailRow label="Requested At" :value="formatDate(selectedPayout.created_at)" />
                                        <DetailRow label="Approved At" :value="formatDate(selectedPayout.approved_at)" />
                                        <DetailRow label="Refunded At" :value="formatDate(selectedPayout.refunded_at)" />
                                    </dl>
                                </article>
                            </section>

                            <section v-if="selectedPayout.review_reason || selectedPayout.failure_reason" class="detail-card">
                                <h4 class="detail-title">Review Notes</h4>
                                <p v-if="selectedPayout.review_reason" class="mt-4 whitespace-pre-wrap rounded-2xl bg-amber-50 p-4 text-sm leading-6 text-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
                                    {{ selectedPayout.review_reason }}
                                </p>
                                <p v-if="selectedPayout.failure_reason" class="mt-4 whitespace-pre-wrap rounded-2xl bg-red-50 p-4 text-sm leading-6 text-red-700 dark:bg-red-950/30 dark:text-red-300">
                                    {{ selectedPayout.failure_reason }}
                                </p>
                            </section>

                            <section v-if="canReject(selectedPayout)" class="detail-card">
                                <h4 class="detail-title">Reject Payout</h4>
                                <p class="mt-1 text-sm text-slate-500">
                                    Rejection returns the complete reserved wallet amount exactly once.
                                </p>

                                <textarea
                                    v-model.trim="rejectionReason"
                                    rows="4"
                                    maxlength="2000"
                                    class="admin-payout-input mt-5 resize-none"
                                    placeholder="Enter a clear rejection reason..."
                                ></textarea>

                                <p v-if="actionError" class="mt-2 text-sm font-semibold text-red-600">
                                    {{ actionError }}
                                </p>
                            </section>
                        </div>
                    </div>

                    <footer class="border-t border-slate-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900 sm:px-7">
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <button type="button" class="rounded-xl px-5 py-3 text-sm font-black text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" :disabled="processing" @click="closeModal">
                                Close
                            </button>

                            <div class="flex flex-col gap-3 sm:flex-row">
                                <button
                                    v-if="canReject(selectedPayout)"
                                    type="button"
                                    class="rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-black text-red-700 hover:bg-red-100 disabled:opacity-50 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
                                    :disabled="processing"
                                    @click="rejectPayout"
                                >
                                    {{ processingAction === 'reject' ? 'Rejecting & Refunding…' : 'Reject & Return Funds' }}
                                </button>

                                <button
                                    v-if="canApprove(selectedPayout)"
                                    type="button"
                                    class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-black text-white hover:bg-indigo-700 disabled:opacity-50"
                                    :disabled="processing"
                                    @click="approvePayout"
                                >
                                    {{ processingAction === 'approve' ? 'Approving & Processing…' : 'Approve & Process' }}
                                </button>
                            </div>
                        </div>
                    </footer>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script>
import {
    computed,
    defineComponent,
    h,
    onBeforeUnmount,
    onMounted,
    ref,
} from 'vue';
import axios from 'axios';
import { verificationModal } from '../../services/verificationModal';

const DetailRow = defineComponent({
    props: {
        label: String,
        value: [String, Number],
    },
    setup(props) {
        return () => h('div', {
            class: 'flex items-start justify-between gap-4 border-b border-slate-100 pb-3 last:border-0 dark:border-slate-800',
        }, [
            h('dt', { class: 'text-sm text-slate-500' }, props.label),
            h('dd', { class: 'max-w-[65%] break-words text-right text-sm font-bold' }, props.value || 'N/A'),
        ]);
    },
});

const MetricBox = defineComponent({
    props: {
        label: String,
        value: [String, Number],
    },
    setup(props) {
        return () => h('div', {
            class: 'rounded-2xl bg-slate-50 p-4 dark:bg-slate-950',
        }, [
            h('p', { class: 'text-[11px] font-black uppercase tracking-wider text-slate-400' }, props.label),
            h('p', { class: 'mt-2 text-lg font-black' }, props.value),
        ]);
    },
});

export default {
    name: 'AdminPayouts',
    components: {
        DetailRow,
        MetricBox,
    },

    setup() {
        const loading = ref(false);
        const detailsLoading = ref(false);
        const processing = ref(false);
        const processingAction = ref('');

        const payouts = ref([]);
        const selectedPayout = ref({});
        const modalOpen = ref(false);

        const stats = ref({
            total: 0,
            pending_review: 0,
            processing: 0,
            successful: 0,
            failed: 0,
            automatic: 0,
            manual: 0,
            total_amount: 0,
            total_fees: 0,
            pending_amount: 0,
            successful_amount: 0,
            today_amount: 0,
            refunded_amount: 0,
        });

        const filters = ref({
            search: '',
            status: '',
            processing_mode: '',
            merchant_type: '',
            period: '',
        });

        const pagination = ref({
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0,
        });

        const rejectionReason = ref('');
        const actionError = ref('');
        const successMessage = ref('');
        const errorMessage = ref('');

        const summaryCards = computed(() => [
            {
                label: 'Pending Review',
                value: stats.value.pending_review,
                icon: '◷',
                iconClass: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
            },
            {
                label: 'Processing',
                value: stats.value.processing,
                icon: '↻',
                iconClass: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
            },
            {
                label: 'Successful Amount',
                value: `₦${compactCurrency(stats.value.successful_amount)}`,
                icon: '✓',
                iconClass: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            },
            {
                label: 'Returned Funds',
                value: `₦${compactCurrency(stats.value.refunded_amount)}`,
                icon: '↩',
                iconClass: 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
            },
        ]);

        const buildParams = (page = 1) => {
            const params = {
                page,
                per_page: 20,
            };

            Object.entries(filters.value).forEach(([key, value]) => {
                if (String(value || '').trim()) {
                    params[key] = String(value).trim();
                }
            });

            return params;
        };

        const fetchPayouts = async (page = 1) => {
            loading.value = true;
            errorMessage.value = '';

            try {
                const response = await axios.get('/api/v1/admin/payouts', {
                    params: buildParams(page),
                });

                const paginator = response.data?.data || {};

                payouts.value = Array.isArray(paginator.data)
                    ? paginator.data
                    : [];

                pagination.value = {
                    current_page: Number(paginator.current_page || 1),
                    last_page: Number(paginator.last_page || 1),
                    from: Number(paginator.from || 0),
                    to: Number(paginator.to || 0),
                    total: Number(paginator.total || 0),
                };
            } catch (error) {
                payouts.value = [];
                errorMessage.value =
                    error.response?.data?.message ||
                    'The payouts could not be loaded.';
            } finally {
                loading.value = false;
            }
        };

        const fetchStats = async () => {
            try {
                const response = await axios.get('/api/v1/admin/payouts/stats');
                stats.value = {
                    ...stats.value,
                    ...(response.data?.data || {}),
                };
            } catch (error) {
                console.error('Payout stats failed:', error.response?.data || error);
            }
        };

        const refreshPage = async () => {
            successMessage.value = '';
            await Promise.all([
                fetchPayouts(pagination.value.current_page),
                fetchStats(),
            ]);
        };

        const applyFilters = async () => {
            await fetchPayouts(1);
        };

        const resetFilters = async () => {
            filters.value = {
                search: '',
                status: '',
                processing_mode: '',
                merchant_type: '',
                period: '',
            };
            await fetchPayouts(1);
        };

        const showPendingReview = async () => {
            filters.value.status = 'pending';
            filters.value.processing_mode = 'manual';
            await fetchPayouts(1);
        };

        const openPayout = async (payout) => {
            selectedPayout.value = payout;
            modalOpen.value = true;
            detailsLoading.value = true;
            rejectionReason.value = '';
            actionError.value = '';
            document.body.style.overflow = 'hidden';

            try {
                const response = await axios.get(`/api/v1/admin/payouts/${payout.id}`);
                selectedPayout.value = response.data?.data || payout;
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The payout details could not be loaded.';
            } finally {
                detailsLoading.value = false;
            }
        };

        const closeModal = () => {
            if (processing.value) return;

            modalOpen.value = false;
            selectedPayout.value = {};
            rejectionReason.value = '';
            actionError.value = '';
            document.body.style.overflow = '';
        };

        const approvePayout = async () => {
            const confirmed = await verificationModal.confirm({
                title: 'Approve payout?',
                message: 'Approve this payout and send it for immediate provider processing?',
                confirmText: 'Approve payout',
            });
            if (!confirmed) return;

            processing.value = true;
            processingAction.value = 'approve';
            actionError.value = '';
            successMessage.value = '';

            try {
                const response = await axios.post(
                    `/api/v1/admin/payouts/${selectedPayout.value.id}/approve`
                );

                selectedPayout.value =
                    response.data?.data || selectedPayout.value;

                successMessage.value =
                    response.data?.message ||
                    'Payout approved successfully.';

                await Promise.all([
                    fetchPayouts(pagination.value.current_page),
                    fetchStats(),
                ]);

                closeModal();
            } catch (error) {
                actionError.value =
                    error.response?.data?.message ||
                    'The payout could not be approved.';
            } finally {
                processing.value = false;
                processingAction.value = '';
            }
        };

        const rejectPayout = async () => {
            if (rejectionReason.value.length < 5) {
                actionError.value =
                    'Enter a rejection reason containing at least 5 characters.';
                return;
            }

            const confirmed = await verificationModal.confirm({
                title: 'Reject payout?',
                message: 'Reject this payout and return the complete reserved wallet amount?',
                confirmText: 'Reject payout',
                danger: true,
            });
            if (!confirmed) return;

            processing.value = true;
            processingAction.value = 'reject';
            actionError.value = '';
            successMessage.value = '';

            try {
                const response = await axios.post(
                    `/api/v1/admin/payouts/${selectedPayout.value.id}/reject`,
                    {
                        reason: rejectionReason.value,
                    }
                );

                selectedPayout.value =
                    response.data?.data || selectedPayout.value;

                successMessage.value =
                    response.data?.message ||
                    'Payout rejected and reserved funds returned.';

                await Promise.all([
                    fetchPayouts(pagination.value.current_page),
                    fetchStats(),
                ]);

                closeModal();
            } catch (error) {
                actionError.value =
                    error.response?.data?.errors?.reason?.[0] ||
                    error.response?.data?.message ||
                    'The payout could not be rejected.';
            } finally {
                processing.value = false;
                processingAction.value = '';
            }
        };

        const canApprove = (payout) => {
            return payout?.status === 'pending' &&
                payout?.processing_mode === 'manual' &&
                !payout?.refunded_at;
        };

        const canReject = (payout) => {
            return ['pending', 'processing'].includes(payout?.status) &&
                !payout?.refunded_at;
        };

        const walletDebit = (payout) => {
            return Number(
                payout?.metadata?.wallet_debit ??
                (Number(payout?.amount || 0) + Number(payout?.fee || 0))
            );
        };

        const merchantName = (payout) => {
            return payout?.merchant?.user?.name ||
                payout?.merchant?.business_name ||
                'Unknown Merchant';
        };

        const businessName = (payout) => {
            return payout?.business?.name ||
                payout?.business?.business_name ||
                payout?.merchant?.business_name ||
                'N/A';
        };

        const merchantTypeLabel = (payout) => {
            return formatText(
                payout?.withdrawal_setting?.merchant_type ||
                payout?.metadata?.merchant_type ||
                'unknown'
            );
        };

        const formatCurrency = (value) => {
            return new Intl.NumberFormat('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(value || 0));
        };

        const compactCurrency = (value) => {
            return new Intl.NumberFormat('en-NG', {
                notation: 'compact',
                maximumFractionDigits: 1,
            }).format(Number(value || 0));
        };

        const formatDate = (value) => {
            if (!value) return 'N/A';

            return new Date(value).toLocaleString('en-NG', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        };

        const formatText = (value) => {
            return String(value || '')
                .replaceAll('_', ' ')
                .replaceAll('-', ' ')
                .replace(/\b\w/g, (letter) => letter.toUpperCase());
        };

        const statusLabel = (status) => {
            const value = String(status || '').toLowerCase();

            if (value === 'pending') return 'Pending Review';
            if (['success', 'successful'].includes(value)) return 'Successful';

            return formatText(value || 'pending');
        };

        const statusClass = (status) => {
            const value = String(status || '').toLowerCase();

            if (['success', 'successful', 'completed', 'approved'].includes(value)) {
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
            }

            if (['failed', 'rejected', 'cancelled'].includes(value)) {
                return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';
            }

            if (value === 'processing') {
                return 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300';
            }

            return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
        };

        const modeClass = (mode) => {
            return mode === 'automatic'
                ? 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300'
                : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
        };

        const maskAccount = (value) => {
            const account = String(value || '');

            if (!account) return 'N/A';
            if (account.length <= 4) return account;

            return `${'*'.repeat(account.length - 4)}${account.slice(-4)}`;
        };

        const moneyOrNone = (value) => {
            return value === null || value === undefined || value === ''
                ? 'No Limit'
                : `₦${formatCurrency(value)}`;
        };

        const handleKeydown = (event) => {
            if (
                event.key === 'Escape' &&
                modalOpen.value &&
                !processing.value
            ) {
                closeModal();
            }
        };

        onMounted(async () => {
            window.addEventListener('keydown', handleKeydown);
            await Promise.all([
                fetchPayouts(),
                fetchStats(),
            ]);
        });

        onBeforeUnmount(() => {
            window.removeEventListener('keydown', handleKeydown);
            document.body.style.overflow = '';
        });

        return {
            loading,
            detailsLoading,
            processing,
            processingAction,
            payouts,
            selectedPayout,
            modalOpen,
            stats,
            filters,
            pagination,
            rejectionReason,
            actionError,
            successMessage,
            errorMessage,
            summaryCards,
            refreshPage,
            fetchPayouts,
            applyFilters,
            resetFilters,
            showPendingReview,
            openPayout,
            closeModal,
            approvePayout,
            rejectPayout,
            canApprove,
            canReject,
            walletDebit,
            merchantName,
            businessName,
            merchantTypeLabel,
            formatCurrency,
            compactCurrency,
            formatDate,
            formatText,
            statusLabel,
            statusClass,
            modeClass,
            maskAccount,
            moneyOrNone,
        };
    },
};
</script>

<style scoped>
.admin-payout-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid rgb(226 232 240);
    background: white;
    padding: 0.75rem 1rem;
    color: rgb(15 23 42);
    outline: none;
    transition:
        border-color 150ms ease,
        box-shadow 150ms ease;
}

.admin-payout-input:focus {
    border-color: rgb(99 102 241);
    box-shadow: 0 0 0 4px rgb(99 102 241 / 0.1);
}

.detail-card {
    border: 1px solid rgb(226 232 240);
    border-radius: 1.5rem;
    background: white;
    padding: 1.25rem;
}

.detail-title {
    font-size: 1rem;
    font-weight: 900;
}

:global(.dark) .admin-payout-input {
    border-color: rgb(51 65 85);
    background: rgb(2 6 23);
    color: white;
}

:global(.dark) .detail-card {
    border-color: rgb(30 41 59);
    background: rgb(15 23 42);
}
</style>
