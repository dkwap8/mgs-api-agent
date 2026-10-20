<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-950 dark:text-white">Transactions</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Inbound collection records</p>
        </div>

        <!-- Filters -->
        <div class="rounded-2xl bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                <select v-model="filters.status" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    <option value="">All Status</option>
                    <option value="success">Success</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                </select>
                <input v-model="filters.date_from" type="date" placeholder="From" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                <input v-model="filters.date_to" type="date" placeholder="To" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                <input v-model="filters.account_no" placeholder="Account No." class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-950 placeholder-gray-400 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder-gray-500" />
                <input v-model="filters.customer_email" placeholder="Customer email" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-950 placeholder-gray-400 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder-gray-500" />
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <input v-model="filters.search" placeholder="Reference / payer" class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-950 placeholder-gray-400 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder-gray-500" />
                <button @click="applyFilters" class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    Apply
                </button>
                <button @click="clearFilters" class="rounded-xl border border-slate-200 px-5 py-2 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-[#2a2a2a]">
                    Clear
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
            <div v-if="loading" class="py-12 text-center text-slate-500 dark:text-slate-400">
                Loading transactions...
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-medium text-slate-500 dark:text-slate-400">Reference</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-slate-500 dark:text-slate-400">Amount</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-slate-500 dark:text-slate-400">Type</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-slate-500 dark:text-slate-400">Status</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-slate-500 dark:text-slate-400">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="txn in transactions" :key="txn.id || txn.reference" class="hover:bg-slate-50 dark:hover:bg-[#1f1f1f]">
                            <td class="px-4 py-3 font-mono text-sm text-slate-950 dark:text-white">{{ txn.reference || 'N/A' }}</td>
                            <td class="px-4 py-3 font-medium text-slate-950 dark:text-white">₦{{ formatCurrency(txn.amount) }}</td>
                            <td class="px-4 py-3">
                                <span :class="typeClass(txn.type)">
                                    {{ txn.type || 'transaction' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="statusClass(txn.status)">
                                    {{ txn.status || 'pending' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">{{ formatDate(txn.created_at) }}</td>
                        </tr>
                        <tr v-if="transactions.length === 0">
                            <td colspan="5" class="px-4 py-12 text-center">
                                <p class="text-slate-500 dark:text-slate-400">No transactions found</p>
                                <p class="text-sm text-slate-400 dark:text-slate-500">Try adjusting or clearing the filters.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }}
                    </span>
                    <div class="flex gap-2">
                        <button
                            @click="prevPage"
                            :disabled="!pagination.prev_page_url"
                            class="rounded-xl border border-slate-200 px-4 py-1.5 text-sm hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:hover:bg-[#2a2a2a]"
                        >
                            Previous
                        </button>
                        <button
                            @click="nextPage"
                            :disabled="!pagination.next_page_url"
                            class="rounded-xl border border-slate-200 px-4 py-1.5 text-sm hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:hover:bg-[#2a2a2a]"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

export default {
    name: 'Transactions',

    setup() {
        const loading = ref(false);
        const transactions = ref([]);

        const pagination = ref({
            from: 0,
            to: 0,
            total: 0,
            prev_page_url: null,
            next_page_url: null,
        });

        const filters = ref({
            search: '',
            status: '',
            date_from: '',
            date_to: '',
            account_no: '',
            customer_email: '',
        });

        const formatCurrency = (amount) => {
            return new Intl.NumberFormat('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount || 0);
        };

        const formatDate = (date) => {
            if (!date) return 'N/A';
            return new Date(date).toLocaleString('en-NG', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        };

        const typeClass = (type) => {
            const classes = {
                credit: 'inline-block rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300',
                debit: 'inline-block rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300',
                collection: 'inline-block rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
                payout: 'inline-block rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
                refund: 'inline-block rounded-full bg-purple-100 px-3 py-1 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
            };
            return classes[type] || 'inline-block rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 dark:bg-slate-700/30 dark:text-slate-300';
        };

        const statusClass = (status) => {
            const classes = {
                success: 'inline-block rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300',
                pending: 'inline-block rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                failed: 'inline-block rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300',
                processing: 'inline-block rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
            };
            return classes[status] || classes.pending;
        };

        const cleanParams = () => {
            const params = {};
            Object.entries(filters.value).forEach(([key, value]) => {
                if (value !== '') params[key] = value;
            });
            return params;
        };

        const fetchTransactions = async (url = null) => {
            loading.value = true;
            try {
                const response = await axios.get(url || '/api/v1/wallet/transactions', {
                    params: url ? {} : cleanParams(),
                });
                const data = response.data.data;
                transactions.value = data?.data || data || [];
                pagination.value = {
                    from: data?.from || 0,
                    to: data?.to || 0,
                    total: data?.total || transactions.value.length,
                    prev_page_url: data?.prev_page_url || null,
                    next_page_url: data?.next_page_url || null,
                };
            } catch (error) {
                console.error('Error fetching transactions:', error.response?.data || error);
                transactions.value = [];
            } finally {
                loading.value = false;
            }
        };

        const applyFilters = () => { fetchTransactions(); };
        const clearFilters = () => {
            filters.value = { search: '', status: '', date_from: '', date_to: '', account_no: '', customer_email: '' };
            fetchTransactions();
        };
        const prevPage = () => { if (pagination.value.prev_page_url) fetchTransactions(pagination.value.prev_page_url); };
        const nextPage = () => { if (pagination.value.next_page_url) fetchTransactions(pagination.value.next_page_url); };

        watch(filters, () => { fetchTransactions(); }, { deep: true });

        onMounted(() => { fetchTransactions(); });

        return {
            loading,
            transactions,
            pagination,
            filters,
            formatCurrency,
            formatDate,
            typeClass,
            statusClass,
            applyFilters,
            clearFilters,
            prevPage,
            nextPage,
        };
    },
};
</script>