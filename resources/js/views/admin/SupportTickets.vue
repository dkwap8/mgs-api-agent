<template>
    <div class="space-y-6">
        <!-- Heading -->
        <section
            class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
        >
            <div>
                <p
                    class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400"
                >
                    Customer operations
                </p>

                <h2
                    class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl"
                >
                    Support Tickets
                </h2>

                <p
                    class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400"
                >
                    Review merchant enquiries, respond to support requests,
                    prioritize urgent issues and manage ticket resolution.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
                :disabled="loading"
                @click="refreshPage"
            >
                <span>↻</span>

                {{ loading ? 'Refreshing…' : 'Refresh tickets' }}
            </button>
        </section>

        <!-- Statistics -->
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="summary in summaries"
                :key="summary.label"
                class="flex min-h-[125px] items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <span
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-xl"
                    :class="summary.iconClass"
                >
                    {{ summary.icon }}
                </span>

                <span>
                    <span
                        class="block text-2xl font-black text-slate-950 dark:text-white"
                    >
                        {{ summary.value }}
                    </span>

                    <span
                        class="mt-1 block text-sm font-semibold text-slate-500"
                    >
                        {{ summary.label }}
                    </span>
                </span>
            </article>
        </section>

        <!-- Urgent alert -->
        <section
            v-if="stats.urgent > 0"
            class="flex flex-col justify-between gap-4 rounded-3xl border border-red-200 bg-red-50 p-5 dark:border-red-900/50 dark:bg-red-950/30 sm:flex-row sm:items-center"
        >
            <div class="flex items-start gap-4">
                <span
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-red-600 font-black text-white"
                >
                    !
                </span>

                <div>
                    <h3 class="font-black text-red-900 dark:text-red-200">
                        {{ stats.urgent }} urgent
                        {{ stats.urgent === 1 ? 'ticket requires' : 'tickets require' }}
                        attention
                    </h3>

                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                        Review urgent tickets before handling normal-priority requests.
                    </p>
                </div>
            </div>

            <button
                type="button"
                class="rounded-xl bg-red-600 px-5 py-3 text-sm font-black text-white hover:bg-red-700"
                @click="filterUrgent"
            >
                Show urgent tickets
            </button>
        </section>

        <!-- Filters -->
        <section
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
        >
            <form
                class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_210px_190px_auto]"
                @submit.prevent="applyFilters"
            >
                <div class="relative">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"
                    >
                        ⌕
                    </span>

                    <input
                        v-model="filters.search"
                        type="search"
                        placeholder="Search ticket number, subject, message, customer or merchant..."
                        class="support-input pl-11"
                    />
                </div>

                <select
                    v-model="filters.status"
                    class="support-input"
                >
                    <option value="">
                        All statuses
                    </option>

                    <option value="open">
                        Open
                    </option>

                    <option value="in_progress">
                        In Progress
                    </option>

                    <option value="resolved">
                        Resolved
                    </option>

                    <option value="closed">
                        Closed
                    </option>
                </select>

                <select
                    v-model="filters.priority"
                    class="support-input"
                >
                    <option value="">
                        All priorities
                    </option>

                    <option value="low">
                        Low
                    </option>

                    <option value="medium">
                        Medium
                    </option>

                    <option value="high">
                        High
                    </option>

                    <option value="urgent">
                        Urgent
                    </option>
                </select>

                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white hover:bg-indigo-700"
                    >
                        Apply
                    </button>

                    <button
                        type="button"
                        class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300"
                        @click="resetFilters"
                    >
                        Clear
                    </button>
                </div>
            </form>

            <div class="mt-5 flex flex-wrap gap-2">
                <button
                    v-for="option in quickStatusFilters"
                    :key="option.value"
                    type="button"
                    class="rounded-xl border px-4 py-2 text-sm font-black transition"
                    :class="filters.status === option.value
                        ? 'border-indigo-600 bg-indigo-600 text-white'
                        : 'border-slate-200 text-slate-600 hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:text-slate-300'"
                    @click="setStatus(option.value)"
                >
                    {{ option.label }}
                </button>
            </div>
        </section>

        <!-- Feedback -->
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

        <!-- Loading -->
        <section
            v-if="loading"
            class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="item in 6"
                :key="item"
                class="h-[370px] animate-pulse rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="h-12 w-12 rounded-2xl bg-slate-200 dark:bg-slate-800"
                ></div>

                <div
                    class="mt-5 h-5 w-2/3 rounded bg-slate-200 dark:bg-slate-800"
                ></div>

                <div
                    class="mt-3 h-4 w-1/2 rounded bg-slate-200 dark:bg-slate-800"
                ></div>
            </article>
        </section>

        <!-- Empty -->
        <section
            v-else-if="tickets.length === 0"
            class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-20 text-center dark:border-slate-700 dark:bg-slate-900"
        >
            <span
                class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-100 text-3xl dark:bg-indigo-950"
            >
                🎧
            </span>

            <h3 class="mt-5 text-xl font-black">
                No support tickets found
            </h3>

            <p class="mt-2 text-sm text-slate-500">
                No ticket matches the selected search or filters.
            </p>

            <button
                type="button"
                class="mt-5 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white"
                @click="resetFilters"
            >
                Clear filters
            </button>
        </section>

        <!-- Ticket cards -->
        <section
            v-else
            class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="ticket in tickets"
                :key="ticket.id"
                class="flex min-h-[390px] flex-col rounded-3xl border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-xl dark:bg-slate-900"
                :class="ticketBorderClass(ticket)"
            >
                <div class="flex items-start justify-between gap-4">
                    <span
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-xl"
                        :class="priorityIconClass(ticket.priority)"
                    >
                        {{ priorityIcon(ticket.priority) }}
                    </span>

                    <div class="flex flex-col items-end gap-2">
                        <span
                            class="rounded-full px-3 py-1 text-xs font-black"
                            :class="statusClass(ticket.status)"
                        >
                            {{ formatStatus(ticket.status) }}
                        </span>

                        <span
                            class="rounded-full px-3 py-1 text-xs font-black"
                            :class="priorityClass(ticket.priority)"
                        >
                            {{ formatPriority(ticket.priority) }}
                        </span>
                    </div>
                </div>

                <div class="mt-5">
                    <p
                        class="font-mono text-xs font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400"
                    >
                        {{ ticket.ticket_number || `TICKET-${ticket.id}` }}
                    </p>

                    <h3
                        class="mt-2 line-clamp-2 text-xl font-black text-slate-950 dark:text-white"
                    >
                        {{ ticket.subject || 'Support Request' }}
                    </h3>

                    <p
                        class="mt-3 line-clamp-3 text-sm leading-6 text-slate-500 dark:text-slate-400"
                    >
                        {{ firstMessage(ticket.message) }}
                    </p>
                </div>

                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            Customer
                        </dt>

                        <dd
                            class="max-w-[65%] truncate text-right font-bold"
                        >
                            {{ customerName(ticket) }}
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            Merchant
                        </dt>

                        <dd
                            class="max-w-[65%] truncate text-right font-bold"
                        >
                            {{ ticket.merchant?.business_name || 'N/A' }}
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            Created
                        </dt>

                        <dd class="text-right font-semibold">
                            {{ formatDateTime(ticket.created_at) }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-auto pt-6">
                    <button
                        type="button"
                        class="flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700"
                        @click="openTicket(ticket)"
                    >
                        Review Ticket
                    </button>
                </div>
            </article>
        </section>

        <!-- Pagination -->
        <section
            v-if="pagination.last_page > 1"
            class="flex flex-col items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900 sm:flex-row"
        >
            <p class="text-sm text-slate-500">
                Showing {{ pagination.from || 0 }}–{{ pagination.to || 0 }}
                of {{ pagination.total || 0 }} tickets
            </p>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold disabled:opacity-40 dark:border-slate-700"
                    :disabled="pagination.current_page <= 1"
                    @click="fetchTickets(pagination.current_page - 1)"
                >
                    Previous
                </button>

                <span class="px-3 text-sm font-black">
                    {{ pagination.current_page }}
                    /
                    {{ pagination.last_page }}
                </span>

                <button
                    type="button"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold disabled:opacity-40 dark:border-slate-700"
                    :disabled="pagination.current_page >= pagination.last_page"
                    @click="fetchTickets(pagination.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </section>

        <!-- Ticket modal -->
        <Teleport to="body">
            <div
                v-if="modalOpen"
                class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-5"
                @click.self="closeModal"
            >
                <div
                    class="flex max-h-[95vh] w-full max-w-5xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 shadow-2xl dark:border-slate-700 dark:bg-slate-950"
                >
                    <!-- Header -->
                    <header
                        class="flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-5 dark:border-slate-800 dark:bg-slate-900 sm:px-7"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap gap-2">
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black"
                                    :class="statusClass(selectedTicket.status)"
                                >
                                    {{ formatStatus(selectedTicket.status) }}
                                </span>

                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black"
                                    :class="priorityClass(selectedTicket.priority)"
                                >
                                    {{ formatPriority(selectedTicket.priority) }}
                                </span>
                            </div>

                            <p
                                class="mt-3 font-mono text-xs font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400"
                            >
                                {{
                                    selectedTicket.ticket_number ||
                                    `TICKET-${selectedTicket.id}`
                                }}
                            </p>

                            <h3
                                class="mt-1 max-w-3xl text-xl font-black sm:text-2xl"
                            >
                                {{ selectedTicket.subject }}
                            </h3>
                        </div>

                        <button
                            type="button"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                            @click="closeModal"
                        >
                            ✕
                        </button>
                    </header>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto p-4 sm:p-7">
                        <div
                            v-if="detailsLoading"
                            class="py-24 text-center text-slate-500"
                        >
                            Loading complete ticket…
                        </div>

                        <div v-else class="space-y-6">
                            <!-- Customer details -->
                            <section class="grid gap-5 lg:grid-cols-2">
                                <article class="support-detail-card">
                                    <h4 class="support-detail-heading">
                                        Customer Information
                                    </h4>

                                    <dl class="mt-5 space-y-4">
                                        <DetailRow
                                            label="Name"
                                            :value="customerName(selectedTicket)"
                                        />

                                        <DetailRow
                                            label="Email"
                                            :value="selectedTicket.user?.email"
                                        />

                                        <DetailRow
                                            label="Phone"
                                            :value="selectedTicket.user?.phone"
                                        />

                                        <DetailRow
                                            label="User ID"
                                            :value="selectedTicket.user_id
                                                ? `#${selectedTicket.user_id}`
                                                : 'N/A'"
                                        />
                                    </dl>
                                </article>

                                <article class="support-detail-card">
                                    <h4 class="support-detail-heading">
                                        Merchant Information
                                    </h4>

                                    <dl class="mt-5 space-y-4">
                                        <DetailRow
                                            label="Business"
                                            :value="selectedTicket.merchant?.business_name"
                                        />

                                        <DetailRow
                                            label="Email"
                                            :value="selectedTicket.merchant?.business_email"
                                        />

                                        <DetailRow
                                            label="Phone"
                                            :value="selectedTicket.merchant?.business_phone"
                                        />

                                        <DetailRow
                                            label="Created"
                                            :value="formatDateTime(selectedTicket.created_at)"
                                        />
                                    </dl>
                                </article>
                            </section>

                            <!-- Conversation -->
                            <section class="support-detail-card">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <h4 class="support-detail-heading">
                                            Conversation
                                        </h4>

                                        <p class="mt-1 text-sm text-slate-500">
                                            Original request and all administrator replies.
                                        </p>
                                    </div>

                                    <span
                                        class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                                    >
                                        {{ conversationMessages.length }}
                                        messages
                                    </span>
                                </div>

                                <div class="mt-6 space-y-4">
                                    <article
                                        v-for="(message, index) in conversationMessages"
                                        :key="`${message.type}-${index}`"
                                        class="flex"
                                        :class="message.type === 'admin'
                                            ? 'justify-end'
                                            : 'justify-start'"
                                    >
                                        <div
                                            class="max-w-[90%] rounded-2xl p-4 sm:max-w-[75%]"
                                            :class="message.type === 'admin'
                                                ? 'bg-indigo-600 text-white'
                                                : 'border border-slate-200 bg-slate-50 text-slate-800 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200'"
                                        >
                                            <div
                                                class="mb-2 flex items-center justify-between gap-4"
                                            >
                                                <span
                                                    class="text-xs font-black uppercase tracking-wider"
                                                    :class="message.type === 'admin'
                                                        ? 'text-indigo-100'
                                                        : 'text-slate-400'"
                                                >
                                                    {{
                                                        message.type === 'admin'
                                                            ? 'MGS Support'
                                                            : customerName(selectedTicket)
                                                    }}
                                                </span>

                                                <span
                                                    v-if="message.date"
                                                    class="text-[10px]"
                                                    :class="message.type === 'admin'
                                                        ? 'text-indigo-200'
                                                        : 'text-slate-400'"
                                                >
                                                    {{ message.date }}
                                                </span>
                                            </div>

                                            <p
                                                class="whitespace-pre-wrap text-sm leading-6"
                                            >
                                                {{ message.message }}
                                            </p>
                                        </div>
                                    </article>
                                </div>
                            </section>

                            <!-- Reply -->
                            <section
                                v-if="selectedTicket.status !== 'closed'"
                                class="support-detail-card"
                            >
                                <h4 class="support-detail-heading">
                                    Reply to Ticket
                                </h4>

                                <textarea
                                    v-model="replyMessage"
                                    rows="6"
                                    maxlength="5000"
                                    placeholder="Write a clear response to the customer..."
                                    class="support-input mt-5 resize-none"
                                ></textarea>

                                <div class="mt-2 flex justify-between gap-4">
                                    <p class="text-sm text-red-600">
                                        {{ replyError }}
                                    </p>

                                    <p class="text-xs text-slate-400">
                                        {{ replyMessage.length }}/5000
                                    </p>
                                </div>

                                <label
                                    class="mt-5 flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950"
                                >
                                    <input
                                        v-model="markResolvedAfterReply"
                                        type="checkbox"
                                        class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600"
                                    />

                                    <span>
                                        <span class="block text-sm font-black">
                                            Mark ticket as resolved
                                        </span>

                                        <span class="mt-1 block text-xs leading-5 text-slate-500">
                                            The reply will be sent and the ticket status
                                            will change to resolved.
                                        </span>
                                    </span>
                                </label>

                                <button
                                    type="button"
                                    class="mt-5 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-black text-white hover:bg-indigo-700 disabled:opacity-50"
                                    :disabled="processing"
                                    @click="sendReply"
                                >
                                    {{
                                        processing
                                            ? 'Sending Reply…'
                                            : 'Send Reply'
                                    }}
                                </button>
                            </section>
                        </div>
                    </div>

                    <!-- Actions -->
                    <footer
                        class="border-t border-slate-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900 sm:px-7"
                    >
                        <div
                            class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <button
                                type="button"
                                class="rounded-xl px-5 py-3 text-sm font-black text-slate-600 hover:bg-slate-100 dark:text-slate-300"
                                :disabled="processing"
                                @click="closeModal"
                            >
                                Close Window
                            </button>

                            <div class="flex flex-col gap-3 sm:flex-row">
                                <button
                                    v-if="[
                                        'resolved',
                                        'closed',
                                    ].includes(selectedTicket.status)"
                                    type="button"
                                    class="rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-3 text-sm font-black text-indigo-700 hover:bg-indigo-100 disabled:opacity-50 dark:border-indigo-900 dark:bg-indigo-950 dark:text-indigo-300"
                                    :disabled="processing"
                                    @click="updateTicketStatus('reopen')"
                                >
                                    Reopen Ticket
                                </button>

                                <button
                                    v-if="![
                                        'resolved',
                                        'closed',
                                    ].includes(selectedTicket.status)"
                                    type="button"
                                    class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-black text-emerald-700 hover:bg-emerald-100 disabled:opacity-50 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300"
                                    :disabled="processing"
                                    @click="updateTicketStatus('resolve')"
                                >
                                    Mark Resolved
                                </button>

                                <button
                                    v-if="selectedTicket.status !== 'closed'"
                                    type="button"
                                    class="rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-black text-red-700 hover:bg-red-100 disabled:opacity-50 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
                                    :disabled="processing"
                                    @click="updateTicketStatus('close')"
                                >
                                    Close Ticket
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
    name: 'DetailRow',

    props: {
        label: {
            type: String,
            required: true,
        },

        value: {
            type: [String, Number],
            default: 'N/A',
        },
    },

    setup(props) {
        return () =>
            h(
                'div',
                {
                    class:
                        'flex items-start justify-between gap-4 border-b border-slate-100 pb-3 last:border-0 dark:border-slate-800',
                },
                [
                    h(
                        'dt',
                        {
                            class: 'text-sm text-slate-500',
                        },
                        props.label
                    ),

                    h(
                        'dd',
                        {
                            class:
                                'max-w-[65%] break-words text-right text-sm font-bold',
                        },
                        props.value || 'N/A'
                    ),
                ]
            );
    },
});

