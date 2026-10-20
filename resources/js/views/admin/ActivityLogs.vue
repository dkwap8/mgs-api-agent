<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-950 dark:text-white">Activity Logs</h1>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Track admin and system actions.
                </p>
            </div>

            <button @click="fetchLogs" class="rounded-xl bg-indigo-600 px-4 py-2 text-white">
                Refresh
            </button>
        </div>

        <div class="admin-card">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <input v-model="filters.search" placeholder="Search logs..." class="input-field" />

                <input v-model="filters.module" placeholder="Module e.g Merchant" class="input-field" />

                <input v-model="filters.action" placeholder="Action e.g Approve" class="input-field" />
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
            <div v-if="loading" class="p-8 text-center text-slate-500 dark:text-slate-400">
                Loading activity logs...
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-900">
                        <tr>
                            <th class="th">User</th>
                            <th class="th">Module</th>
                            <th class="th">Action</th>
                            <th class="th">Description</th>
                            <th class="th">IP</th>
                            <th class="th">Date</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        <tr v-for="log in logs" :key="log.id">
                            <td class="td">{{ (log.admin?.name || log.user?.name) || 'System' }}</td>
                            <td class="td">{{ log.module }}</td>
                            <td class="td">
                                <span class="badge bg-indigo-100 text-indigo-700 dark:bg-blue-950/40 dark:text-indigo-300">
                                    {{ log.action }}
                                </span>
                            </td>
                            <td class="td">{{ log.description }}</td>
                            <td class="td font-mono text-sm">{{ log.ip_address || 'N/A' }}</td>
                            <td class="td">{{ formatDate(log.created_at) }}</td>
                        </tr>

                        <tr v-if="logs.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                                No activity logs found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t border-slate-200 px-4 py-3 dark:border-slate-700">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }}
                </p>

                <div class="flex gap-2">
                    <button @click="prevPage" :disabled="!pagination.prev_page_url" class="page-btn">Previous</button>
                    <button @click="nextPage" :disabled="!pagination.next_page_url" class="page-btn">Next</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { onMounted, ref, watch } from 'vue';
import axios from 'axios';

export default {
    name: 'ActivityLogs',

    setup() {
        const loading = ref(false);
        const logs = ref([]);

        const filters = ref({
            search: '',
            module: '',
            action: '',
        });

        const pagination = ref({
            from: 0,
            to: 0,
            total: 0,
            prev_page_url: null,
            next_page_url: null,
        });

        const cleanParams = () => {
            const params = {};

            Object.entries(filters.value).forEach(([key, value]) => {
                if (value !== '') params[key] = value;
            });

            return params;
        };

        const fetchLogs = async (url = null) => {
            loading.value = true;

            try {
                const response = await axios.get(url || '/api/v1/admin/activity-logs', {
                    params: url ? {} : cleanParams(),
                });

                const data = response.data.data;
                logs.value = data?.data || data || [];

                pagination.value = {
                    from: data?.from || 0,
                    to: data?.to || 0,
                    total: data?.total || logs.value.length,
                    prev_page_url: data?.prev_page_url || null,
                    next_page_url: data?.next_page_url || null,
                };
            } catch (error) {
                console.error('Error fetching activity logs:', error.response?.data || error);
                logs.value = [];
            } finally {
                loading.value = false;
            }
        };

        const prevPage = () => {
            if (pagination.value.prev_page_url) fetchLogs(pagination.value.prev_page_url);
        };

        const nextPage = () => {
            if (pagination.value.next_page_url) fetchLogs(pagination.value.next_page_url);
        };

        const formatDate = (date) => {
            if (!date) return 'N/A';
            return new Date(date).toLocaleString('en-NG');
        };

        watch(filters, () => fetchLogs(), { deep: true });
        onMounted(fetchLogs);

        return {
            loading,
            logs,
            filters,
            pagination,
            fetchLogs,
            prevPage,
            nextPage,
            formatDate,
        };
    },
};
</script>

<style scoped>
.admin-card {
    border-radius: 0.75rem;
    background: white;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgb(0 0 0 / 0.1);
}

.dark .admin-card {
    background: #111111;
}

.input-field {
    width: 100%;
    border-radius: 0.5rem;
    border: 1px solid #d1d5db;
    background: white;
    padding: 0.5rem 0.75rem;
    color: #111827;
    outline: none;
}

.dark .input-field {
    border-color: #374151;
    background: #1f1f1f;
    color: white;
}

.th {
    padding: 0.75rem 1rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
}

.dark .th {
    color: #d1d5db;
}

.td {
    padding: 0.85rem 1rem;
    color: #374151;
    white-space: nowrap;
}

.dark .td {
    color: #d1d5db;
}

.badge {
    display: inline-flex;
    align-items: center;
    border-radius: 9999px;
    padding: 0.25rem 0.65rem;
    font-size: 0.75rem;
    font-weight: 700;
}

.page-btn {
    border-radius: 0.5rem;
    border: 1px solid #d1d5db;
    padding: 0.35rem 0.75rem;
    font-size: 0.875rem;
    color: #374151;
}

.page-btn:disabled {
    opacity: 0.5;
}

.dark .page-btn {
    border-color: #374151;
    color: #d1d5db;
}
</style>