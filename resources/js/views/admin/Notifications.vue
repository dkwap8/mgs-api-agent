<template>
    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400">
                    Administration
                </p>

                <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">
                    Notifications
                </h1>

                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Review system activity and operational alerts.
                </p>
            </div>

            <div class="flex gap-2">
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900"
                    :disabled="loading"
                    @click="loadNotifications"
                >
                    {{ loading ? 'Refreshing…' : '↻ Refresh' }}
                </button>

                <button
                    type="button"
                    class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white hover:bg-emerald-700 disabled:opacity-50"
                    :disabled="markingAll || stats.unread === 0"
                    @click="markAllRead"
                >
                    {{ markingAll ? 'Updating…' : 'Mark all read' }}
                </button>
            </div>
        </section>

        <section
            v-if="errorMessage"
            class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
        >
            {{ errorMessage }}
        </section>

        <section class="grid gap-4 sm:grid-cols-3">
            <article class="summary-card">
                <p class="summary-label">Total</p>
                <p class="summary-value">{{ stats.total }}</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Unread</p>
                <p class="summary-value text-red-500">{{ stats.unread }}</p>
            </article>

            <article class="summary-card">
                <p class="summary-label">Read</p>
                <p class="summary-value text-emerald-500">{{ stats.read }}</p>
            </article>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="grid gap-3 border-b border-slate-200 p-4 dark:border-slate-800 sm:grid-cols-2">
                <select v-model="filters.status" class="filter-input" @change="loadNotifications(1)">
                    <option value="">All statuses</option>
                    <option value="unread">Unread</option>
                    <option value="read">Read</option>
                </select>

                <select v-model="filters.type" class="filter-input" @change="loadNotifications(1)">
                    <option value="">All notification types</option>
                    <option value="customer_registered">Customer registered</option>
                    <option value="kyc_verified">KYC verified</option>
                    <option value="virtual_account_created">Virtual account created</option>
                    <option value="virtual_account_failed">Virtual account failed</option>
                    <option value="business_approved">Business approved</option>
                    <option value="business_rejected">Business rejected</option>
                    <option value="payout_approved">Payout approved</option>
                    <option value="payout_rejected">Payout rejected</option>
                    <option value="system_alert">System alert</option>
                </select>
            </div>

            <div v-if="loading" class="py-20 text-center text-sm text-slate-500">
                Loading notifications…
            </div>

            <div v-else-if="notifications.length === 0" class="py-20 text-center text-sm text-slate-500">
                No notifications found.
            </div>

            <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                <article
                    v-for="item in notifications"
                    :key="item.id"
                    class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start"
                    :class="!item.is_read ? 'bg-indigo-50/60 dark:bg-indigo-950/20' : ''"
                >
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
                        :class="iconClass(item.type)"
                    >
                        {{ icon(item.type) }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-black text-slate-950 dark:text-white">
                                {{ item.title }}
                            </h2>

                            <span
                                v-if="!item.is_read"
                                class="rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-black text-white"
                            >
                                NEW
                            </span>
                        </div>

                        <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {{ item.message }}
                        </p>

                        <div class="mt-3 flex flex-wrap items-center gap-3 text-xs font-semibold text-slate-400">
                            <span>{{ typeLabel(item.type) }}</span>
                            <span>•</span>
                            <span>{{ formatDate(item.created_at) }}</span>
                        </div>
                    </div>

                    <div class="flex shrink-0 gap-2">
                        <button
                            v-if="!item.is_read"
                            type="button"
                            class="rounded-xl bg-indigo-600 px-3 py-2 text-xs font-black text-white hover:bg-indigo-700"
                            @click="markRead(item.id)"
                        >
                            Mark read
                        </button>

                        <button
                            type="button"
                            class="rounded-xl border border-red-200 px-3 py-2 text-xs font-black text-red-600 hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-950/30"
                            @click="deleteNotification(item.id)"
                        >
                            Delete
                        </button>
                    </div>
                </article>
            </div>

            <div
                v-if="pagination.lastPage > 1"
                class="flex items-center justify-between border-t border-slate-200 p-4 dark:border-slate-800"
            >
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-black disabled:opacity-40 dark:border-slate-700"
                    :disabled="pagination.currentPage <= 1"
                    @click="loadNotifications(pagination.currentPage - 1)"
                >
                    Previous
                </button>

                <span class="text-sm font-semibold text-slate-500">
                    Page {{ pagination.currentPage }} of {{ pagination.lastPage }}
                </span>

                <button
                    type="button"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-black disabled:opacity-40 dark:border-slate-700"
                    :disabled="pagination.currentPage >= pagination.lastPage"
                    @click="loadNotifications(pagination.currentPage + 1)"
                >
                    Next
                </button>
            </div>
        </section>
    </div>
</template>

<script>
import axios from 'axios';
import { verificationModal } from '../../services/verificationModal';

export default {
    name: 'AdminNotifications',

    data() {
        return {
            loading: false,
            markingAll: false,
            errorMessage: '',
            notifications: [],
            filters: {
                status: '',
                type: '',
            },
            stats: {
                total: 0,
                unread: 0,
                read: 0,
            },
            pagination: {
                currentPage: 1,
                lastPage: 1,
            },
        };
    },

    mounted() {
        this.loadNotifications();
    },

    methods: {
        async loadNotifications(page = 1) {
            this.loading = true;
            this.errorMessage = '';

            try {
                const response = await axios.get(
                    '/api/v1/admin/notifications',
                    {
                        params: {
                            page,
                            status: this.filters.status || undefined,
                            type: this.filters.type || undefined,
                        },
                    }
                );

                const paginator = response.data?.data || {};

                this.notifications = Array.isArray(paginator.data)
                    ? paginator.data
                    : [];

                this.pagination.currentPage =
                    Number(paginator.current_page || 1);

                this.pagination.lastPage =
                    Number(paginator.last_page || 1);

                this.stats.total =
                    Number(paginator.total || this.notifications.length);

                await this.loadUnreadCount();

                this.stats.read = Math.max(
                    0,
                    this.stats.total - this.stats.unread
                );
            } catch (error) {
                this.errorMessage =
                    error.response?.data?.message ||
                    'Notifications could not be loaded.';
            } finally {
                this.loading = false;
            }
        },

        async loadUnreadCount() {
            const response = await axios.get(
                '/api/v1/admin/notifications/unread'
            );

            this.stats.unread = Number(
                response.data?.data?.count || 0
            );
        },

        async markRead(id) {
            try {
                await axios.post(
                    `/api/v1/admin/notifications/${id}/read`
                );

                await this.loadNotifications(
                    this.pagination.currentPage
                );
            } catch (error) {
                this.errorMessage =
                    error.response?.data?.message ||
                    'Notification could not be updated.';
            }
        },

        async markAllRead() {
            this.markingAll = true;

            try {
                await axios.post(
                    '/api/v1/admin/notifications/read-all'
                );

                await this.loadNotifications(1);
            } catch (error) {
                this.errorMessage =
                    error.response?.data?.message ||
                    'Notifications could not be updated.';
            } finally {
                this.markingAll = false;
            }
        },

        async deleteNotification(id) {
            const confirmed = await verificationModal.confirm({
                title: 'Delete notification?',
                message: 'This notification will be permanently removed.',
                confirmText: 'Delete notification',
                danger: true,
            });
            if (!confirmed) return;

            try {
                await axios.delete(
                    `/api/v1/admin/notifications/${id}`
                );

                await this.loadNotifications(
                    this.pagination.currentPage
                );
            } catch (error) {
                this.errorMessage =
                    error.response?.data?.message ||
                    'Notification could not be deleted.';
            }
        },

        icon(type) {
            return {
                customer_registered: '👤',
                kyc_verified: '🪪',
                virtual_account_created: '🏦',
                virtual_account_failed: '⚠️',
                business_approved: '✅',
                business_rejected: '❌',
                payout_approved: '💸',
                payout_rejected: '⛔',
                system_alert: '🔔',
            }[type] || '🔔';
        },

        iconClass(type) {
            if (
                [
                    'virtual_account_failed',
                    'business_rejected',
                    'payout_rejected',
                ].includes(type)
            ) {
                return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';
            }

            if (
                [
                    'virtual_account_created',
                    'business_approved',
                    'payout_approved',
                    'kyc_verified',
                ].includes(type)
            ) {
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
            }

            return 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300';
        },

        typeLabel(type) {
            return String(type || 'notification')
                .replaceAll('_', ' ')
                .replace(/\b\w/g, (letter) => letter.toUpperCase());
        },

        formatDate(date) {
            return new Date(date).toLocaleString();
        },
    },
};
</script>

<style scoped>
.summary-card {
    border-radius: 1rem;
    border: 1px solid rgb(226 232 240);
    background: white;
    padding: 1.25rem;
    box-shadow: 0 1px 2px rgb(15 23 42 / 0.05);
}

.summary-label {
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgb(100 116 139);
}

.summary-value {
    margin-top: 0.5rem;
    font-size: 1.875rem;
    font-weight: 900;
}

.filter-input {
    width: 100%;
    border-radius: 0.875rem;
    border: 1px solid rgb(226 232 240);
    background: white;
    padding: 0.7rem 0.9rem;
    outline: none;
}

:global(.dark) .summary-card,
:global(.dark) .filter-input {
    border-color: rgb(51 65 85);
    background: rgb(15 23 42);
    color: white;
}
</style>