export default {
    name: 'AdminSupportTickets',

    components: {
        DetailRow,
    },

    setup() {
        const loading = ref(false);
        const detailsLoading = ref(false);
        const processing = ref(false);
        const modalOpen = ref(false);

        const tickets = ref([]);
        const selectedTicket = ref({});

        const stats = ref({
            total: 0,
            open: 0,
            in_progress: 0,
            resolved: 0,
            closed: 0,
            urgent: 0,
            high_priority: 0,
        });

        const filters = ref({
            search: '',
            status: '',
            priority: '',
        });

        const pagination = ref({
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0,
        });

        const replyMessage = ref('');
        const replyError = ref('');
        const markResolvedAfterReply = ref(false);

        const successMessage = ref('');
        const errorMessage = ref('');

        const quickStatusFilters = [
            {
                value: '',
                label: 'All Tickets',
            },
            {
                value: 'open',
                label: 'Open',
            },
            {
                value: 'in_progress',
                label: 'In Progress',
            },
            {
                value: 'resolved',
                label: 'Resolved',
            },
            {
                value: 'closed',
                label: 'Closed',
            },
        ];

        const summaries = computed(() => [
            {
                label: 'Total Tickets',
                value: stats.value.total,
                icon: '🎧',
                iconClass:
                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
            },
            {
                label: 'Open',
                value: stats.value.open,
                icon: '○',
                iconClass:
                    'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
            },
            {
                label: 'In Progress',
                value: stats.value.in_progress,
                icon: '◷',
                iconClass:
                    'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
            },
            {
                label: 'Resolved',
                value: stats.value.resolved,
                icon: '✓',
                iconClass:
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            },
        ]);

        const conversationMessages = computed(() => {
            return parseConversation(
                selectedTicket.value?.message || ''
            );
        });

        const buildParams = (page = 1) => {
            const params = {
                page,
                per_page: 20,
            };

            Object.entries(filters.value).forEach(
                ([key, value]) => {
                    if (String(value || '').trim()) {
                        params[key] = String(value).trim();
                    }
                }
            );

            return params;
        };

        const fetchTickets = async (page = 1) => {
            loading.value = true;
            errorMessage.value = '';
            successMessage.value = '';

            try {
                const response = await axios.get(
                    '/api/v1/admin/support',
                    {
                        params: buildParams(page),
                    }
                );

                const paginator = response.data?.data || {};

                tickets.value = Array.isArray(paginator.data)
                    ? paginator.data
                    : [];

                pagination.value = {
                    current_page:
                        Number(paginator.current_page || 1),

                    last_page:
                        Number(paginator.last_page || 1),

                    from:
                        Number(paginator.from || 0),

                    to:
                        Number(paginator.to || 0),

                    total:
                        Number(paginator.total || 0),
                };
            } catch (error) {
                tickets.value = [];

                errorMessage.value =
                    error.response?.data?.message ||
                    'The support tickets could not be loaded.';
            } finally {
                loading.value = false;
            }
        };

        const fetchStats = async () => {
            try {
                const response = await axios.get(
                    '/api/v1/admin/support/stats'
                );

                stats.value = {
                    ...stats.value,
                    ...(response.data?.data || {}),
                };
            } catch (error) {
                console.error(
                    'Support statistics failed:',
                    error.response?.data || error
                );
            }
        };

        const refreshPage = async () => {
            await Promise.all([
                fetchTickets(pagination.value.current_page),
                fetchStats(),
            ]);
        };

        const applyFilters = async () => {
            await fetchTickets(1);
        };

        const resetFilters = async () => {
            filters.value = {
                search: '',
                status: '',
                priority: '',
            };

            await fetchTickets(1);
        };

        const setStatus = async (status) => {
            filters.value.status = status;
            await fetchTickets(1);
        };

        const filterUrgent = async () => {
            filters.value.priority = 'urgent';
            filters.value.status = '';
            await fetchTickets(1);
        };

        const openTicket = async (ticket) => {
            selectedTicket.value = ticket;
            modalOpen.value = true;
            detailsLoading.value = true;
            replyMessage.value = '';
            replyError.value = '';
            markResolvedAfterReply.value = false;
            errorMessage.value = '';

            document.body.style.overflow = 'hidden';

            try {
                const response = await axios.get(
                    `/api/v1/admin/support/${ticket.id}`
                );

                selectedTicket.value =
                    response.data?.data || ticket;
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The complete ticket could not be loaded.';
            } finally {
                detailsLoading.value = false;
            }
        };

        const closeModal = () => {
            if (processing.value) {
                return;
            }

            modalOpen.value = false;
            selectedTicket.value = {};
            replyMessage.value = '';
            replyError.value = '';
            markResolvedAfterReply.value = false;

            document.body.style.overflow = '';
        };

        const sendReply = async () => {
            const message = replyMessage.value.trim();

            replyError.value = '';

            if (message.length < 2) {
                replyError.value =
                    'Please enter a reply containing at least 2 characters.';

                return;
            }

            processing.value = true;
            errorMessage.value = '';
            successMessage.value = '';

            try {
                const response = await axios.post(
                    `/api/v1/admin/support/${selectedTicket.value.id}/reply`,
                    {
                        message,
                        mark_resolved:
                            markResolvedAfterReply.value,
                    }
                );

                selectedTicket.value =
                    response.data?.data ||
                    selectedTicket.value;

                successMessage.value =
                    response.data?.message ||
                    'Reply sent successfully.';

                replyMessage.value = '';
                markResolvedAfterReply.value = false;

                await Promise.all([
                    fetchTickets(
                        pagination.value.current_page
                    ),
                    fetchStats(),
                ]);
            } catch (error) {
                replyError.value =
                    error.response?.data?.errors?.message?.[0] ||
                    error.response?.data?.message ||
                    'The reply could not be sent.';
            } finally {
                processing.value = false;
            }
        };

        const updateTicketStatus = async (action) => {
            const ticket = selectedTicket.value;

            if (!ticket?.id || processing.value) {
                return;
            }

            const confirmations = {
                resolve:
                    `Mark ${ticket.ticket_number || 'this ticket'} as resolved?`,

                close:
                    `Close ${ticket.ticket_number || 'this ticket'}? The customer will no longer receive replies on it.`,

                reopen:
                    `Reopen ${ticket.ticket_number || 'this ticket'}?`,
            };

            const confirmed = await verificationModal.confirm({
                title: 'Confirm ticket action',
                message: confirmations[action],
                confirmText: action === 'close' ? 'Close ticket' : 'Continue',
                danger: action === 'close',
            });
            if (!confirmed) return;

            processing.value = true;
            errorMessage.value = '';
            successMessage.value = '';

            try {
                const response = await axios.post(
                    `/api/v1/admin/support/${ticket.id}/${action}`
                );

                selectedTicket.value =
                    response.data?.data ||
                    selectedTicket.value;

                successMessage.value =
                    response.data?.message ||
                    'Ticket status updated.';

                await Promise.all([
                    fetchTickets(
                        pagination.value.current_page
                    ),
                    fetchStats(),
                ]);
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The ticket status could not be updated.';
            } finally {
                processing.value = false;
            }
        };

        const parseConversation = (message) => {
            const raw = String(message || '').trim();

            if (!raw) {
                return [];
            }

            const marker =
                /\n\n--- ADMIN REPLY(?: \[([^\]]+)\])? ---\n/g;

            const messages = [];
            let lastIndex = 0;
            let match;
            let isFirst = true;

            while ((match = marker.exec(raw)) !== null) {
                const content = raw
                    .slice(lastIndex, match.index)
                    .trim();

                if (content) {
                    messages.push({
                        type: isFirst
                            ? 'customer'
                            : 'admin',

                        message: content,
                        date: null,
                    });
                }

                lastIndex = marker.lastIndex;
                isFirst = false;

                const nextMatchIndex = raw
                    .slice(lastIndex)
                    .search(marker);

                const endIndex = nextMatchIndex === -1
                    ? raw.length
                    : lastIndex + nextMatchIndex;

                const reply = raw
                    .slice(lastIndex, endIndex)
                    .trim();

                if (reply) {
                    messages.push({
                        type: 'admin',
                        message: reply,
                        date: match[1] || null,
                    });
                }

                lastIndex = endIndex;

                if (nextMatchIndex === -1) {
                    break;
                }

                marker.lastIndex = lastIndex;
            }

            if (messages.length === 0) {
                return [
                    {
                        type: 'customer',
                        message: raw,
                        date: null,
                    },
                ];
            }

            return messages;
        };

        const firstMessage = (message) => {
            return parseConversation(message)[0]?.message ||
                'No message supplied.';
        };

        const customerName = (ticket) => {
            return (
                ticket?.user?.name ||
                ticket?.merchant?.user?.name ||
                'Unknown Customer'
            );
        };

        const formatStatus = (status) => {
            const labels = {
                open: 'Open',
                in_progress: 'In Progress',
                resolved: 'Resolved',
                closed: 'Closed',
            };

            return labels[status] || status || 'Open';
        };

        const statusClass = (status) => {
            if (status === 'resolved') {
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
            }

            if (status === 'closed') {
                return 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
            }

            if (status === 'in_progress') {
                return 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300';
            }

            return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
        };

        const formatPriority = (priority) => {
            const labels = {
                low: 'Low Priority',
                medium: 'Medium Priority',
                high: 'High Priority',
                urgent: 'Urgent',
            };

            return labels[priority] ||
                priority ||
                'Medium Priority';
        };

        const priorityClass = (priority) => {
            if (priority === 'urgent') {
                return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';
            }

            if (priority === 'high') {
                return 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300';
            }

            if (priority === 'low') {
                return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
            }

            return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
        };

        const priorityIconClass = (priority) => {
            return priorityClass(priority);
        };

        const priorityIcon = (priority) => {
            if (priority === 'urgent') {
                return '!';
            }

            if (priority === 'high') {
                return '↑';
            }

            if (priority === 'low') {
                return '↓';
            }

            return '•';
        };

        const ticketBorderClass = (ticket) => {
            if (
                ticket.priority === 'urgent' &&
                ![
                    'resolved',
                    'closed',
                ].includes(ticket.status)
            ) {
                return 'border-red-300 dark:border-red-900';
            }

            return 'border-slate-200 hover:border-indigo-200 dark:border-slate-800 dark:hover:border-indigo-900';
        };

        const formatDateTime = (value) => {
            if (!value) {
                return 'N/A';
            }

            return new Date(value).toLocaleString(
                'en-NG',
                {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                }
            );
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
            window.addEventListener(
                'keydown',
                handleKeydown
            );

            await Promise.all([
                fetchTickets(),
                fetchStats(),
            ]);
        });

        onBeforeUnmount(() => {
            window.removeEventListener(
                'keydown',
                handleKeydown
            );

            document.body.style.overflow = '';
        });

        return {
            loading,
            detailsLoading,
            processing,
            modalOpen,
            tickets,
            selectedTicket,
            stats,
            filters,
            pagination,
            replyMessage,
            replyError,
            markResolvedAfterReply,
            successMessage,
            errorMessage,
            quickStatusFilters,
            summaries,
            conversationMessages,
            fetchTickets,
            refreshPage,
            applyFilters,
            resetFilters,
            setStatus,
            filterUrgent,
            openTicket,
            closeModal,
            sendReply,
            updateTicketStatus,
            firstMessage,
            customerName,
            formatStatus,
            statusClass,
            formatPriority,
            priorityClass,
            priorityIconClass,
            priorityIcon,
            ticketBorderClass,
            formatDateTime,
        };
    },
};
</script>

<style scoped>
.support-input {
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

.support-input:focus {
    border-color: rgb(99 102 241);
    box-shadow: 0 0 0 4px rgb(99 102 241 / 0.1);
}

.support-detail-card {
    border: 1px solid rgb(226 232 240);
    border-radius: 1.5rem;
    background: white;
    padding: 1.25rem;
}

.support-detail-heading {
    font-size: 1rem;
    font-weight: 900;
    color: rgb(15 23 42);
}

:global(.dark) .support-input {
    border-color: rgb(51 65 85);
    background: rgb(2 6 23);
    color: white;
}

:global(.dark) .support-detail-card {
    border-color: rgb(30 41 59);
    background: rgb(15 23 42);
}

:global(.dark) .support-detail-heading {
    color: white;
}
</style>