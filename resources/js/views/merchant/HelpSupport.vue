<template>
    <div class="space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-950 dark:text-white">Help & Support</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">We're here to help you with any questions or issues.</p>
        </div>

        <!-- Quick Contact -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-2xl bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📧</span>
                    <div>
                        <h3 class="font-semibold text-slate-950 dark:text-white">Email Support</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Send us an email at hello@paydrex.ng</p>
                        <a href="mailto:hello@mgsapi.ng" class="mt-2 inline-block text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                            Send Email →
                        </a>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📞</span>
                    <div>
                        <h3 class="font-semibold text-slate-950 dark:text-white">Call Us</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Reach our team on 0812345678</p>
                        <a href="tel:0812345678" class="mt-2 inline-block text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                            Call Now →
                        </a>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📚</span>
                    <div>
                        <h3 class="font-semibold text-slate-950 dark:text-white">Documentation</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Check our documentation for guides</p>
                        <router-link to="/user/documentation" class="mt-2 inline-block text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                            View Documentation →
                        </router-link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ticket Stats -->
        <div v-if="stats" class="grid grid-cols-2 gap-4 md:grid-cols-5">
            <div class="rounded-2xl bg-white p-4 text-center shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
                <p class="text-2xl font-bold text-slate-950 dark:text-white">{{ stats.total || 0 }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Total Tickets</p>
            </div>
            <div class="rounded-2xl bg-white p-4 text-center shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.open || 0 }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Open</p>
            </div>
            <div class="rounded-2xl bg-white p-4 text-center shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ stats.in_progress || 0 }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">In Progress</p>
            </div>
            <div class="rounded-2xl bg-white p-4 text-center shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.resolved || 0 }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Resolved</p>
            </div>
            <div class="rounded-2xl bg-white p-4 text-center shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
                <p class="text-2xl font-bold text-slate-600 dark:text-slate-400">{{ stats.closed || 0 }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Closed</p>
            </div>
        </div>

        <!-- Create Ticket Button -->
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold text-slate-950 dark:text-white">My Support Tickets</h2>
            <button @click="showCreateModal = true" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                + New Ticket
            </button>
        </div>

        <!-- Tickets List -->
        <div class="rounded-2xl bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
            <div v-if="loading" class="py-8 text-center text-slate-500 dark:text-slate-400">
                Loading tickets...
            </div>

            <div v-else-if="tickets.length === 0" class="py-12 text-center">
                <p class="text-slate-500 dark:text-slate-400">No support tickets yet</p>
                <p class="text-sm text-slate-400 dark:text-slate-500">Create a ticket and we'll get back to you.</p>
            </div>

            <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                <div v-for="ticket in tickets" :key="ticket.id" class="p-4 hover:bg-slate-50 dark:hover:bg-[#1f1f1f]">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="font-medium text-slate-950 dark:text-white">{{ ticket.subject }}</span>
                                <span :class="getStatusClass(ticket.status)" class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium">
                                    {{ ticket.status }}
                                </span>
                                <span :class="getPriorityClass(ticket.priority)" class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium">
                                    {{ ticket.priority }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300 line-clamp-2">{{ ticket.message }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                <span>#{{ ticket.ticket_number }}</span>
                                <span>•</span>
                                <span>{{ ticket.category_label || ticket.category }}</span>
                                <span>•</span>
                                <span>{{ formatDate(ticket.created_at) }}</span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="viewTicket(ticket)" class="rounded-xl border border-slate-200 px-3 py-1 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-[#2a2a2a]">
                                View
                            </button>
                            <button v-if="ticket.status !== 'closed'" @click="closeTicket(ticket.id)" class="rounded-xl border border-red-200 px-3 py-1 text-sm text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Ticket Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-950">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-slate-950 dark:text-white">Create Support Ticket</h3>
                    <button @click="showCreateModal = false" class="text-2xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">✕</button>
                </div>

                <form @submit.prevent="createTicket" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Subject</label>
                        <input v-model="newTicket.subject" placeholder="Brief subject" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-950 placeholder-gray-400 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder-gray-500" required />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Category</label>
                        <select v-model="newTicket.category" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-950 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white" required>
                            <option value="">Select category</option>
                            <option value="payment">Payment Issue</option>
                            <option value="account">Account Issue</option>
                            <option value="technical">Technical Issue</option>
                            <option value="general">General Inquiry</option>
                            <option value="bug">Bug Report</option>
                            <option value="feature">Feature Request</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Priority</label>
                        <select v-model="newTicket.priority" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-950 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Message</label>
                        <textarea v-model="newTicket.message" rows="4" placeholder="Describe your issue in detail..." class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-950 placeholder-gray-400 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder-gray-500" required></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showCreateModal = false" class="rounded-xl border border-slate-200 px-4 py-2 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-[#2a2a2a]">
                            Cancel
                        </button>
                        <button type="submit" :disabled="creating" class="rounded-xl bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-50">
                            {{ creating ? 'Creating...' : 'Create Ticket' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- View Ticket Modal -->
        <div v-if="showViewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-950">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-slate-950 dark:text-white">{{ selectedTicket?.subject }}</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">#{{ selectedTicket?.ticket_number }}</p>
                    </div>
                    <button @click="showViewModal = false" class="text-2xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">✕</button>
                </div>

                <div class="space-y-4">
                    <div class="flex flex-wrap gap-3">
                        <span :class="getStatusClass(selectedTicket?.status)" class="inline-block rounded-full px-3 py-1 text-sm font-medium">
                            {{ selectedTicket?.status }}
                        </span>
                        <span :class="getPriorityClass(selectedTicket?.priority)" class="inline-block rounded-full px-3 py-1 text-sm font-medium">
                            {{ selectedTicket?.priority }}
                        </span>
                        <span class="inline-block rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                            {{ selectedTicket?.category_label || selectedTicket?.category }}
                        </span>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-900">
                        <p class="whitespace-pre-wrap text-slate-700 dark:text-slate-300">{{ selectedTicket?.message }}</p>
                    </div>

                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        Created: {{ formatDate(selectedTicket?.created_at) }}
                    </div>

                    <div v-if="selectedTicket?.status !== 'closed'" class="border-t border-slate-200 pt-4 dark:border-slate-700">
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Add Reply</label>
                        <textarea v-model="replyMessage" rows="3" placeholder="Type your reply..." class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-950 placeholder-gray-400 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder-gray-500"></textarea>
                        <div class="mt-2 flex justify-end gap-2">
                            <button @click="replyTicket" :disabled="!replyMessage.trim()" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-50">
                                Send Reply
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { verificationModal } from '../../services/verificationModal';

export default {
    name: 'HelpSupport',

    setup() {
        const loading = ref(false);
        const creating = ref(false);
        const tickets = ref([]);
        const stats = ref(null);
        const showCreateModal = ref(false);
        const showViewModal = ref(false);
        const selectedTicket = ref(null);
        const replyMessage = ref('');

        const newTicket = ref({
            subject: '',
            category: '',
            priority: 'medium',
            message: '',
        });

        const formatDate = (date) => {
            if (!date) return 'N/A';
            return new Date(date).toLocaleString('en-US', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        };

        const getStatusClass = (status) => {
            const classes = {
                open: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                in_progress: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                resolved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                closed: 'bg-slate-100 text-slate-800 dark:bg-slate-700/30 dark:text-slate-300',
            };
            return classes[status] || classes.open;
        };

        const getPriorityClass = (priority) => {
            const classes = {
                low: 'bg-slate-100 text-slate-800 dark:bg-slate-700/30 dark:text-slate-300',
                medium: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                urgent: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            };
            return classes[priority] || classes.low;
        };

        const fetchTickets = async () => {
            loading.value = true;
            try {
                const response = await axios.get('/api/v1/support/tickets', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                tickets.value = response.data.data || [];
            } catch (error) {
                console.error('Error fetching tickets:', error);
            } finally {
                loading.value = false;
            }
        };

        const fetchStats = async () => {
            try {
                const response = await axios.get('/api/v1/support/stats', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                stats.value = response.data.data || null;
            } catch (error) {
                console.error('Error fetching stats:', error);
            }
        };

        const createTicket = async () => {
            creating.value = true;
            try {
                await axios.post('/api/v1/support/tickets', newTicket.value, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                showCreateModal.value = false;
                newTicket.value = { subject: '', category: '', priority: 'medium', message: '' };
                await fetchTickets();
                await fetchStats();
            } catch (error) {
                console.error('Error creating ticket:', error);
            } finally {
                creating.value = false;
            }
        };

        const viewTicket = (ticket) => {
            selectedTicket.value = ticket;
            replyMessage.value = '';
            showViewModal.value = true;
        };

        const replyTicket = async () => {
            if (!replyMessage.value.trim() || !selectedTicket.value) return;

            try {
                await axios.post(`/api/v1/support/tickets/${selectedTicket.value.id}/reply`, {
                    message: replyMessage.value
                }, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                replyMessage.value = '';
                await fetchTickets();
                // Update selected ticket
                const updated = tickets.value.find(t => t.id === selectedTicket.value.id);
                if (updated) {
                    selectedTicket.value = updated;
                }
            } catch (error) {
                console.error('Error replying:', error);
            }
        };

        const performCloseTicket = async (id) => {
            try {
                await axios.post(`/api/v1/support/tickets/${id}/close`, {}, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                await fetchTickets();
                await fetchStats();
            } catch (error) {
                console.error('Error closing ticket:', error);
            }
        };

        const closeTicket = (id) => {
            verificationModal.warning('Closing this ticket will stop further replies. You can still view its history.', {
                title: 'Close support ticket?',
                actions: [
                    { id: 'confirm_close', label: 'Close ticket', style: 'danger' },
                    { id: 'close', label: 'Keep open', style: 'secondary' },
                ],
            }, {
                confirm_close: () => performCloseTicket(id),
            });
        };

        onMounted(() => {
            fetchTickets();
            fetchStats();
        });

        return {
            loading,
            creating,
            tickets,
            stats,
            showCreateModal,
            showViewModal,
            selectedTicket,
            replyMessage,
            newTicket,
            formatDate,
            getStatusClass,
            getPriorityClass,
            createTicket,
            viewTicket,
            replyTicket,
            closeTicket,
        };
    },
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>