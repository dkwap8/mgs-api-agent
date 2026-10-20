<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-950 dark:text-white">Customers</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Manage your customers and their KYC status</p>
            </div>

            <button
                @click="showModal = true"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600"
            >
                <span>＋</span>
                Add Customer
            </button>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="card">
                <p class="card-label">Total Customers</p>
                <p class="card-value">{{ customers.length }}</p>
            </div>

            <div class="card">
                <p class="card-label">Verified</p>
                <p class="mt-2 text-2xl font-bold text-green-600 dark:text-green-400">{{ verifiedCount }}</p>
            </div>

            <div class="card">
                <p class="card-label">Pending</p>
                <p class="mt-2 text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ pendingCount }}</p>
            </div>

            <div class="card">
                <p class="card-label">Active</p>
                <p class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ activeCount }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 rounded-2xl bg-white p-4 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
            <input v-model="searchQuery" placeholder="Search customers..." class="input-field flex-1" />

            <select v-model="statusFilter" class="input-field w-auto">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="blocked">Blocked</option>
            </select>

            <select v-model="kycFilter" class="input-field w-auto">
                <option value="all">All KYC</option>
                <option value="verified">Verified</option>
                <option value="pending">Pending</option>
                <option value="failed">Failed</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-900">
                        <tr>
                            <th class="th">Name</th>
                            <th class="th">Email</th>
                            <th class="th">Phone</th>
                            <th class="th">KYC Status</th>
                            <th class="th">Status</th>
                            <th class="th">Date Added</th>
                            <th class="th text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="customer in filteredCustomers" :key="customer.id" class="hover:bg-slate-50 dark:hover:bg-[#1f1f1f]">
                            <td class="td font-medium text-slate-950 dark:text-white">
                                {{ customer.first_name || '' }} {{ customer.last_name || '' }}
                            </td>

                            <td class="td">{{ customer.email || 'N/A' }}</td>
                            <td class="td">{{ customer.phone || 'N/A' }}</td>

                            <td class="td">
                                <span :class="kycStatusClass(customer.kyc_status)">
                                    {{ customer.kyc_status || 'pending' }}
                                </span>
                            </td>

                            <td class="td">
                                <span :class="statusBadgeClass(customer.status)">
                                    {{ customer.status || 'active' }}
                                </span>
                            </td>

                            <td class="td text-sm text-slate-500 dark:text-slate-400">
                                {{ formatDate(customer.created_at) }}
                            </td>

                            <td class="td">
                                <div class="flex justify-end gap-2">
                                    <button
                                        v-if="customer.kyc_status !== 'verified'"
                                        @click="openVerifyModal(customer)"
                                        class="rounded-xl bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700"
                                    >
                                        Verify KYC
                                    </button>

                                    <span v-else class="text-xs font-semibold text-green-600 dark:text-green-400">
                                        Verified
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="filteredCustomers.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center">
                                <p class="text-slate-500 dark:text-slate-400">No customers found</p>
                                <p class="text-sm text-slate-400 dark:text-slate-500">Try adjusting your filters, or add a new customer.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Customer Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-950">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-slate-950 dark:text-white">Add Customer</h3>
                    <button @click="closeModal" class="text-2xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">✕</button>
                </div>

                <form @submit.prevent="addCustomer" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <input v-model="form.first_name" placeholder="First name" class="input-field" required />
                        <input v-model="form.last_name" placeholder="Last name" class="input-field" required />
                    </div>

                    <input v-model="form.email" type="email" placeholder="Email" class="input-field w-full" />
                    <input v-model="form.phone" placeholder="Phone number" class="input-field w-full" required />

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="closeModal" class="rounded-xl border border-slate-200 px-4 py-2 hover:bg-slate-50 dark:border-slate-700 dark:text-white dark:hover:bg-[#2a2a2a]">
                            Cancel
                        </button>

                        <button type="submit" :disabled="loading" class="rounded-xl bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700 disabled:opacity-50">
                            {{ loading ? 'Saving...' : 'Save Customer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Verify KYC Modal -->
        <div v-if="showVerifyModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-950">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-slate-950 dark:text-white">Verify Customer KYC</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ selectedCustomer?.first_name }} {{ selectedCustomer?.last_name }}
                        </p>
                    </div>

                    <button @click="closeVerifyModal" class="text-2xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">✕</button>
                </div>

                <div v-if="verifyMessage.text" :class="verifyMessageClass" class="mb-4 rounded-xl p-3 text-sm font-medium">
                    {{ verifyMessage.text }}
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300">Verification Type</label>
                    <select v-model="verifyForm.type" class="input-field w-full">
                        <option value="bvn">BVN</option>
                        <option value="nin">NIN</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300">
                        {{ verifyForm.type === 'bvn' ? 'BVN Number' : 'NIN Number' }}
                    </label>

                    <input
                        v-model="verifyForm.number"
                        maxlength="11"
                        placeholder="Enter 11 digit number"
                        class="input-field w-full"
                    />

                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Must be exactly 11 digits.
                    </p>
                </div>

                <div class="mb-5">
                    <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300">Name</label>
                    <input v-model="verifyForm.name" class="input-field w-full" />
                </div>

                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        @click="closeVerifyModal"
                        class="rounded-xl border border-slate-200 px-4 py-2 hover:bg-slate-50 dark:border-slate-700 dark:text-white dark:hover:bg-[#2a2a2a]"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="verifyCustomer"
                        :disabled="verifyLoading"
                        class="rounded-xl bg-green-600 px-4 py-2 font-semibold text-white hover:bg-green-700 disabled:opacity-50"
                    >
                        {{ verifyLoading ? 'Verifying...' : 'Verify Now' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

export default {
    name: 'Customers',

    setup() {
        const customers = ref([]);
        const showModal = ref(false);
        const showVerifyModal = ref(false);
        const selectedCustomer = ref(null);
        const loading = ref(false);
        const verifyLoading = ref(false);
        const searchQuery = ref('');
        const statusFilter = ref('all');
        const kycFilter = ref('all');

        const form = ref({
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
        });

        const verifyForm = ref({
            type: 'bvn',
            number: '',
            name: '',
        });

        const verifyMessage = ref({
            text: '',
            type: '',
        });

        const verifyMessageClass = computed(() => {
            return verifyMessage.value.type === 'success'
                ? 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300'
                : 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300';
        });

        const verifiedCount = computed(() => customers.value.filter(c => c.kyc_status === 'verified').length);
        const pendingCount = computed(() => customers.value.filter(c => c.kyc_status === 'pending').length);
        const activeCount = computed(() => customers.value.filter(c => c.status === 'active').length);

        const filteredCustomers = computed(() => {
            let result = customers.value;

            if (searchQuery.value) {
                const query = searchQuery.value.toLowerCase();
                result = result.filter(c =>
                    (c.first_name || '').toLowerCase().includes(query) ||
                    (c.last_name || '').toLowerCase().includes(query) ||
                    (c.email || '').toLowerCase().includes(query) ||
                    (c.phone || '').includes(query)
                );
            }

            if (statusFilter.value !== 'all') {
                result = result.filter(c => c.status === statusFilter.value);
            }

            if (kycFilter.value !== 'all') {
                result = result.filter(c => c.kyc_status === kycFilter.value);
            }

            return result;
        });

        const fetchCustomers = async () => {
            try {
                const response = await axios.get('/api/v1/customers');
                customers.value = response.data.data?.data || response.data.data || [];
            } catch (error) {
                console.error('Error fetching customers:', error.response?.data || error);
                customers.value = [];
            }
        };

        const addCustomer = async () => {
            loading.value = true;

            try {
                await axios.post('/api/v1/customers', form.value);
                closeModal();
                await fetchCustomers();
            } catch (error) {
                console.error('Failed to add customer:', error.response?.data || error);
            } finally {
                loading.value = false;
            }
        };

        const closeModal = () => {
            showModal.value = false;
            form.value = {
                first_name: '',
                last_name: '',
                email: '',
                phone: '',
            };
        };

        const openVerifyModal = (customer) => {
            selectedCustomer.value = customer;
            verifyForm.value = {
                type: 'bvn',
                number: '',
                name: `${customer.first_name || ''} ${customer.last_name || ''}`.trim(),
            };
            verifyMessage.value = { text: '', type: '' };
            showVerifyModal.value = true;
        };

        const closeVerifyModal = () => {
            showVerifyModal.value = false;
            selectedCustomer.value = null;
            verifyForm.value = {
                type: 'bvn',
                number: '',
                name: '',
            };
            verifyMessage.value = { text: '', type: '' };
        };

        const verifyCustomer = async () => {
            if (!selectedCustomer.value) return;

            if (!/^\d{11}$/.test(verifyForm.value.number)) {
                verifyMessage.value = {
                    text: 'BVN/NIN must be exactly 11 digits.',
                    type: 'error',
                };
                return;
            }

            verifyLoading.value = true;

            try {
                const endpoint =
                    verifyForm.value.type === 'bvn'
                        ? `/api/v1/customers/${selectedCustomer.value.id}/verify-bvn`
                        : `/api/v1/customers/${selectedCustomer.value.id}/verify-nin`;

                const payload =
                    verifyForm.value.type === 'bvn'
                        ? {
                              bvn: verifyForm.value.number,
                              name: verifyForm.value.name,
                          }
                        : {
                              nin: verifyForm.value.number,
                              name: verifyForm.value.name,
                          };

                await axios.post(endpoint, payload);

                verifyMessage.value = {
                    text: 'Customer KYC verified successfully.',
                    type: 'success',
                };

                await fetchCustomers();

                setTimeout(() => {
                    closeVerifyModal();
                }, 800);
            } catch (error) {
                console.error('KYC verification error:', error.response?.data || error);

                verifyMessage.value = {
                    text: error.response?.data?.message || 'KYC verification failed.',
                    type: 'error',
                };
            } finally {
                verifyLoading.value = false;
            }
        };

        const kycStatusClass = (status) => {
            const classes = {
                verified: 'inline-block rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300',
                pending: 'inline-block rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                failed: 'inline-block rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300',
            };

            return classes[status] || classes.pending;
        };

        const statusBadgeClass = (status) => {
            const classes = {
                active: 'inline-block rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
                inactive: 'inline-block rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 dark:bg-slate-700/30 dark:text-slate-300',
                blocked: 'inline-block rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300',
            };

            return classes[status] || classes.active;
        };

        const formatDate = (date) => {
            if (!date) return 'N/A';

            return new Date(date).toLocaleDateString('en-US', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            });
        };

        onMounted(fetchCustomers);

        return {
            customers,
            showModal,
            showVerifyModal,
            selectedCustomer,
            loading,
            verifyLoading,
            searchQuery,
            statusFilter,
            kycFilter,
            form,
            verifyForm,
            verifyMessage,
            verifyMessageClass,
            verifiedCount,
            pendingCount,
            activeCount,
            filteredCustomers,
            fetchCustomers,
            addCustomer,
            closeModal,
            openVerifyModal,
            closeVerifyModal,
            verifyCustomer,
            kycStatusClass,
            statusBadgeClass,
            formatDate,
        };
    },
};
</script>

<style scoped>
.card {
    border-radius: 0.75rem;
    background: white;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgb(0 0 0 / 0.1);
}

.dark .card {
    background: #111111;
}

.card-label {
    font-size: 0.875rem;
    color: #6b7280;
}

.dark .card-label {
    color: #9ca3af;
}

.card-value {
    margin-top: 0.5rem;
    font-size: 1.5rem;
    font-weight: 800;
    color: #111827;
}

.dark .card-value {
    color: white;
}

.input-field {
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
    background: white;
    padding: 0.5rem 0.75rem;
    color: #111827;
    outline: none;
}

.input-field:focus {
    border-color: #3b82f6;
}

.dark .input-field {
    border-color: #374151;
    background: #1f1f1f;
    color: white;
}

.th {
    padding: 0.75rem 1rem;
    text-align: left;
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
}

.dark .th {
    color: #9ca3af;
}

.td {
    padding: 0.75rem 1rem;
    color: #4b5563;
    white-space: nowrap;
}

.dark .td {
    color: #d1d5db;
}
</style>