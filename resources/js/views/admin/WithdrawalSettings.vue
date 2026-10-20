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
                    Settlement controls
                </p>

                <h2
                    class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl"
                >
                    Withdrawal Settings
                </h2>

                <p
                    class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400"
                >
                    Configure withdrawal limits, automatic approval,
                    daily controls and fees independently for every
                    merchant business type.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
                    :disabled="loading"
                    @click="fetchSettings"
                >
                    {{ loading ? 'Refreshing…' : '↻ Refresh' }}
                </button>

                <button
                    type="button"
                    class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-black text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700"
                    @click="openCreate"
                >
                    ＋ Add Merchant Type
                </button>
            </div>
        </section>

        <!-- Information -->
        <section
            class="rounded-3xl border border-indigo-200 bg-indigo-50 p-5 dark:border-indigo-900/50 dark:bg-indigo-950/30"
        >
            <div class="flex items-start gap-4">
                <span
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 font-black text-white"
                >
                    ₦
                </span>

                <div>
                    <h3
                        class="font-black text-indigo-950 dark:text-indigo-200"
                    >
                        Automatic and manual processing
                    </h3>

                    <p
                        class="mt-1 text-sm leading-6 text-indigo-800 dark:text-indigo-300"
                    >
                        Requests within the configured automatic limit
                        can be processed automatically. Requests above
                        that amount remain pending for administrator
                        review.
                    </p>
                </div>
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
            class="grid gap-5 xl:grid-cols-2"
        >
            <article
                v-for="item in 2"
                :key="item"
                class="h-[560px] animate-pulse rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900"
            ></article>
        </section>

        <!-- Empty -->
        <section
            v-else-if="settings.length === 0"
            class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-20 text-center dark:border-slate-700 dark:bg-slate-900"
        >
            <h3 class="text-xl font-black">
                No withdrawal settings found
            </h3>

            <p class="mt-2 text-sm text-slate-500">
                Run the withdrawal settings seeder or create a merchant type.
            </p>
        </section>

        <!-- Setting cards -->
        <section
            v-else
            class="grid gap-5 xl:grid-cols-2"
        >
            <article
                v-for="setting in settings"
                :key="setting.id"
                class="flex flex-col rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400"
                        >
                            Merchant Type
                        </p>

                        <h3 class="mt-1 text-2xl font-black capitalize">
                            {{ merchantTypeLabel(setting.merchant_type) }}
                        </h3>
                    </div>

                    <span
                        class="rounded-full px-3 py-1 text-xs font-black"
                        :class="
                            setting.withdrawals_enabled
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'
                        "
                    >
                        {{
                            setting.withdrawals_enabled
                                ? 'Withdrawals Enabled'
                                : 'Withdrawals Disabled'
                        }}
                    </span>
                </div>

                <!-- Auto approval -->
                <div
                    class="mt-6 rounded-3xl bg-gradient-to-br from-indigo-600 to-violet-700 p-5 text-white"
                >
                    <div
                        class="flex items-start justify-between gap-4"
                    >
                        <div>
                            <p class="text-sm text-indigo-100">
                                Automatic Withdrawal Limit
                            </p>

                            <p class="mt-2 text-3xl font-black">
                                ₦{{
                                    formatCurrency(
                                        setting.automatic_withdrawal_limit
                                    )
                                }}
                            </p>
                        </div>

                        <span
                            class="rounded-full bg-white/15 px-3 py-1 text-xs font-black"
                        >
                            {{
                                setting.automatic_withdrawals_enabled
                                    ? 'Automatic ON'
                                    : 'Manual Only'
                            }}
                        </span>
                    </div>
                </div>

                <!-- Limits -->
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <MetricBox
                        label="Minimum Amount"
                        :value="
                            `₦${formatCurrency(
                                setting.minimum_withdrawal_amount
                            )}`
                        "
                    />

                    <MetricBox
                        label="Maximum Amount"
                        :value="
                            setting.maximum_withdrawal_amount !== null
                                ? `₦${formatCurrency(
                                      setting.maximum_withdrawal_amount
                                  )}`
                                : 'No Limit'
                        "
                    />

                    <MetricBox
                        label="Daily Limit"
                        :value="
                            setting.daily_withdrawal_limit !== null
                                ? `₦${formatCurrency(
                                      setting.daily_withdrawal_limit
                                  )}`
                                : 'No Limit'
                        "
                    />

                    <MetricBox
                        label="Daily Requests"
                        :value="
                            setting.daily_request_limit !== null
                                ? String(setting.daily_request_limit)
                                : 'No Limit'
                        "
                    />
                </div>

                <!-- Fee -->
                <div
                    class="mt-6 rounded-2xl border border-slate-200 p-5 dark:border-slate-700"
                >
                    <div
                        class="flex items-start justify-between gap-4"
                    >
                        <div>
                            <p class="font-black">
                                Withdrawal Charge
                            </p>

                            <p class="mt-1 text-sm text-slate-500">
                                {{
                                    setting.charge_enabled
                                        ? chargeDescription(setting)
                                        : 'No withdrawal charge is applied.'
                                }}
                            </p>
                        </div>

                        <span
                            class="rounded-full px-3 py-1 text-xs font-black"
                            :class="
                                setting.charge_enabled
                                    ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300'
                                    : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
                            "
                        >
                            {{
                                setting.charge_enabled
                                    ? 'Enabled'
                                    : 'Disabled'
                            }}
                        </span>
                    </div>

                    <div
                        v-if="setting.charge_enabled"
                        class="mt-4 grid gap-3 sm:grid-cols-3"
                    >
                        <MetricBox
                            label="Charge Type"
                            :value="
                                setting.charge_type === 'percentage'
                                    ? 'Percentage'
                                    : 'Fixed'
                            "
                        />

                        <MetricBox
                            label="Charge Cap"
                            :value="
                                setting.maximum_charge !== null
                                    ? `₦${formatCurrency(
                                          setting.maximum_charge
                                      )}`
                                    : 'No Cap'
                            "
                        />

                        <MetricBox
                            label="Collection"
                            :value="
                                setting.fee_collection_mode === 'add_on'
                                    ? 'Added to Debit'
                                    : 'Deducted'
                            "
                        />
                    </div>
                </div>

                <div
                    class="mt-auto grid grid-cols-2 gap-3 pt-6 sm:grid-cols-3"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
                        @click="openPreview(setting)"
                    >
                        Preview
                    </button>

                    <button
                        type="button"
                        class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-black text-indigo-700 hover:bg-indigo-100 dark:border-indigo-900 dark:bg-indigo-950 dark:text-indigo-300"
                        @click="toggleSetting(setting)"
                    >
                        {{
                            setting.withdrawals_enabled
                                ? 'Disable'
                                : 'Enable'
                        }}
                    </button>

                    <button
                        type="button"
                        class="col-span-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-black text-white hover:bg-indigo-700 sm:col-span-1"
                        @click="openEdit(setting)"
                    >
                        Edit Settings
                    </button>
                </div>
            </article>
        </section>

        <!-- Form modal -->
        <Teleport to="body">
            <div
                v-if="formOpen"
                class="fixed inset-0 z-[210] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-5"
                @click.self="closeForm"
            >
                <div
                    class="flex max-h-[95vh] w-full max-w-5xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 shadow-2xl dark:border-slate-700 dark:bg-slate-950"
                >
                    <header
                        class="flex items-start justify-between border-b border-slate-200 bg-white px-5 py-5 dark:border-slate-800 dark:bg-slate-900 sm:px-7"
                    >
                        <div>
                            <p
                                class="text-xs font-black uppercase tracking-wider text-indigo-600"
                            >
                                Withdrawal Policy
                            </p>

                            <h3 class="mt-1 text-2xl font-black">
                                {{
                                    editing
                                        ? 'Edit Withdrawal Settings'
                                        : 'Add Merchant Type'
                                }}
                            </h3>
                        </div>

                        <button
                            type="button"
                            class="flex h-11 w-11 items-center justify-center rounded-2xl text-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                            @click="closeForm"
                        >
                            ✕
                        </button>
                    </header>

                    <form
                        class="flex-1 overflow-y-auto p-4 sm:p-7"
                        @submit.prevent="saveSettings"
                    >
                        <div
                            v-if="formError"
                            class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                        >
                            {{ formError }}
                        </div>

                        <section class="settings-section">
                            <h4 class="settings-title">
                                Merchant Type
                            </h4>

                            <div class="mt-5 grid gap-5 md:grid-cols-2">
                                <FormField
                                    label="Merchant Type"
                                    :error="fieldErrors.merchant_type"
                                >
                                    <input
                                        v-model.trim="form.merchant_type"
                                        type="text"
                                        class="settings-input"
                                        placeholder="starter"
                                        :disabled="editing"
                                    />
                                </FormField>

                                <ToggleField
                                    v-model="form.withdrawals_enabled"
                                    title="Withdrawals Enabled"
                                    description="Allow this merchant type to request withdrawals."
                                />
                            </div>
                        </section>

                        <section class="settings-section mt-6">
                            <h4 class="settings-title">
                                Automatic Approval
                            </h4>

                            <div class="mt-5 grid gap-5 md:grid-cols-2">
                                <ToggleField
                                    v-model="
                                        form.automatic_withdrawals_enabled
                                    "
                                    title="Automatic Withdrawals"
                                    description="Automatically process eligible requests."
                                />

                                <FormField
                                    label="Automatic Withdrawal Limit"
                                    :error="
                                        fieldErrors.automatic_withdrawal_limit
                                    "
                                >
                                    <MoneyInput
                                        v-model="
                                            form.automatic_withdrawal_limit
                                        "
                                        :disabled="
                                            !form.automatic_withdrawals_enabled
                                        "
                                    />
                                </FormField>
                            </div>
                        </section>

                        <section class="settings-section mt-6">
                            <h4 class="settings-title">
                                Withdrawal Limits
                            </h4>

                            <div
                                class="mt-5 grid gap-5 md:grid-cols-2"
                            >
                                <FormField
                                    label="Minimum Withdrawal"
                                    :error="
                                        fieldErrors.minimum_withdrawal_amount
                                    "
                                >
                                    <MoneyInput
                                        v-model="
                                            form.minimum_withdrawal_amount
                                        "
                                    />
                                </FormField>

                                <FormField
                                    label="Maximum Withdrawal"
                                    :error="
                                        fieldErrors.maximum_withdrawal_amount
                                    "
                                >
                                    <MoneyInput
                                        v-model="
                                            form.maximum_withdrawal_amount
                                        "
                                        placeholder="Leave empty for no limit"
                                    />
                                </FormField>

                                <FormField
                                    label="Daily Withdrawal Limit"
                                    :error="
                                        fieldErrors.daily_withdrawal_limit
                                    "
                                >
                                    <MoneyInput
                                        v-model="
                                            form.daily_withdrawal_limit
                                        "
                                        placeholder="Leave empty for no limit"
                                    />
                                </FormField>

                                <FormField
                                    label="Daily Request Limit"
                                    :error="
                                        fieldErrors.daily_request_limit
                                    "
                                >
                                    <input
                                        v-model="
                                            form.daily_request_limit
                                        "
                                        type="number"
                                        min="1"
                                        class="settings-input"
                                        placeholder="Leave empty for no limit"
                                    />
                                </FormField>
                            </div>
                        </section>

                        <section class="settings-section mt-6">
                            <h4 class="settings-title">
                                Withdrawal Charge
                            </h4>

                            <div class="mt-5">
                                <ToggleField
                                    v-model="form.charge_enabled"
                                    title="Enable Withdrawal Charge"
                                    description="Apply a configurable fee to every withdrawal."
                                />
                            </div>

                            <div
                                v-if="form.charge_enabled"
                                class="mt-5 grid gap-5 md:grid-cols-2"
                            >
                                <FormField
                                    label="Charge Type"
                                    :error="fieldErrors.charge_type"
                                >
                                    <select
                                        v-model="form.charge_type"
                                        class="settings-input"
                                    >
                                        <option value="percentage">
                                            Percentage
                                        </option>

                                        <option value="fixed">
                                            Fixed Amount
                                        </option>
                                    </select>
                                </FormField>

                                <FormField
                                    v-if="
                                        form.charge_type ===
                                        'percentage'
                                    "
                                    label="Percentage"
                                    :error="fieldErrors.percentage"
                                >
                                    <div class="relative">
                                        <input
                                            v-model="
                                                form.percentage
                                            "
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.0001"
                                            class="settings-input pr-14"
                                        />

                                        <span
                                            class="absolute inset-y-0 right-4 flex items-center text-slate-400"
                                        >
                                            %
                                        </span>
                                    </div>
                                </FormField>

                                <FormField
                                    v-else
                                    label="Fixed Charge"
                                    :error="fieldErrors.fixed_amount"
                                >
                                    <MoneyInput
                                        v-model="form.fixed_amount"
                                    />
                                </FormField>

                                <FormField
                                    label="Maximum Charge Cap"
                                    :error="fieldErrors.maximum_charge"
                                >
                                    <MoneyInput
                                        v-model="form.maximum_charge"
                                        placeholder="Optional"
                                    />
                                </FormField>

                                <FormField
                                    label="Minimum Charge"
                                    :error="fieldErrors.minimum_charge"
                                >
                                    <MoneyInput
                                        v-model="form.minimum_charge"
                                        placeholder="Optional"
                                    />
                                </FormField>

                                <FormField
                                    label="Fee Collection Mode"
                                    :error="
                                        fieldErrors.fee_collection_mode
                                    "
                                >
                                    <select
                                        v-model="
                                            form.fee_collection_mode
                                        "
                                        class="settings-input"
                                    >
                                        <option value="add_on">
                                            Add fee to wallet debit
                                        </option>

                                        <option value="deduct">
                                            Deduct fee from payout
                                        </option>
                                    </select>
                                </FormField>
                            </div>
                        </section>

                        <div
                            class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
                        >
                            <button
                                type="button"
                                class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700"
                                :disabled="saving"
                                @click="closeForm"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-black text-white hover:bg-indigo-700 disabled:opacity-50"
                                :disabled="saving"
                            >
                                {{
                                    saving
                                        ? 'Saving…'
                                        : 'Save Withdrawal Settings'
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Preview modal -->
        <Teleport to="body">
            <div
                v-if="previewOpen"
                class="fixed inset-0 z-[220] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm"
                @click.self="closePreview"
            >
                <div
                    class="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
                >
                    <div
                        class="flex items-start justify-between gap-4"
                    >
                        <div>
                            <p
                                class="text-xs font-black uppercase tracking-wider text-indigo-600"
                            >
                                Fee Calculator
                            </p>

                            <h3 class="mt-1 text-2xl font-black">
                                Withdrawal Preview
                            </h3>
                        </div>

                        <button
                            type="button"
                            class="text-xl text-slate-500"
                            @click="closePreview"
                        >
                            ✕
                        </button>
                    </div>

                    <label class="mt-6 block">
                        <span class="mb-2 block text-sm font-black">
                            Withdrawal Amount
                        </span>

                        <MoneyInput
                            v-model="previewAmount"
                        />
                    </label>

                    <button
                        type="button"
                        class="mt-4 w-full rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white"
                        :disabled="previewing"
                        @click="calculatePreview"
                    >
                        {{
                            previewing
                                ? 'Calculating…'
                                : 'Calculate Preview'
                        }}
                    </button>

                    <div
                        v-if="previewResult"
                        class="mt-6 space-y-3 rounded-2xl bg-slate-50 p-5 dark:bg-slate-950"
                    >
                        <PreviewRow
                            label="Requested Amount"
                            :value="
                                `₦${formatCurrency(
                                    previewResult.amount
                                )}`
                            "
                        />

                        <PreviewRow
                            label="Withdrawal Fee"
                            :value="
                                `₦${formatCurrency(
                                    previewResult.fee
                                )}`
                            "
                        />

                        <PreviewRow
                            label="Wallet Debit"
                            :value="
                                `₦${formatCurrency(
                                    previewResult.wallet_debit
                                )}`
                            "
                        />

                        <PreviewRow
                            label="Merchant Receives"
                            :value="
                                `₦${formatCurrency(
                                    previewResult.net_amount
                                )}`
                            "
                        />

                        <PreviewRow
                            label="Processing"
                            :value="
                                previewResult.automatic
                                    ? 'Automatic'
                                    : 'Pending Admin Review'
                            "
                        />

                        <p
                            v-if="previewResult.review_reason"
                            class="rounded-xl bg-amber-50 p-3 text-sm text-amber-700 dark:bg-amber-950/30 dark:text-amber-300"
                        >
                            {{ previewResult.review_reason }}
                        </p>
                    </div>

                    <p
                        v-if="previewError"
                        class="mt-4 text-sm text-red-600"
                    >
                        {{ previewError }}
                    </p>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script>
import {
    defineComponent,
    h,
    onMounted,
    ref,
} from 'vue';

import axios from 'axios';

const FormField = defineComponent({
    props: {
        label: String,
        error: String,
    },

    setup(props, { slots }) {
        return () =>
            h('label', {}, [
                h(
                    'span',
                    {
                        class:
                            'mb-2 block text-sm font-black',
                    },
                    props.label
                ),

                slots.default?.(),

                props.error
                    ? h(
                          'span',
                          {
                              class:
                                  'mt-1 block text-sm text-red-600',
                          },
                          props.error
                      )
                    : null,
            ]);
    },
});

const ToggleField = defineComponent({
    props: {
        modelValue: Boolean,
        title: String,
        description: String,
    },

    emits: ['update:modelValue'],

    setup(props, { emit }) {
        return () =>
            h(
                'button',
                {
                    type: 'button',

                    class: [
                        'flex w-full items-center justify-between gap-4 rounded-2xl border p-4 text-left',
                        props.modelValue
                            ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/30'
                            : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-950',
                    ],

                    onClick: () =>
                        emit(
                            'update:modelValue',
                            !props.modelValue
                        ),
                },
                [
                    h('span', {}, [
                        h(
                            'span',
                            {
                                class:
                                    'block text-sm font-black',
                            },
                            props.title
                        ),

                        h(
                            'span',
                            {
                                class:
                                    'mt-1 block text-xs text-slate-500',
                            },
                            props.description
                        ),
                    ]),

                    h(
                        'span',
                        {
                            class: [
                                'relative h-6 w-11 rounded-full',
                                props.modelValue
                                    ? 'bg-indigo-600'
                                    : 'bg-slate-300 dark:bg-slate-700',
                            ],
                        },
                        [
                            h('span', {
                                class: [
                                    'absolute top-1 h-4 w-4 rounded-full bg-white transition',
                                    props.modelValue
                                        ? 'left-6'
                                        : 'left-1',
                                ],
                            }),
                        ]
                    ),
                ]
            );
    },
});

const MoneyInput = defineComponent({
    props: {
        modelValue: [
            String,
            Number,
        ],

        placeholder: {
            type: String,
            default: '0.00',
        },

        disabled: Boolean,
    },

    emits: ['update:modelValue'],

    setup(props, { emit }) {
        return () =>
            h(
                'div',
                {
                    class: 'relative',
                },
                [
                    h(
                        'span',
                        {
                            class:
                                'absolute inset-y-0 left-4 flex items-center font-black text-slate-400',
                        },
                        '₦'
                    ),

                    h('input', {
                        value: props.modelValue,
                        type: 'number',
                        min: 0,
                        step: '0.01',
                        disabled: props.disabled,
                        placeholder: props.placeholder,

                        class:
                            'settings-input pl-10 disabled:cursor-not-allowed disabled:opacity-50',

                        onInput: (event) =>
                            emit(
                                'update:modelValue',
                                event.target.value
                            ),
                    }),
                ]
            );
    },
});

const MetricBox = defineComponent({
    props: {
        label: String,
        value: [
            String,
            Number,
        ],
    },

    setup(props) {
        return () =>
            h(
                'div',
                {
                    class:
                        'rounded-2xl bg-slate-50 p-4 dark:bg-slate-950',
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
                                'mt-2 text-sm font-black',
                        },
                        props.value
                    ),
                ]
            );
    },
});

