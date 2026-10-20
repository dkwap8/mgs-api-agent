<template>
    <div class="text-slate-950 dark:text-gray-100">
        <h2 class="mb-6 text-2xl font-bold text-slate-950 dark:text-white">Transactions</h2>

        <div class="mb-4 rounded-xl bg-white p-4 shadow dark:bg-slate-800">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <input v-model="filters.search" placeholder="Search by reference..." class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-950 placeholder-gray-400 dark:border-gray-600 dark:bg-slate-900 dark:text-white dark:placeholder-gray-500" />

                <select v-model="filters.status" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-950 dark:border-gray-600 dark:bg-slate-900 dark:text-white">
                    <option value="">All Status</option>
                    <option value="success">Success</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                </select>

                <input v-model="filters.date_from" type="date" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-950 dark:border-gray-600 dark:bg-slate-900 dark:text-white" />
                <input v-model="filters.date_to" type="date" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-950 dark:border-gray-600 dark:bg-slate-900 dark:text-white" />
            </div>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow dark:bg-slate-800">
            <div v-if="loading" class="p-6 text-center text-slate-500 dark:text-slate-400">
                Loading transactions...
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-slate-700 dark:text-gray-200">Reference</th>
                            <th class="px-4 py-2 text-left text-slate-700 dark:text-gray-200">Amount</th>
                            <th class="px-4 py-2 text-left text-slate-700 dark:text-gray-200">Type</th>
                            <th class="px-4 py-2 text-left text-slate-700 dark:text-gray-200">Status</th>
                            <th class="px-4 py-2 text-left text-slate-700 dark:text-gray-200">Date</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        <tr v-for="txn in transactions" :key="txn.id || txn.reference" class="text-slate-700 dark:text-slate-300">
                            <td class="px-4 py-2 font-mono text-sm">{{ txn.reference || 'N/A' }}</td>
                            <td class="px-4 py-2 font-medium">₦{{ formatCurrency(txn.amount) }}</td>
                            <td class="px-4 py-2">
                                <span :class="typeClass(txn.type)">
                                    {{ txn.type || 'transaction' }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                <span :class="statusClass(txn.status)">
                                    {{ txn.status || 'pending' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-slate-500 dark:text-slate-400">{{ formatDate(txn.created_at) }}</td>
                        </tr>

                        <tr v-if="transactions.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                                No transactions found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }}
                    </span>

                    <div class="flex space-x-2">
                        <button
                            @click="prevPage"
                            :disabled="!pagination.prev_page_url"
                            class="rounded border border-slate-300 px-3 py-1 text-slate-700 hover:bg-slate-50 disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-slate-700"
                        >
                            Previous
                        </button>

                        <button
                            @click="nextPage"
                            :disabled="!pagination.next_page_url"
                            class="rounded border border-slate-300 px-3 py-1 text-slate-700 hover:bg-slate-50 disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-slate-700"
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
        });

        const formatCurrency = (amount) => {
            return new Intl.NumberFormat('en-NG').format(amount || 0);
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
                credit: 'px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                debit: 'px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                collection: 'px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
                payout: 'px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
                refund: 'px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
            };

            return classes[type] || 'px-2 py-1 text-xs rounded-full bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300';
        };

        const statusClass = (status) => {
            const classes = {
                success: 'px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                pending: 'px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                failed: 'px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                processing: 'px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
            };

            return classes[status] || classes.pending;
        };

        const cleanParams = () => {
            const params = {};

            Object.entries(filters.value).forEach(([key, value]) => {
                if (value !== '') {
                    params[key] = value;
                }
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

        const prevPage = () => {
            if (pagination.value.prev_page_url) {
                fetchTransactions(pagination.value.prev_page_url);
            }
        };

        const nextPage = () => {
            if (pagination.value.next_page_url) {
                fetchTransactions(pagination.value.next_page_url);
            }
        };

        watch(filters, () => {
            fetchTransactions();
        }, { deep: true });

        onMounted(() => {
            fetchTransactions();
        });

        return {
            loading,
            transactions,
            pagination,
            filters,
            formatCurrency,
            formatDate,
            typeClass,
            statusClass,
            prevPage,
            nextPage,
        };
    },
};
</script>