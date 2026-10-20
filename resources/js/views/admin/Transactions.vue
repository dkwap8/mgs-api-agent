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
                    Payment monitoring
                </p>

                <h2
                    class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl"
                >
                    Transactions
                </h2>

                <p
                    class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400"
                >
                    Monitor every platform transaction, payment collection,
                    payout, credit and debit from one place.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                :disabled="loading"
                @click="refreshPage"
            >
                <span>↻</span>

                {{ loading ? 'Refreshing…' : 'Refresh transactions' }}
            </button>
        </section>

        <!-- Statistics -->
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="summary in summaries"
                :key="summary.label"
                class="flex min-h-[130px] items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <span
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-xl"
                    :class="summary.iconClass"
                >
                    {{ summary.icon }}
                </span>

                <span class="min-w-0">
                    <span
                        class="block truncate text-2xl font-black text-slate-950 dark:text-white"
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

        <!-- Volume card -->
        <section
            class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-700 via-violet-700 to-purple-800 p-6 text-white shadow-2xl shadow-indigo-900/20"
        >
            <div
                class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full border-[48px] border-white/5"
            ></div>

            <div class="relative z-10">
                <div
                    class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div>
                        <p class="text-sm font-semibold text-indigo-100">
                            Total successful transaction volume
                        </p>

                        <p class="mt-2 text-4xl font-black sm:text-5xl">
                            ₦{{ formatCurrency(stats.total_volume) }}
                        </p>

                        <p class="mt-2 text-sm text-indigo-100">
                            Across all merchants and transaction channels
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[520px]">
                        <div
                            class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur"
                        >
                            <p
                                class="text-xs font-black uppercase tracking-wider text-indigo-200"
                            >
                                Today
                            </p>

                            <p class="mt-2 text-lg font-black">
                                ₦{{ formatCurrency(stats.today_volume) }}
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur"
                        >
                            <p
                                class="text-xs font-black uppercase tracking-wider text-indigo-200"
                            >
                                Credits
                            </p>

                            <p class="mt-2 text-lg font-black">
                                ₦{{ formatCurrency(stats.credit_volume) }}
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur"
                        >
                            <p
                                class="text-xs font-black uppercase tracking-wider text-indigo-200"
                            >
                                Debits
                            </p>

                            <p class="mt-2 text-lg font-black">
                                ₦{{ formatCurrency(stats.debit_volume) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Filters -->
        <section
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
        >
            <form
                class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_190px_190px_190px_auto]"
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
                        placeholder="Search reference, merchant, customer or description..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-950"
                    />
                </div>

                <select
                    v-model="filters.status"
                    class="transaction-input"
                >
                    <option value="">
                        All statuses
                    </option>

                    <option value="success">
                        Success
                    </option>

                    <option value="successful">
                        Successful
                    </option>

                    <option value="completed">
                        Completed
                    </option>

                    <option value="pending">
                        Pending
                    </option>

                    <option value="processing">
                        Processing
                    </option>

                    <option value="failed">
                        Failed
                    </option>

                    <option value="rejected">
                        Rejected
                    </option>

                    <option value="reversed">
                        Reversed
                    </option>
                </select>

                <select
                    v-model="filters.type"
                    class="transaction-input"
                >
                    <option value="">
                        All transaction types
                    </option>

                    <option value="credit">
                        Credit
                    </option>

                    <option value="debit">
                        Debit
                    </option>

                    <option value="collection">
                        Collection
                    </option>

                    <option value="deposit">
                        Deposit
                    </option>

                    <option value="payout">
                        Payout
                    </option>

                    <option value="withdrawal">
                        Withdrawal
                    </option>
                </select>

                <select
                    v-model="filters.period"
                    class="transaction-input"
                >
                    <option value="">
                        All dates
                    </option>

                    <option value="today">
                        Today
                    </option>

                    <option value="yesterday">
                        Yesterday
                    </option>

                    <option value="week">
                        This week
                    </option>

                    <option value="month">
                        This month
                    </option>
                </select>

                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white transition hover:bg-indigo-700"
                    >
                        Apply
                    </button>

                    <button
                        type="button"
                        class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                        @click="resetFilters"
                    >
                        Clear
                    </button>
                </div>
            </form>
        </section>

        <!-- Feedback -->
        <section
            v-if="errorMessage"
            class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
        >
            {{ errorMessage }}
        </section>

        <!-- Transaction table -->
        <section
            class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
        >
            <div
                class="flex flex-col gap-3 border-b border-slate-200 px-5 py-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h3 class="text-lg font-black">
                        Platform transactions
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Showing filtered transaction records.
                    </p>
                </div>

                <span
                    class="w-fit rounded-full bg-indigo-100 px-3 py-1 text-sm font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                >
                    {{ pagination.total }} records
                </span>
            </div>

            <div
                v-if="loading"
                class="py-20 text-center text-sm text-slate-500"
            >
                Loading transactions…
            </div>

            <div
                v-else-if="transactions.length === 0"
                class="px-6 py-20 text-center"
            >
                <span
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-100 text-3xl dark:bg-indigo-950"
                >
                    ↻
                </span>

                <h3 class="mt-5 text-xl font-black">
                    No transactions found
                </h3>

                <p class="mt-2 text-sm text-slate-500">
                    No transaction matches the selected filters.
                </p>

                <button
                    type="button"
                    class="mt-5 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white"
                    @click="resetFilters"
                >
                    Clear filters
                </button>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead
                        class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950/60"
                    >
                        <tr
                            class="text-left text-[11px] font-black uppercase tracking-wider text-slate-400"
                        >
                            <th class="px-5 py-4">
                                Transaction
                            </th>

                            <th class="px-5 py-4">
                                Merchant
                            </th>

                            <th class="px-5 py-4">
                                Customer
                            </th>

                            <th class="px-5 py-4">
                                Amount
                            </th>

                            <th class="px-5 py-4">
                                Type
                            </th>

                            <th class="px-5 py-4">
                                Status
                            </th>

                            <th class="px-5 py-4">
                                Date
                            </th>

                            <th class="px-5 py-4 text-right">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody
                        class="divide-y divide-slate-100 dark:divide-slate-800"
                    >
                        <tr
                            v-for="transaction in transactions"
                            :key="transaction.id || transaction.reference"
                            class="transition hover:bg-slate-50 dark:hover:bg-slate-800/40"
                        >
                            <td class="px-5 py-4">
                                <div class="flex min-w-[190px] items-center gap-3">
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                        :class="typeIconClass(transaction.type)"
                                    >
                                        {{ typeIcon(transaction.type) }}
                                    </span>

                                    <div class="min-w-0">
                                        <p
                                            class="max-w-[210px] truncate font-mono text-sm font-black text-slate-950 dark:text-white"
                                        >
                                            {{
                                                transaction.reference ||
                                                `TXN-${transaction.id}`
                                            }}
                                        </p>

                                        <p
                                            class="mt-1 max-w-[210px] truncate text-xs text-slate-500"
                                        >
                                            {{
                                                transaction.description ||
                                                'Platform transaction'
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <p
                                    class="max-w-[180px] truncate text-sm font-bold"
                                >
                                    {{
                                        transaction.merchant?.business_name ||
                                        'N/A'
                                    }}
                                </p>

                                <p
                                    class="mt-1 max-w-[180px] truncate text-xs text-slate-500"
                                >
                                    {{
                                        transaction.merchant?.business_email ||
                                        transaction.merchant?.user?.email ||
                                        'No email'
                                    }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <p
                                    class="max-w-[170px] truncate text-sm font-bold"
                                >
                                    {{ customerName(transaction.customer) }}
                                </p>

                                <p
                                    class="mt-1 max-w-[170px] truncate text-xs text-slate-500"
                                >
                                    {{
                                        transaction.customer?.email ||
                                        transaction.customer?.phone ||
                                        'No customer'
                                    }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <p
                                    class="whitespace-nowrap text-sm font-black text-slate-950 dark:text-white"
                                >
                                    ₦{{ formatCurrency(transaction.amount) }}
                                </p>

                                <p
                                    v-if="transaction.fee"
                                    class="mt-1 whitespace-nowrap text-xs text-slate-500"
                                >
                                    Fee:
                                    ₦{{ formatCurrency(transaction.fee) }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-black capitalize"
                                    :class="typeClass(transaction.type)"
                                >
                                    {{ transaction.type || 'transaction' }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-black capitalize"
                                    :class="statusClass(transaction.status)"
                                >
                                    {{ transaction.status || 'pending' }}
                                </span>
                            </td>

                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-slate-500"
                            >
                                {{ formatDate(transaction.created_at) }}
                            </td>

                            <td class="px-5 py-4 text-right">
                                <button
                                    type="button"
                                    class="rounded-xl bg-indigo-50 px-4 py-2 text-xs font-black text-indigo-700 transition hover:bg-indigo-100 dark:bg-indigo-950 dark:text-indigo-300"
                                    @click="openTransaction(transaction)"
                                >
                                    View
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="pagination.last_page > 1"
                class="flex flex-col items-center justify-between gap-4 border-t border-slate-200 px-5 py-4 dark:border-slate-800 sm:flex-row"
            >
                <p class="text-sm text-slate-500">
                    Showing {{ pagination.from || 0 }}–{{ pagination.to || 0 }}
                    of {{ pagination.total || 0 }}
                </p>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700"
                        :disabled="pagination.current_page <= 1"
                        @click="fetchTransactions(pagination.current_page - 1)"
                    >
                        Previous
                    </button>

                    <span class="px-3 text-sm font-bold">
                        {{ pagination.current_page }}
                        /
                        {{ pagination.last_page }}
                    </span>

                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700"
                        :disabled="
                            pagination.current_page >=
                            pagination.last_page
                        "
                        @click="fetchTransactions(pagination.current_page + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>
        </section>

        <!-- Transaction modal -->
        <Teleport to="body">
            <div
                v-if="modalOpen"
                class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-5"
                @click.self="closeModal"
            >
                <div
                    class="flex max-h-[95vh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 shadow-2xl dark:border-slate-700 dark:bg-slate-950"
                >
                    <header
                        class="flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-5 dark:border-slate-800 dark:bg-slate-900 sm:px-7"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-3">
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black capitalize"
                                    :class="
                                        statusClass(
                                            selectedTransaction.status
                                        )
                                    "
                                >
                                    {{
                                        selectedTransaction.status ||
                                        'pending'
                                    }}
                                </span>

                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black capitalize"
                                    :class="
                                        typeClass(
                                            selectedTransaction.type
                                        )
                                    "
                                >
                                    {{
                                        selectedTransaction.type ||
                                        'transaction'
                                    }}
                                </span>
                            </div>

                            <h3
                                class="mt-3 break-all font-mono text-xl font-black sm:text-2xl"
                            >
                                {{
                                    selectedTransaction.reference ||
                                    `TXN-${selectedTransaction.id}`
                                }}
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                {{
                                    formatDateTime(
                                        selectedTransaction.created_at
                                    )
                                }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-xl text-slate-500 transition hover:bg-slate-100 dark:hover:bg-slate-800"
                            @click="closeModal"
                        >
                            ✕
                        </button>
                    </header>

                    <div class="flex-1 overflow-y-auto p-4 sm:p-7">
                        <div
                            v-if="detailsLoading"
                            class="py-24 text-center text-slate-500"
                        >
                            Loading transaction details…
                        </div>

                        <div v-else class="space-y-6">
                            <!-- Amount -->
                            <section
                                class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 to-violet-700 p-6 text-white"
                            >
                                <div
                                    class="pointer-events-none absolute -right-14 -top-14 h-44 w-44 rounded-full border-[30px] border-white/5"
                                ></div>

                                <div class="relative z-10">
                                    <p class="text-sm font-semibold text-indigo-100">
                                        Transaction amount
                                    </p>

                                    <p class="mt-2 text-4xl font-black">
                                        ₦{{
                                            formatCurrency(
                                                selectedTransaction.amount
                                            )
                                        }}
                                    </p>

                                    <div
                                        class="mt-6 grid gap-3 sm:grid-cols-3"
                                    >
                                        <div
                                            class="rounded-2xl bg-white/10 p-4"
                                        >
                                            <p
                                                class="text-xs uppercase tracking-wider text-indigo-200"
                                            >
                                                Fee
                                            </p>

                                            <p class="mt-1 font-black">
                                                ₦{{
                                                    formatCurrency(
                                                        selectedTransaction.fee
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-2xl bg-white/10 p-4"
                                        >
                                            <p
                                                class="text-xs uppercase tracking-wider text-indigo-200"
                                            >
                                                Profit
                                            </p>

                                            <p class="mt-1 font-black">
                                                ₦{{
                                                    formatCurrency(
                                                        selectedTransaction.profit
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-2xl bg-white/10 p-4"
                                        >
                                            <p
                                                class="text-xs uppercase tracking-wider text-indigo-200"
                                            >
                                                Currency
                                            </p>

                                            <p class="mt-1 font-black">
                                                {{
                                                    selectedTransaction.currency ||
                                                    'NGN'
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Transaction -->
                            <section class="transaction-detail-card">
                                <h4 class="transaction-detail-heading">
                                    Transaction Information
                                </h4>

                                <div class="mt-5 grid gap-4 md:grid-cols-2">
                                    <DetailBox
                                        label="Reference"
                                        :value="
                                            selectedTransaction.reference
                                        "
                                    />

                                    <DetailBox
                                        label="External Reference"
                                        :value="
                                            selectedTransaction.external_reference ||
                                            selectedTransaction.provider_reference ||
                                            'Not provided'
                                        "
                                    />

                                    <DetailBox
                                        label="Type"
                                        :value="
                                            selectedTransaction.type
                                        "
                                    />

                                    <DetailBox
                                        label="Status"
                                        :value="
                                            selectedTransaction.status
                                        "
                                    />

                                    <DetailBox
                                        class="md:col-span-2"
                                        label="Description"
                                        :value="
                                            selectedTransaction.description ||
                                            'No description'
                                        "
                                    />
                                </div>
                            </section>

                            <section class="grid gap-5 lg:grid-cols-2">
                                <!-- Merchant -->
                                <article class="transaction-detail-card">
                                    <h4 class="transaction-detail-heading">
                                        Merchant
                                    </h4>

                                    <dl class="mt-5 space-y-4">
                                        <DetailRow
                                            label="Business"
                                            :value="
                                                selectedTransaction.merchant
                                                    ?.business_name
                                            "
                                        />

                                        <DetailRow
                                            label="Email"
                                            :value="
                                                selectedTransaction.merchant
                                                    ?.business_email ||
                                                selectedTransaction.merchant
                                                    ?.user?.email
                                            "
                                        />

                                        <DetailRow
                                            label="Phone"
                                            :value="
                                                selectedTransaction.merchant
                                                    ?.business_phone ||
                                                selectedTransaction.merchant
                                                    ?.user?.phone
                                            "
                                        />

                                        <DetailRow
                                            label="Merchant ID"
                                            :value="
                                                selectedTransaction.merchant_id
                                                    ? `#${selectedTransaction.merchant_id}`
                                                    : 'N/A'
                                            "
                                        />
                                    </dl>
                                </article>

                                <!-- Customer -->
                                <article class="transaction-detail-card">
                                    <h4 class="transaction-detail-heading">
                                        Customer
                                    </h4>

                                    <dl class="mt-5 space-y-4">
                                        <DetailRow
                                            label="Name"
                                            :value="
                                                customerName(
                                                    selectedTransaction.customer
                                                )
                                            "
                                        />

                                        <DetailRow
                                            label="Email"
                                            :value="
                                                selectedTransaction.customer
                                                    ?.email
                                            "
                                        />

                                        <DetailRow
                                            label="Phone"
                                            :value="
                                                selectedTransaction.customer
                                                    ?.phone
                                            "
                                        />

                                        <DetailRow
                                            label="Customer ID"
                                            :value="
                                                selectedTransaction.customer_id
                                                    ? `#${selectedTransaction.customer_id}`
                                                    : 'N/A'
                                            "
                                        />
                                    </dl>
                                </article>
                            </section>

                            <!-- Metadata -->
                            <section
                                v-if="
                                    selectedTransaction.metadata ||
                                    selectedTransaction.meta
                                "
                                class="transaction-detail-card"
                            >
                                <h4 class="transaction-detail-heading">
                                    Transaction Metadata
                                </h4>

                                <pre
                                    class="mt-5 max-h-80 overflow-auto rounded-2xl bg-slate-950 p-4 text-xs leading-6 text-slate-200"
                                >{{ formattedMetadata }}</pre>
                            </section>
                        </div>
                    </div>

                    <footer
                        class="border-t border-slate-200 bg-white px-5 py-4 text-right dark:border-slate-800 dark:bg-slate-900 sm:px-7"
                    >
                        <button
                            type="button"
                            class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-black text-white hover:bg-indigo-700"
                            @click="closeModal"
                        >
                            Close transaction
                        </button>
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

const DetailBox = defineComponent({
    name: 'DetailBox',

    inheritAttrs: false,

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

    setup(props, { attrs }) {
        return () =>
            h(
                'div',
                {
                    ...attrs,
                    class: [
                        'rounded-2xl bg-slate-50 p-4 dark:bg-slate-950',
                        attrs.class,
                    ],
                },
                [
                    h(
                        'p',
                        {
                            class:
                                'text-[11px] font-black uppercase tracking-wider text-slate-400',
                        },
                        props.label
                    ),

                    h(
                        'p',
                        {
                            class:
                                'mt-2 whitespace-pre-wrap break-words text-sm font-bold',
                        },
                        props.value || 'N/A'
                    ),
                ]
            );
    },
});

export default {
    name: 'AdminTransactions',

    components: {
        DetailRow,
        DetailBox,
    },

    setup() {
        const loading = ref(false);
        const detailsLoading = ref(false);
        const modalOpen = ref(false);

        const transactions = ref([]);
        const selectedTransaction = ref({});

        const stats = ref({
            total: 0,
            successful: 0,
            pending: 0,
            failed: 0,
            total_volume: 0,
            today_volume: 0,
            credit_volume: 0,
            debit_volume: 0,
        });

        const filters = ref({
            search: '',
            status: '',
            type: '',
            period: '',
        });

        const pagination = ref({
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0,
        });

        const errorMessage = ref('');

        const summaries = computed(() => [
            {
                label: 'Total Transactions',
                value: stats.value.total,
                icon: '↻',
                iconClass:
                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
            },
            {
                label: 'Successful',
                value: stats.value.successful,
                icon: '✓',
                iconClass:
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            },
            {
                label: 'Pending',
                value: stats.value.pending,
                icon: '◷',
                iconClass:
                    'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
            },
            {
                label: 'Failed',
                value: stats.value.failed,
                icon: '✕',
                iconClass:
                    'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
            },
        ]);

        const formattedMetadata = computed(() => {
            const metadata =
                selectedTransaction.value.metadata ||
                selectedTransaction.value.meta ||
                {};

            if (typeof metadata === 'string') {
                try {
                    return JSON.stringify(
                        JSON.parse(metadata),
                        null,
                        2
                    );
                } catch {
                    return metadata;
                }
            }

            return JSON.stringify(metadata, null, 2);
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

        const fetchTransactions = async (page = 1) => {
            loading.value = true;
            errorMessage.value = '';

            try {
                const response = await axios.get(
                    '/api/v1/admin/transactions',
                    {
                        params: buildParams(page),
                    }
                );

                const paginator = response.data?.data || {};

                transactions.value = Array.isArray(paginator.data)
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
                errorMessage.value =
                    error.response?.data?.message ||
                    'The transactions could not be loaded.';

                transactions.value = [];
            } finally {
                loading.value = false;
            }
        };

        const fetchStats = async () => {
            try {
                const response = await axios.get(
                    '/api/v1/admin/transactions/stats'
                );

                stats.value = {
                    ...stats.value,
                    ...(response.data?.data || {}),
                };
            } catch (error) {
                console.error(
                    'Transaction statistics failed:',
                    error.response?.data || error
                );
            }
        };

        const refreshPage = async () => {
            await Promise.all([
                fetchTransactions(pagination.value.current_page),
                fetchStats(),
            ]);
        };

        const applyFilters = async () => {
            await fetchTransactions(1);
        };

        const resetFilters = async () => {
            filters.value = {
                search: '',
                status: '',
                type: '',
                period: '',
            };

            await fetchTransactions(1);
        };

        const openTransaction = async (transaction) => {
            selectedTransaction.value = transaction;
            modalOpen.value = true;
            detailsLoading.value = true;
            errorMessage.value = '';

            document.body.style.overflow = 'hidden';

            try {
                const response = await axios.get(
                    `/api/v1/admin/transactions/${transaction.id}`
                );

                selectedTransaction.value =
                    response.data?.data || transaction;
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The transaction details could not be loaded.';
            } finally {
                detailsLoading.value = false;
            }
        };

        const closeModal = () => {
            modalOpen.value = false;
            selectedTransaction.value = {};
            document.body.style.overflow = '';
        };

        const customerName = (customer) => {
            if (!customer) {
                return 'No customer';
            }

            return [
                customer.first_name,
                customer.last_name,
            ]
                .filter(Boolean)
                .join(' ')
                .trim() || customer.name || 'Unnamed Customer';
        };

        const formatCurrency = (amount) => {
            return new Intl.NumberFormat('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(amount || 0));
        };

        const formatDate = (date) => {
            if (!date) {
                return 'N/A';
            }

            return new Date(date).toLocaleDateString('en-NG', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            });
        };

        const formatDateTime = (date) => {
            if (!date) {
                return 'N/A';
            }

            return new Date(date).toLocaleString('en-NG', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        };

        const normalizedStatus = (status) => {
            return String(status || '').toLowerCase();
        };

        const statusClass = (status) => {
            const value = normalizedStatus(status);

            if (
                [
                    'success',
                    'successful',
                    'completed',
                    'paid',
                    'approved',
                ].includes(value)
            ) {
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
            }

            if (
                [
                    'failed',
                    'rejected',
                    'cancelled',
                    'reversed',
                ].includes(value)
            ) {
                return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';
            }

            return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
        };

        const typeClass = (type) => {
            const value = String(type || '').toLowerCase();

            if (
                [
                    'credit',
                    'collection',
                    'deposit',
                ].includes(value)
            ) {
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
            }

            if (
                [
                    'debit',
                    'payout',
                    'withdrawal',
                ].includes(value)
            ) {
                return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';
            }

            return 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300';
        };

        const typeIconClass = (type) => {
            return typeClass(type);
        };

        const typeIcon = (type) => {
            const value = String(type || '').toLowerCase();

            if (
                [
                    'credit',
                    'collection',
                    'deposit',
                ].includes(value)
            ) {
                return '↓';
            }

            if (
                [
                    'debit',
                    'payout',
                    'withdrawal',
                ].includes(value)
            ) {
                return '↑';
            }

            return '↻';
        };

        const handleKeydown = (event) => {
            if (
                event.key === 'Escape' &&
                modalOpen.value
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
                fetchTransactions(),
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
            modalOpen,
            transactions,
            selectedTransaction,
            stats,
            filters,
            pagination,
            errorMessage,
            summaries,
            formattedMetadata,
            fetchTransactions,
            refreshPage,
            applyFilters,
            resetFilters,
            openTransaction,
            closeModal,
            customerName,
            formatCurrency,
            formatDate,
            formatDateTime,
            statusClass,
            typeClass,
            typeIconClass,
            typeIcon,
        };
    },
};
</script>

<style scoped>
.transaction-input {
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

.transaction-input:focus {
    border-color: rgb(99 102 241);
    box-shadow: 0 0 0 4px rgb(99 102 241 / 0.1);
}

.transaction-detail-card {
    border: 1px solid rgb(226 232 240);
    border-radius: 1.5rem;
    background: white;
    padding: 1.25rem;
}

.transaction-detail-heading {
    font-size: 1rem;
    font-weight: 900;
    color: rgb(15 23 42);
}

:global(.dark) .transaction-input {
    border-color: rgb(51 65 85);
    background: rgb(2 6 23);
    color: white;
}

:global(.dark) .transaction-detail-card {
    border-color: rgb(30 41 59);
    background: rgb(15 23 42);
}

:global(.dark) .transaction-detail-heading {
    color: white;
}
</style>