const PreviewRow = defineComponent({
    props: {
        label: String,
        value: [
            String,
            Number,
        ],
    },

    setup(props) {
        return () =>
            h(
                'div',
                {
                    class:
                        'flex justify-between gap-4 border-b border-slate-200 pb-3 last:border-0 dark:border-slate-800',
                },
                [
                    h(
                        'span',
                        {
                            class:
                                'text-sm text-slate-500',
                        },
                        props.label
                    ),

                    h(
                        'span',
                        {
                            class:
                                'text-right text-sm font-black',
                        },
                        props.value
                    ),
                ]
            );
    },
});

export default {
    name: 'AdminWithdrawalSettings',

    components: {
        FormField,
        ToggleField,
        MoneyInput,
        MetricBox,
        PreviewRow,
    },

    setup() {
        const loading = ref(false);
        const saving = ref(false);

        const settings = ref([]);

        const formOpen = ref(false);
        const editing = ref(false);
        const selectedSetting = ref(null);

        const successMessage = ref('');
        const errorMessage = ref('');
        const formError = ref('');
        const fieldErrors = ref({});

        const previewOpen = ref(false);
        const previewSetting = ref(null);
        const previewAmount = ref('');
        const previewResult = ref(null);
        const previewError = ref('');
        const previewing = ref(false);

        const emptyForm = () => ({
            merchant_type: '',
            withdrawals_enabled: true,
            automatic_withdrawals_enabled: false,
            automatic_withdrawal_limit: 0,
            minimum_withdrawal_amount: 100,
            maximum_withdrawal_amount: '',
            daily_withdrawal_limit: '',
            charge_enabled: false,
            charge_type: 'fixed',
            percentage: 0,
            fixed_amount: 0,
            maximum_charge: '',
            minimum_charge: '',
            fee_collection_mode: 'add_on',
            daily_request_limit: '',
        });

        const form = ref(emptyForm());

        const fetchSettings = async () => {
            loading.value = true;
            errorMessage.value = '';

            try {
                const response = await axios.get(
                    '/api/v1/admin/withdrawal-settings'
                );

                settings.value =
                    response.data?.data || [];
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'Withdrawal settings could not be loaded.';
            } finally {
                loading.value = false;
            }
        };

        const openCreate = () => {
            editing.value = false;
            selectedSetting.value = null;
            form.value = emptyForm();
            formError.value = '';
            fieldErrors.value = {};
            formOpen.value = true;
        };

        const openEdit = (setting) => {
            editing.value = true;
            selectedSetting.value = setting;

            form.value = {
                merchant_type: setting.merchant_type,
                withdrawals_enabled:
                    Boolean(setting.withdrawals_enabled),

                automatic_withdrawals_enabled:
                    Boolean(
                        setting.automatic_withdrawals_enabled
                    ),

                automatic_withdrawal_limit:
                    setting.automatic_withdrawal_limit,

                minimum_withdrawal_amount:
                    setting.minimum_withdrawal_amount,

                maximum_withdrawal_amount:
                    setting.maximum_withdrawal_amount ?? '',

                daily_withdrawal_limit:
                    setting.daily_withdrawal_limit ?? '',

                charge_enabled:
                    Boolean(setting.charge_enabled),

                charge_type:
                    setting.charge_type || 'fixed',

                percentage:
                    setting.percentage || 0,

                fixed_amount:
                    setting.fixed_amount || 0,

                maximum_charge:
                    setting.maximum_charge ?? '',

                minimum_charge:
                    setting.minimum_charge ?? '',

                fee_collection_mode:
                    setting.fee_collection_mode || 'add_on',

                daily_request_limit:
                    setting.daily_request_limit ?? '',
            };

            formError.value = '';
            fieldErrors.value = {};
            formOpen.value = true;
        };

        const closeForm = () => {
            if (saving.value) {
                return;
            }

            formOpen.value = false;
            editing.value = false;
            selectedSetting.value = null;
            form.value = emptyForm();
        };

        const saveSettings = async () => {
            saving.value = true;
            formError.value = '';
            fieldErrors.value = {};

            try {
                const payload = {
                    ...form.value,

                    automatic_withdrawal_limit:
                        Number(
                            form.value
                                .automatic_withdrawal_limit ||
                                0
                        ),

                    minimum_withdrawal_amount:
                        Number(
                            form.value
                                .minimum_withdrawal_amount ||
                                0
                        ),

                    maximum_withdrawal_amount:
                        nullableNumber(
                            form.value
                                .maximum_withdrawal_amount
                        ),

                    daily_withdrawal_limit:
                        nullableNumber(
                            form.value.daily_withdrawal_limit
                        ),

                    percentage:
                        Number(form.value.percentage || 0),

                    fixed_amount:
                        Number(form.value.fixed_amount || 0),

                    maximum_charge:
                        nullableNumber(
                            form.value.maximum_charge
                        ),

                    minimum_charge:
                        nullableNumber(
                            form.value.minimum_charge
                        ),

                    daily_request_limit:
                        nullableInteger(
                            form.value.daily_request_limit
                        ),
                };

                const response = editing.value
                    ? await axios.put(
                          `/api/v1/admin/withdrawal-settings/${selectedSetting.value.id}`,
                          payload
                      )
                    : await axios.post(
                          '/api/v1/admin/withdrawal-settings',
                          payload
                      );

                successMessage.value =
                    response.data?.message ||
                    'Withdrawal settings saved successfully.';

                closeForm();
                await fetchSettings();
            } catch (error) {
                formError.value =
                    error.response?.data?.message ||
                    'Withdrawal settings could not be saved.';

                fieldErrors.value =
                    normalizeErrors(
                        error.response?.data?.errors ||
                            {}
                    );
            } finally {
                saving.value = false;
            }
        };

        const toggleSetting = async (setting) => {
            try {
                const response = await axios.post(
                    `/api/v1/admin/withdrawal-settings/${setting.id}/toggle`
                );

                successMessage.value =
                    response.data?.message ||
                    'Withdrawal status updated.';

                await fetchSettings();
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'Withdrawal status could not be changed.';
            }
        };

        const openPreview = (setting) => {
            previewSetting.value = setting;
            previewAmount.value = '';
            previewResult.value = null;
            previewError.value = '';
            previewOpen.value = true;
        };

        const closePreview = () => {
            previewOpen.value = false;
            previewSetting.value = null;
            previewResult.value = null;
            previewError.value = '';
        };

        const calculatePreview = async () => {
            previewError.value = '';
            previewResult.value = null;

            const amount = Number(
                previewAmount.value || 0
            );

            if (amount <= 0) {
                previewError.value =
                    'Enter a valid withdrawal amount.';

                return;
            }

            previewing.value = true;

            try {
                const response = await axios.post(
                    '/api/v1/admin/withdrawal-settings/preview',
                    {
                        merchant_type:
                            previewSetting.value.merchant_type,

                        amount,
                        withdrawn_today: 0,
                        requests_today: 0,
                    }
                );

                previewResult.value =
                    response.data?.data || null;
            } catch (error) {
                previewError.value =
                    error.response?.data?.errors?.amount?.[0] ||
                    error.response?.data?.message ||
                    'The withdrawal preview could not be calculated.';
            } finally {
                previewing.value = false;
            }
        };

        const chargeDescription = (setting) => {
            if (setting.charge_type === 'percentage') {
                const cap =
                    setting.maximum_charge !== null
                        ? `, capped at ₦${formatCurrency(
                              setting.maximum_charge
                          )}`
                        : '';

                return `${Number(
                    setting.percentage || 0
                )}% of the withdrawal amount${cap}.`;
            }

            return `Fixed charge of ₦${formatCurrency(
                setting.fixed_amount
            )}.`;
        };

        const merchantTypeLabel = (value) => {
            return String(value || '')
                .replaceAll('_', ' ')
                .replaceAll('-', ' ')
                .replace(/\b\w/g, (letter) =>
                    letter.toUpperCase()
                );
        };

        const formatCurrency = (value) => {
            return new Intl.NumberFormat('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(value || 0));
        };

        const nullableNumber = (value) => {
            return value === '' ||
                value === null ||
                value === undefined
                ? null
                : Number(value);
        };

        const nullableInteger = (value) => {
            return value === '' ||
                value === null ||
                value === undefined
                ? null
                : Number.parseInt(value, 10);
        };

        const normalizeErrors = (errors) => {
            const result = {};

            Object.entries(errors).forEach(
                ([field, messages]) => {
                    result[field] = Array.isArray(messages)
                        ? messages[0]
                        : String(messages);
                }
            );

            return result;
        };

        onMounted(fetchSettings);

        return {
            loading,
            saving,
            settings,
            formOpen,
            editing,
            successMessage,
            errorMessage,
            formError,
            fieldErrors,
            form,
            previewOpen,
            previewAmount,
            previewResult,
            previewError,
            previewing,
            fetchSettings,
            openCreate,
            openEdit,
            closeForm,
            saveSettings,
            toggleSetting,
            openPreview,
            closePreview,
            calculatePreview,
            chargeDescription,
            merchantTypeLabel,
            formatCurrency,
        };
    },
};
</script>

<style scoped>
.settings-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid rgb(226 232 240);
    background: white;
    padding: 0.75rem 1rem;
    color: rgb(15 23 42);
    outline: none;
}

.settings-input:focus {
    border-color: rgb(99 102 241);
    box-shadow: 0 0 0 4px rgb(99 102 241 / 0.1);
}

.settings-section {
    border: 1px solid rgb(226 232 240);
    border-radius: 1.5rem;
    background: white;
    padding: 1.25rem;
}

.settings-title {
    font-size: 1rem;
    font-weight: 900;
}

:global(.dark) .settings-input {
    border-color: rgb(51 65 85);
    background: rgb(2 6 23);
    color: white;
}

:global(.dark) .settings-section {
    border-color: rgb(30 41 59);
    background: rgb(15 23 42);
}
</style>