<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-950 dark:text-white">Virtual Accounts</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Reusable collection accounts for your customers</p>
            </div>
            <button @click="showCreateModal = true" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                <span>＋</span>
                Create account
            </button>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3 rounded-2xl bg-white p-4 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
            <select v-model="statusFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                <option value="all">All</option>
                <option value="static">Static</option>
                <option value="dynamic">Dynamic</option>
            </select>
            <input
                v-model="searchQuery"
                placeholder="Account no. / name / reference"
                class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-950 placeholder-gray-400 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder-gray-500"
                @input="filterAccounts"
            />
            <button @click="clearFilters" class="rounded-xl border border-slate-200 px-4 py-2 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-[#2a2a2a]">
                Clear
            </button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-2xl bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
                <p class="text-sm text-slate-500 dark:text-slate-400">Total Accounts</p>
                <p class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">{{ accounts.length }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
                <p class="text-sm text-slate-500 dark:text-slate-400">Static</p>
                <p class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ staticCount }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
                <p class="text-sm text-slate-500 dark:text-slate-400">Dynamic</p>
                <p class="mt-2 text-2xl font-bold text-green-600 dark:text-green-400">{{ dynamicCount }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
                <p class="text-sm text-slate-500 dark:text-slate-400">Active</p>
                <p class="mt-2 text-2xl font-bold text-green-600 dark:text-green-400">{{ activeCount }}</p>
            </div>
        </div>

        <!-- Accounts Grid -->
        <div v-if="loading" class="py-12 text-center text-slate-500 dark:text-slate-400">
            Loading virtual accounts...
        </div>

        <div v-else-if="filteredAccounts.length === 0" class="rounded-2xl bg-white py-12 text-center shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
            <p class="text-slate-500 dark:text-slate-400">No virtual accounts found</p>
            <p class="text-sm text-slate-400 dark:text-slate-500">Try adjusting the filters, or create one with "Create account".</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="account in filteredAccounts"
                :key="account.id"
                class="rounded-2xl bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] transition-shadow hover:shadow-md dark:bg-slate-950"
            >
                <div class="flex items-start justify-between">
                    <div class="min-w-0 flex-1">
                        <p class="font-mono text-sm font-semibold text-slate-950 dark:text-white">{{ account.account_number || 'N/A' }}</p>
                        <p class="text-sm text-slate-600 dark:text-slate-300">{{ account.name || account.reference || 'Unnamed' }}</p>
                    </div>
                    <span :class="account.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-700/30 dark:text-slate-300'" class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium">
                        {{ account.status || 'active' }}
                    </span>
                </div>

                <div class="mt-3 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                    <span>Type:</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ account.type || 'static' }}</span>
                </div>

                <div class="mt-2 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                    <span>Created:</span>
                    <span>{{ formatDate(account.created_at) }}</span>
                </div>

                <div class="mt-4 flex gap-2">
                    <button @click="copyAccountNumber(account.account_number)" class="flex-1 rounded-xl border border-slate-200 px-3 py-1.5 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-[#2a2a2a]">
                        📋 Copy
                    </button>
                    <button @click="viewAccountDetails(account)" class="flex-1 rounded-xl bg-indigo-600 px-3 py-1.5 text-sm text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                        View
                    </button>
                </div>
            </div>
        </div>

        <!-- Create Account Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-950">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-slate-950 dark:text-white">Create Virtual Account</h3>
                    <button @click="closeCreateModal" class="text-2xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">✕</button>
                </div>

                <form @submit.prevent="createAccount" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Account Name</label>
                        <input v-model="newAccount.name" placeholder="Customer name or reference" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-950 placeholder-gray-400 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder-gray-500" required />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Account Type</label>
                        <select v-model="newAccount.type" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-950 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                            <option value="static">Static</option>
                            <option value="dynamic">Dynamic</option>
                        </select>
                    </div>

                    <div v-if="newAccount.type === 'dynamic'">
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Customer Email</label>
                        <input v-model="newAccount.email" type="email" placeholder="customer@email.com" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-950 placeholder-gray-400 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder-gray-500" />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="closeCreateModal" class="rounded-xl border border-slate-200 px-4 py-2 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-[#2a2a2a]">
                            Cancel
                        </button>
                        <button type="submit" :disabled="creating" class="rounded-xl bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-50">
                            {{ creating ? 'Creating...' : 'Create Account' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { verificationModal } from '../../services/verificationModal';

export default {
    name: 'VirtualAccounts',

    setup() {
        const loading = ref(false);
        const creating = ref(false);
        const accounts = ref([]);
        const searchQuery = ref('');
        const statusFilter = ref('all');
        const showCreateModal = ref(false);

        const newAccount = ref({
            name: '',
            type: 'static',
            email: '',
        });

        const staticCount = computed(() => accounts.value.filter(a => a.type === 'static').length);
        const dynamicCount = computed(() => accounts.value.filter(a => a.type === 'dynamic').length);
        const activeCount = computed(() => accounts.value.filter(a => a.status === 'active').length);

        const filteredAccounts = computed(() => {
            let result = accounts.value;

            if (statusFilter.value !== 'all') {
                result = result.filter(a => a.type === statusFilter.value);
            }

            if (searchQuery.value) {
                const query = searchQuery.value.toLowerCase();
                result = result.filter(a =>
                    (a.account_number || '').includes(query) ||
                    (a.name || '').toLowerCase().includes(query) ||
                    (a.reference || '').toLowerCase().includes(query)
                );
            }

            return result;
        });

        const fetchAccounts = async () => {
            loading.value = true;
            try {
                const response = await axios.get('/api/v1/virtual-accounts');
                const data = response.data.data;
                accounts.value = data?.data || data || [];
            } catch (error) {
                console.error('Error fetching virtual accounts:', error.response?.data || error);
                accounts.value = [];
            } finally {
                loading.value = false;
            }
        };

        const createAccount = async () => {
            if (!newAccount.value.name) {
                verificationModal.warning('Enter an account name before creating the virtual account.', { title: 'Account name required' });
                return;
            }

            creating.value = true;
            try {
                await axios.post('/api/v1/virtual-accounts', newAccount.value);
                closeCreateModal();
                await fetchAccounts();
            } catch (error) {
                console.error('Failed to create virtual account:', error.response?.data || error);
            } finally {
                creating.value = false;
            }
        };

        const copyAccountNumber = async (number) => {
            if (!number) return;
            try {
                await navigator.clipboard.writeText(number);
                verificationModal.success('The virtual account number has been copied to your clipboard.', { title: 'Account number copied' });
            } catch {
                verificationModal.error('The account number could not be copied. Please select and copy it manually.', { title: 'Copy unsuccessful' });
            }
        };

        const viewAccountDetails = (account) => {
            verificationModal.info(`Account: ${account.account_number || 'N/A'}\nName: ${account.name || account.reference || 'N/A'}\nType: ${account.type || 'N/A'}\nStatus: ${account.status || 'N/A'}`, { title: 'Virtual account details' });
        };

        const filterAccounts = () => { /* computed handles this */ };
        const clearFilters = () => {
            searchQuery.value = '';
            statusFilter.value = 'all';
        };
        const closeCreateModal = () => {
            showCreateModal.value = false;
            newAccount.value = { name: '', type: 'static', email: '' };
        };

        const formatDate = (date) => {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString('en-US', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            });
        };

        onMounted(fetchAccounts);

        return {
            loading,
            creating,
            accounts,
            searchQuery,
            statusFilter,
            showCreateModal,
            newAccount,
            staticCount,
            dynamicCount,
            activeCount,
            filteredAccounts,
            fetchAccounts,
            createAccount,
            copyAccountNumber,
            viewAccountDetails,
            filterAccounts,
            clearFilters,
            closeCreateModal,
            formatDate,
        };
    },
};
</script>