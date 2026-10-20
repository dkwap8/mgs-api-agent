<template>
    <div class="space-y-6">
        <section class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400">
                    Secure settlements
                </p>
                <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl">
                    Withdraw Funds
                </h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                    Preview your withdrawal fee, confirm with your withdrawal PIN, then verify the OTP sent to your email.
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

        <section v-if="successMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
            {{ successMessage }}
        </section>

        <section v-if="errorMessage" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
            {{ errorMessage }}
        </section>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(340px,.85fr)]">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Available Balance</p>
                        <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">
                            ₦{{ formatCurrency(balance.available_balance) }}
                        </p>
                    </div>
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-100 text-2xl dark:bg-indigo-950">
                        ₦
                    </span>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <label>
                        <span class="mb-2 block text-sm font-black">Withdrawal Amount</span>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-4 flex items-center font-black text-slate-400">₦</span>
                            <input
                                v-model="form.amount"
                                type="number"
                                min="0"
                                step="0.01"
                                class="payout-input pl-10"
                                placeholder="0.00"
                                @input="clearPreview"
                            />
                        </div>
                    </label>

                    <label>
                        <span class="mb-2 block text-sm font-black">Bank Account</span>
                        <select v-model="form.bank_account_id" class="payout-input" @change="clearPreview">
                            <option value="">Select bank account</option>
                            <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                                {{ account.bank_name }} — {{ maskAccount(account.account_number) }}
                            </option>
                        </select>
                    </label>

                    <label class="sm:col-span-2">
                        <span class="mb-2 block text-sm font-black">Narration <span class="font-normal text-slate-400">(optional)</span></span>
                        <input
                            v-model.trim="form.narration"
                            type="text"
                            maxlength="255"
                            class="payout-input"
                            placeholder="Withdrawal narration"
                        />
                    </label>
                </div>

                <button
                    type="button"
                    class="mt-5 w-full rounded-2xl bg-indigo-600 px-5 py-3.5 text-sm font-black text-white hover:bg-indigo-700 disabled:opacity-50"
                    :disabled="previewing || !canPreview"
                    @click="previewWithdrawal"
                >
                    {{ previewing ? 'Calculating…' : 'Preview Withdrawal' }}
                </button>
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                <h2 class="text-lg font-black">Withdrawal Summary</h2>
                <p class="mt-1 text-sm text-slate-500">Charges and processing mode appear before OTP confirmation.</p>

                <div v-if="preview" class="mt-6 space-y-4">
                    <SummaryRow label="Requested Amount" :value="`₦${formatCurrency(preview.amount)}`" />
                    <SummaryRow label="Withdrawal Fee" :value="`₦${formatCurrency(preview.fee)}`" />
                    <SummaryRow label="Wallet Debit" :value="`₦${formatCurrency(preview.wallet_debit)}`" />
                    <SummaryRow label="You Receive" :value="`₦${formatCurrency(preview.net_amount)}`" />

                    <div class="rounded-2xl p-4" :class="preview.automatic
                        ? 'bg-emerald-50 dark:bg-emerald-950/30'
                        : 'bg-amber-50 dark:bg-amber-950/30'">
                        <p class="text-xs font-black uppercase tracking-wider" :class="preview.automatic
                            ? 'text-emerald-700 dark:text-emerald-300'
                            : 'text-amber-700 dark:text-amber-300'">
                            Processing Mode
                        </p>
                        <p class="mt-1 font-black" :class="preview.automatic
                            ? 'text-emerald-900 dark:text-emerald-200'
                            : 'text-amber-900 dark:text-amber-200'">
                            {{ preview.automatic ? 'Automatic Processing' : 'Pending Admin Review' }}
                        </p>
                        <p v-if="preview.review_reason" class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                            {{ preview.review_reason }}
                        </p>
                    </div>

                    <p v-if="!preview.has_sufficient_balance" class="rounded-xl bg-red-50 p-3 text-sm font-semibold text-red-700 dark:bg-red-950/30 dark:text-red-300">
                        Your available balance is not enough for this withdrawal and its charge.
                    </p>

                    <button
                        type="button"
                        class="w-full rounded-2xl bg-indigo-600 px-5 py-3.5 text-sm font-black text-white hover:bg-indigo-700 disabled:opacity-50"
                        :disabled="!preview.has_sufficient_balance"
                        @click="openPinStep"
                    >
                        Continue Securely
                    </button>
                </div>

                <div v-else class="mt-8 rounded-2xl border border-dashed border-slate-300 px-5 py-12 text-center text-sm text-slate-500 dark:border-slate-700">
                    Enter an amount and select a bank account to see the withdrawal summary.
                </div>
            </article>
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-5 dark:border-slate-800">
                <div>
                    <h2 class="text-lg font-black">Withdrawal History</h2>
                    <p class="mt-1 text-sm text-slate-500">Withdrawals for your active business.</p>
                </div>
            </div>

            <div v-if="loading" class="py-16 text-center text-sm text-slate-500">Loading withdrawals…</div>
            <div v-else-if="payouts.length === 0" class="py-16 text-center text-sm text-slate-500">No withdrawals yet.</div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
                        <tr class="text-left text-[11px] font-black uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-4">Reference</th>
                            <th class="px-5 py-4">Bank</th>
                            <th class="px-5 py-4">Amount</th>
                            <th class="px-5 py-4">Fee</th>
                            <th class="px-5 py-4">Net</th>
                            <th class="px-5 py-4">Mode</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="payout in payouts" :key="payout.id">
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-sm font-black">{{ payout.reference }}</td>
                            <td class="px-5 py-4 text-sm">
                                <p class="font-bold">{{ payout.bank_account?.bank_name || 'N/A' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ maskAccount(payout.bank_account?.account_number) }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm font-black">₦{{ formatCurrency(payout.amount) }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm">₦{{ formatCurrency(payout.fee) }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm font-black">₦{{ formatCurrency(payout.net_amount ?? payout.amount) }}</td>
                            <td class="px-5 py-4 text-sm capitalize">{{ formatText(payout.processing_mode || 'manual') }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-black" :class="statusClass(payout.status)">
                                    {{ statusLabel(payout.status) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">{{ formatDate(payout.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="pagination.last_page > 1" class="flex items-center justify-between border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                <button class="rounded-xl border px-4 py-2 text-sm font-bold disabled:opacity-40 dark:border-slate-700" :disabled="pagination.current_page <= 1" @click="fetchPayouts(pagination.current_page - 1)">
                    Previous
                </button>
                <span class="text-sm font-black">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
                <button class="rounded-xl border px-4 py-2 text-sm font-bold disabled:opacity-40 dark:border-slate-700" :disabled="pagination.current_page >= pagination.last_page" @click="fetchPayouts(pagination.current_page + 1)">
                    Next
                </button>
            </div>
        </section>

        <Teleport to="body">
            <div v-if="securityModalOpen" class="fixed inset-0 z-[220] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm" @click.self="closeSecurityModal">
                <div class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-indigo-600">
                                {{ securityStep === 'pin' ? 'Step 1 of 2' : 'Step 2 of 2' }}
                            </p>
                            <h3 class="mt-1 text-2xl font-black">
                                {{ securityStep === 'pin' ? 'Confirm Withdrawal PIN' : 'Verify Withdrawal OTP' }}
                            </h3>
                        </div>
                        <button type="button" class="text-xl text-slate-500" :disabled="submitting" @click="closeSecurityModal">✕</button>
                    </div>

                    <div v-if="securityStep === 'pin'" class="mt-6">
                        <p class="text-sm leading-6 text-slate-500">
                            Enter your withdrawal PIN. A six-digit OTP will then be sent to your registered email.
                        </p>

                        <label class="mt-5 block">
                            <span class="mb-2 block text-sm font-black">Withdrawal PIN</span>
                            <input
                                v-model="pin"
                                type="password"
                                inputmode="numeric"
                                maxlength="6"
                                class="payout-input text-center text-xl tracking-[.35em]"
                                placeholder="••••"
                                @keyup.enter="sendOtp"
                            />
                        </label>

                        <p v-if="securityError" class="mt-3 text-sm font-semibold text-red-600">{{ securityError }}</p>

                        <button
                            type="button"
                            class="mt-5 w-full rounded-2xl bg-indigo-600 px-5 py-3.5 text-sm font-black text-white hover:bg-indigo-700 disabled:opacity-50"
                            :disabled="submitting || pin.length < 4"
                            @click="sendOtp"
                        >
                            {{ submitting ? 'Sending OTP…' : 'Send OTP' }}
                        </button>
                    </div>

                    <div v-else class="mt-6">
                        <p class="text-sm leading-6 text-slate-500">
                            Enter the six-digit code sent to <strong>{{ emailHint }}</strong>.
                        </p>

                        <input
                            v-model="otp"
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            class="payout-input mt-5 text-center text-2xl font-black tracking-[.5em]"
                            placeholder="000000"
                            @input="otp = otp.replace(/\D/g, '').slice(0, 6)"
                            @keyup.enter="verifyOtp"
                        />

                        <div class="mt-3 flex items-center justify-between text-sm">
                            <span class="text-slate-500">Expires in {{ countdownLabel }}</span>
                            <button type="button" class="font-black text-indigo-600 disabled:text-slate-400" :disabled="submitting || resendSeconds > 0" @click="resendOtp">
                                {{ resendSeconds > 0 ? `Resend in ${resendSeconds}s` : 'Resend OTP' }}
                            </button>
                        </div>

                        <p v-if="securityError" class="mt-3 text-sm font-semibold text-red-600">{{ securityError }}</p>

                        <button
                            type="button"
                            class="mt-5 w-full rounded-2xl bg-indigo-600 px-5 py-3.5 text-sm font-black text-white hover:bg-indigo-700 disabled:opacity-50"
                            :disabled="submitting || otp.length !== 6"
                            @click="verifyOtp"
                        >
                            {{ submitting ? 'Verifying…' : 'Verify & Submit Withdrawal' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script>
import { computed, defineComponent, h, onBeforeUnmount, onMounted, ref } from 'vue';
import axios from 'axios';

const SummaryRow = defineComponent({
    props: {
        label: String,
        value: [String, Number],
    },
    setup(props) {
        return () => h('div', {
            class: 'flex items-center justify-between gap-4 border-b border-slate-100 pb-3 last:border-0 dark:border-slate-800',
        }, [
            h('span', { class: 'text-sm text-slate-500' }, props.label),
            h('span', { class: 'text-right text-sm font-black' }, props.value),
        ]);
    },
});

export default {
    name: 'MerchantPayouts',
    components: { SummaryRow },

    setup() {
        const loading = ref(false);
        const previewing = ref(false);
        const submitting = ref(false);

        const balance = ref({
            available_balance: 0,
            ledger_balance: 0,
            reserved_balance: 0,
        });

        const bankAccounts = ref([]);
        const payouts = ref([]);
        const preview = ref(null);

        const form = ref({
            amount: '',
            bank_account_id: '',
            narration: '',
        });

        const pagination = ref({
            current_page: 1,
            last_page: 1,
        });

        const successMessage = ref('');
        const errorMessage = ref('');
        const securityError = ref('');

        const securityModalOpen = ref(false);
        const securityStep = ref('pin');
        const pin = ref('');
        const otp = ref('');
        const requestId = ref('');
        const emailHint = ref('');
        const expiresSeconds = ref(0);
        const resendSeconds = ref(0);

        let timer = null;

        const canPreview = computed(() => {
            return Number(form.value.amount) > 0 &&
                Boolean(form.value.bank_account_id);
        });

        const countdownLabel = computed(() => {
            const minutes = Math.floor(expiresSeconds.value / 60);
            const seconds = expiresSeconds.value % 60;

            return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        });

        const unwrap = (response) => {
            const body = response?.data || {};
            return body.data ?? body;
        };

        const extractMessage = (error, fallback) => {
            return error?.response?.data?.errors?.otp?.[0]
                || error?.response?.data?.errors?.pin?.[0]
                || error?.response?.data?.errors?.amount?.[0]
                || error?.response?.data?.message
                || fallback;
        };

        const fetchBalance = async () => {
            const response = await axios.get('/api/v1/wallet/balance');
            const data = unwrap(response);

            balance.value = {
                available_balance: Number(data.available_balance ?? data.balance ?? 0),
                ledger_balance: Number(data.ledger_balance ?? 0),
                reserved_balance: Number(data.reserved_balance ?? 0),
            };
        };

        const fetchBankAccounts = async () => {
            const response = await axios.get('/api/v1/bank-accounts');
            const data = unwrap(response);
            bankAccounts.value = Array.isArray(data) ? data : [];
        };

        const fetchPayouts = async (page = 1) => {
            const response = await axios.get('/api/v1/payouts', {
                params: { page, per_page: 20 },
            });

            const data = unwrap(response);
            const paginator = data?.data ? data : response?.data?.data || data;

            payouts.value = Array.isArray(paginator?.data)
                ? paginator.data
                : Array.isArray(data)
                    ? data
                    : [];

            pagination.value = {
                current_page: Number(paginator?.current_page || 1),
                last_page: Number(paginator?.last_page || 1),
            };
        };

        const refreshPage = async () => {
            loading.value = true;
            errorMessage.value = '';

            try {
                await Promise.all([
                    fetchBalance(),
                    fetchBankAccounts(),
                    fetchPayouts(pagination.value.current_page),
                ]);
            } catch (error) {
                errorMessage.value = extractMessage(
                    error,
                    'The payout page could not be loaded.'
                );
            } finally {
                loading.value = false;
            }
        };

        const clearPreview = () => {
            preview.value = null;
            errorMessage.value = '';
        };

        const previewWithdrawal = async () => {
            previewing.value = true;
            errorMessage.value = '';
            successMessage.value = '';

            try {
                const response = await axios.post('/api/v1/payouts/preview', {
                    amount: Number(form.value.amount),
                });

                preview.value = unwrap(response);
            } catch (error) {
                preview.value = null;
                errorMessage.value = extractMessage(
                    error,
                    'The withdrawal preview could not be calculated.'
                );
            } finally {
                previewing.value = false;
            }
        };

        const openPinStep = () => {
            securityStep.value = 'pin';
            pin.value = '';
            otp.value = '';
            requestId.value = '';
            emailHint.value = '';
            securityError.value = '';
            securityModalOpen.value = true;
            document.body.style.overflow = 'hidden';
        };

        const closeSecurityModal = (force = false) => {
            if (submitting.value && !force) {
                return;
            }

            securityModalOpen.value = false;
            securityError.value = '';
            securityStep.value = 'pin';

            pin.value = '';
            otp.value = '';
            requestId.value = '';
            emailHint.value = '';

            stopTimer();
            document.body.style.overflow = '';
        };

        const sendOtp = async () => {
            submitting.value = true;
            securityError.value = '';

            try {
                const response = await axios.post('/api/v1/payouts/send-otp', {
                    amount: Number(form.value.amount),
                    bank_account_id: Number(form.value.bank_account_id),
                    narration: form.value.narration || null,
                    pin: pin.value,
                });

                const data = unwrap(response);

                requestId.value = data.request_id;
                emailHint.value = data.email_hint || 'your registered email';
                expiresSeconds.value = Number(data.expires_in_seconds || 300);
                resendSeconds.value = Number(data.resend_after_seconds || 60);
                securityStep.value = 'otp';
                otp.value = '';
                startTimer();
            } catch (error) {
                securityError.value = extractMessage(
                    error,
                    'The withdrawal OTP could not be sent.'
                );
            } finally {
                submitting.value = false;
            }
        };

        const resendOtp = async () => {
            if (resendSeconds.value > 0) return;
            await sendOtp();
        };

        const verifyOtp = async () => {
            submitting.value = true;
            securityError.value = '';

            try {
                const response = await axios.post('/api/v1/payouts/verify-otp', {
                    request_id: requestId.value,
                    otp: otp.value,
                });

                successMessage.value =
                    response?.data?.message ||
                    'Withdrawal submitted successfully.';

                closeSecurityModal(true);

                form.value = {
                    amount: '',
                    bank_account_id: '',
                    narration: '',
                };

                preview.value = null;

                await Promise.all([
                    fetchBalance(),
                    fetchPayouts(1),
                ]);
            } catch (error) {
                securityError.value = extractMessage(
                    error,
                    'The withdrawal OTP could not be verified.'
                );
            } finally {
                submitting.value = false;
            }
        };

        const startTimer = () => {
            stopTimer();

            timer = window.setInterval(() => {
                if (expiresSeconds.value > 0) {
                    expiresSeconds.value -= 1;
                }

                if (resendSeconds.value > 0) {
                    resendSeconds.value -= 1;
                }

                if (expiresSeconds.value <= 0) {
                    stopTimer();
                }
            }, 1000);
        };

        const stopTimer = () => {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        };

        const formatCurrency = (value) => {
            return new Intl.NumberFormat('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
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

        const maskAccount = (value) => {
            const account = String(value || '');

            if (account.length <= 4) return account || 'N/A';

            return `${'*'.repeat(account.length - 4)}${account.slice(-4)}`;
        };

        const formatText = (value) => {
            return String(value || '')
                .replaceAll('_', ' ')
                .replace(/\b\w/g, (letter) => letter.toUpperCase());
        };

        const statusLabel = (status) => {
            const value = String(status || '').toLowerCase();

            if (value === 'pending') return 'Pending Review';
            if (value === 'successful' || value === 'success') return 'Successful';

            return formatText(value || 'pending');
        };

        const statusClass = (status) => {
            const value = String(status || '').toLowerCase();

            if (['success', 'successful', 'completed'].includes(value)) {
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

        onMounted(refreshPage);

        onBeforeUnmount(() => {
            stopTimer();
            document.body.style.overflow = '';
        });

        return {
            loading,
            previewing,
            submitting,
            balance,
            bankAccounts,
            payouts,
            preview,
            form,
            pagination,
            successMessage,
            errorMessage,
            securityError,
            securityModalOpen,
            securityStep,
            pin,
            otp,
            emailHint,
            resendSeconds,
            countdownLabel,
            canPreview,
            refreshPage,
            fetchPayouts,
            clearPreview,
            previewWithdrawal,
            openPinStep,
            closeSecurityModal,
            sendOtp,
            resendOtp,
            verifyOtp,
            formatCurrency,
            formatDate,
            maskAccount,
            formatText,
            statusLabel,
            statusClass,
        };
    },
};
</script>

<style scoped>
.payout-input {
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

.payout-input:focus {
    border-color: rgb(99 102 241);
    box-shadow: 0 0 0 4px rgb(99 102 241 / 0.1);
}

:global(.dark) .payout-input {
    border-color: rgb(51 65 85);
    background: rgb(2 6 23);
    color: white;
}
</style>
