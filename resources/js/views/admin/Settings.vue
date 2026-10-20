<template>
    <div class="space-y-6">
        <section
            class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
        >
            <div>
                <p
                    class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400"
                >
                    Platform configuration
                </p>

                <h1
                    class="mt-1 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl"
                >
                    System Settings
                </h1>

                <p
                    class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400"
                >
                    Configure general platform settings and control virtual
                    account issuance across every merchant.
                </p>
            </div>

            <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
                :disabled="loading"
                @click="loadSettings"
            >
                {{ loading ? 'Refreshing…' : '↻ Refresh' }}
            </button>
        </section>

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

        <form
            v-if="!loading"
            class="space-y-6"
            @submit.prevent="saveSettings"
        >
            <!-- General settings -->
            <section
                class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6"
            >
                <div>
                    <h2 class="text-lg font-black">
                        General Settings
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Basic platform identity and support information.
                    </p>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <label>
                        <span class="mb-2 block text-sm font-black">
                            Application Name
                        </span>

                        <input
                            v-model.trim="settings.app_name"
                            type="text"
                            maxlength="255"
                            class="settings-input"
                            placeholder="MGS API"
                        />
                    </label>

                    <label>
                        <span class="mb-2 block text-sm font-black">
                            Support Email
                        </span>

                        <input
                            v-model.trim="settings.support_email"
                            type="email"
                            maxlength="255"
                            class="settings-input"
                            placeholder="support@example.com"
                        />
                    </label>

                    <label>
                        <span class="mb-2 block text-sm font-black">
                            Support Phone
                        </span>

                        <input
                            v-model.trim="settings.support_phone"
                            type="text"
                            maxlength="50"
                            class="settings-input"
                            placeholder="08000000000"
                        />
                    </label>

                    <label>
                        <span class="mb-2 block text-sm font-black">
                            Default Charge
                        </span>

                        <input
                            v-model.number="settings.default_charge"
                            type="number"
                            min="0"
                            step="0.01"
                            class="settings-input"
                            placeholder="0.00"
                        />
                    </label>

                    <label>
                        <span class="mb-2 block text-sm font-black">
                            Default Limit
                        </span>

                        <input
                            v-model.number="settings.default_limit"
                            type="number"
                            min="0"
                            step="0.01"
                            class="settings-input"
                            placeholder="0.00"
                        />
                    </label>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <SettingToggle
                        v-model="settings.maintenance_mode"
                        title="Maintenance Mode"
                        description="Temporarily restrict normal platform access."
                    />

                    <SettingToggle
                        v-model="settings.merchant_auto_approval"
                        title="Merchant Auto Approval"
                        description="Automatically approve new merchant registrations where supported."
                    />
                </div>
            </section>

            <!-- Global virtual-account controls -->
            <section
                class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <p
                            class="text-xs font-black uppercase tracking-[0.16em] text-indigo-600 dark:text-indigo-400"
                        >
                            Compliance controls
                        </p>

                        <h2 class="mt-1 text-lg font-black">
                            Global Virtual Account Controls
                        </h2>

                        <p
                            class="mt-1 max-w-3xl text-sm leading-6 text-slate-500"
                        >
                            These controls apply across the platform and can
                            override individual merchant policies.
                        </p>
                    </div>

                    <span
                        class="rounded-full px-3 py-1.5 text-xs font-black"
                        :class="
                            settings.virtual_accounts_enabled
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'
                        "
                    >
                        {{
                            settings.virtual_accounts_enabled
                                ? 'Issuance Enabled'
                                : 'Issuance Disabled'
                        }}
                    </span>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <SettingToggle
                        v-model="settings.virtual_accounts_enabled"
                        title="Enable Virtual Accounts"
                        description="Allow the platform to create new virtual accounts. Customer registration will still work when disabled."
                    />

                    <SettingToggle
                        v-model="settings.enforce_virtual_account_policy"
                        title="Enforce System KYC Policy"
                        description="Override all merchant-level virtual account KYC requirements."
                    />

                    <SettingToggle
                        v-model="
                            settings.default_auto_generate_virtual_account
                        "
                        title="Default Auto Generation"
                        description="Default automatic account-generation behaviour for businesses."
                    />

                    <SettingToggle
                        v-model="
                            settings.default_allow_multiple_virtual_accounts
                        "
                        title="Default Multiple Accounts"
                        description="Default permission for customers to have multiple active accounts under one business."
                    />
                </div>

                <div
                    v-if="!settings.virtual_accounts_enabled"
                    class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm leading-6 text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
                >
                    New virtual account issuance is disabled platform-wide.
                    Customers can still be created and verified, but account
                    generation will be skipped.
                </div>

                <div
                    v-if="settings.enforce_virtual_account_policy"
                    class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300"
                >
                    The system KYC policy will override every merchant's saved
                    virtual account requirement.
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <label>
                        <span class="mb-2 block text-sm font-black">
                            System KYC Requirement
                        </span>

                        <select
                            v-model="
                                settings.virtual_account_requirement
                            "
                            class="settings-input"
                            :disabled="
                                !settings.enforce_virtual_account_policy
                            "
                        >
                            <option value="none">
                                No KYC Required
                            </option>

                            <option value="bvn">
                                BVN Verification Required
                            </option>

                            <option value="nin">
                                NIN Verification Required
                            </option>

                            <option value="bvn_or_nin">
                                BVN or NIN Verification Required
                            </option>

                            <option value="bvn_and_nin">
                                BVN and NIN Verification Required
                            </option>
                        </select>

                        <p class="mt-2 text-xs leading-5 text-slate-500">
                            {{
                                requirementDescription(
                                    settings.virtual_account_requirement
                                )
                            }}
                        </p>
                    </label>

                    <label>
                        <span class="mb-2 block text-sm font-black">
                            Maximum Active Virtual Accounts Per Customer
                        </span>

                        <select
                            v-model="
                                settings.maximum_virtual_accounts_per_customer
                            "
                            class="settings-input"
                        >
                            <option :value="null">
                                Unlimited
                            </option>

                            <option :value="1">
                                1 Account
                            </option>

                            <option :value="2">
                                2 Accounts
                            </option>

                            <option :value="3">
                                3 Accounts
                            </option>

                            <option :value="5">
                                5 Accounts
                            </option>

                            <option :value="10">
                                10 Accounts
                            </option>

                            <option value="custom">
                                Custom Limit
                            </option>
                        </select>

                        <input
                            v-if="
                                settings.maximum_virtual_accounts_per_customer
                                    === 'custom'
                            "
                            v-model.number="customMaximum"
                            type="number"
                            min="1"
                            max="1000"
                            class="settings-input mt-3"
                            placeholder="Enter custom limit"
                        />

                        <p class="mt-2 text-xs leading-5 text-slate-500">
                            This limit applies to active virtual accounts
                            attached to the same customer record.
                        </p>
                    </label>
                </div>

                <div
                    class="mt-6 rounded-2xl bg-slate-50 p-5 dark:bg-slate-950"
                >
                    <p
                        class="text-xs font-black uppercase tracking-wider text-slate-400"
                    >
                        Effective platform policy
                    </p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <SummaryItem
                            label="Virtual Accounts"
                            :value="
                                settings.virtual_accounts_enabled
                                    ? 'Enabled'
                                    : 'Disabled'
                            "
                        />

                        <SummaryItem
                            label="Policy Source"
                            :value="
                                settings.enforce_virtual_account_policy
                                    ? 'System Override'
                                    : 'Merchant Policy'
                            "
                        />

                        <SummaryItem
                            label="KYC Requirement"
                            :value="
                                requirementLabel(
                                    settings.virtual_account_requirement
                                )
                            "
                        />

                        <SummaryItem
                            label="Global Maximum"
                            :value="maximumLabel"
                        />
                    </div>
                </div>
            </section>

            <section
                class="flex flex-col gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="font-black">
                        Save system configuration
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Changes to enforced virtual-account policies take effect
                        immediately.
                    </p>
                </div>

                <button
                    type="submit"
                    class="rounded-2xl bg-indigo-600 px-7 py-3.5 text-sm font-black text-white hover:bg-indigo-700 disabled:opacity-50"
                    :disabled="saving"
                >
                    {{ saving ? 'Saving Settings…' : 'Save Settings' }}
                </button>
            </section>
        </form>

        <section
            v-else
            class="rounded-3xl border border-slate-200 bg-white py-24 text-center text-sm text-slate-500 dark:border-slate-800 dark:bg-slate-900"
        >
            Loading system settings…
        </section>
    </div>
</template>

<script>
import {
    computed,
    defineComponent,
    h,
    onMounted,
    ref,
} from 'vue';
import axios from 'axios';

const SettingToggle = defineComponent({
    props: {
        modelValue: Boolean,
        title: String,
        description: String,
    },

    emits: ['update:modelValue'],

    setup(props, { emit }) {
        return () =>
            h(
                'div',
                {
                    class:
                        'flex items-start justify-between gap-4 rounded-2xl border border-slate-200 p-4 dark:border-slate-700',
                },
                [
                    h('div', {}, [
                        h(
                            'p',
                            {
                                class: 'text-sm font-black',
                            },
                            props.title
                        ),

                        h(
                            'p',
                            {
                                class:
                                    'mt-1 text-xs leading-5 text-slate-500',
                            },
                            props.description
                        ),
                    ]),

                    h(
                        'button',
                        {
                            type: 'button',

                            class: [
                                'relative h-7 w-12 shrink-0 rounded-full transition',
                                props.modelValue
                                    ? 'bg-indigo-600'
                                    : 'bg-slate-300 dark:bg-slate-700',
                            ],

                            onClick: () =>
                                emit(
                                    'update:modelValue',
                                    !props.modelValue
                                ),
                        },
                        [
                            h('span', {
                                class: [
                                    'absolute top-1 h-5 w-5 rounded-full bg-white shadow transition',
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

const SummaryItem = defineComponent({
    props: {
        label: String,
        value: [String, Number],
    },

    setup(props) {
        return () =>
            h(
                'div',
                {
                    class:
                        'rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900',
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
                            class: 'mt-2 text-sm font-black',
                        },
                        props.value || 'N/A'
                    ),
                ]
            );
    },
});

export default {
    name: 'AdminSettings',

    components: {
        SettingToggle,
        SummaryItem,
    },

    setup() {
        const loading = ref(false);
        const saving = ref(false);

        const successMessage = ref('');
        const errorMessage = ref('');

        const customMaximum = ref(1);

        const settings = ref({
            app_name: 'MGS API',
            support_email: '',
            support_phone: '',

            maintenance_mode: false,
            merchant_auto_approval: false,

            virtual_accounts_enabled: true,
            enforce_virtual_account_policy: false,
            virtual_account_requirement: 'none',

            default_auto_generate_virtual_account: true,
            default_allow_multiple_virtual_accounts: false,

            maximum_virtual_accounts_per_customer: null,

            default_charge: 0,
            default_limit: 0,
        });

        const maximumLabel = computed(() => {
            const value =
                settings.value
                    .maximum_virtual_accounts_per_customer;

            if (value === null || value === '') {
                return 'Unlimited';
            }

            if (value === 'custom') {
                return `${customMaximum.value} Account(s)`;
            }

            return `${value} Account(s)`;
        });

        const loadSettings = async () => {
            loading.value = true;
            errorMessage.value = '';

            try {
                const response = await axios.get(
                    '/api/v1/admin/settings'
                );

                const data = response.data?.data || {};

                settings.value = {
                    ...settings.value,
                    ...data,

                    maintenance_mode:
                        Boolean(data.maintenance_mode),

                    merchant_auto_approval:
                        Boolean(data.merchant_auto_approval),

                    virtual_accounts_enabled:
                        data.virtual_accounts_enabled === undefined
                            ? true
                            : Boolean(
                                data.virtual_accounts_enabled
                            ),

                    enforce_virtual_account_policy:
                        Boolean(
                            data.enforce_virtual_account_policy
                        ),

                    default_auto_generate_virtual_account:
                        data.default_auto_generate_virtual_account
                            === undefined
                            ? true
                            : Boolean(
                                data.default_auto_generate_virtual_account
                            ),

                    default_allow_multiple_virtual_accounts:
                        Boolean(
                            data.default_allow_multiple_virtual_accounts
                        ),

                    maximum_virtual_accounts_per_customer:
                        data.maximum_virtual_accounts_per_customer
                            ?? null,

                    default_charge:
                        Number(data.default_charge || 0),

                    default_limit:
                        Number(data.default_limit || 0),
                };
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The system settings could not be loaded.';
            } finally {
                loading.value = false;
            }
        };

        const saveSettings = async () => {
            saving.value = true;
            successMessage.value = '';
            errorMessage.value = '';

            try {
                let maximum =
                    settings.value
                        .maximum_virtual_accounts_per_customer;

                if (maximum === 'custom') {
                    maximum = Number(customMaximum.value);
                }

                if (
                    maximum === '' ||
                    maximum === undefined
                ) {
                    maximum = null;
                }

                const payload = {
                    app_name:
                        settings.value.app_name,

                    support_email:
                        settings.value.support_email || null,

                    support_phone:
                        settings.value.support_phone || null,

                    maintenance_mode:
                        Boolean(
                            settings.value.maintenance_mode
                        ),

                    merchant_auto_approval:
                        Boolean(
                            settings.value
                                .merchant_auto_approval
                        ),

                    virtual_accounts_enabled:
                        Boolean(
                            settings.value
                                .virtual_accounts_enabled
                        ),

                    enforce_virtual_account_policy:
                        Boolean(
                            settings.value
                                .enforce_virtual_account_policy
                        ),

                    virtual_account_requirement:
                        settings.value
                            .virtual_account_requirement,

                    default_auto_generate_virtual_account:
                        Boolean(
                            settings.value
                                .default_auto_generate_virtual_account
                        ),

                    default_allow_multiple_virtual_accounts:
                        Boolean(
                            settings.value
                                .default_allow_multiple_virtual_accounts
                        ),

                    maximum_virtual_accounts_per_customer:
                        maximum,

                    default_charge:
                        Number(
                            settings.value.default_charge || 0
                        ),

                    default_limit:
                        Number(
                            settings.value.default_limit || 0
                        ),
                };

                const response = await axios.post(
                    '/api/v1/admin/settings',
                    payload
                );

                successMessage.value =
                    response.data?.message ||
                    'Settings updated successfully.';

                await loadSettings();
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    Object.values(
                        error.response?.data?.errors || {}
                    )?.[0]?.[0] ||
                    'The system settings could not be saved.';
            } finally {
                saving.value = false;
            }
        };

        const requirementLabel = (value) => {
            const labels = {
                none: 'No KYC Required',
                bvn: 'BVN Required',
                nin: 'NIN Required',
                bvn_or_nin: 'BVN or NIN Required',
                bvn_and_nin: 'BVN and NIN Required',
            };

            return labels[value] || 'Unknown';
        };

        const requirementDescription = (value) => {
            const descriptions = {
                none:
                    'Customers may receive virtual accounts without BVN or NIN verification.',

                bvn:
                    'A verified BVN is required before an account can be issued.',

                nin:
                    'A verified NIN is required before an account can be issued.',

                bvn_or_nin:
                    'Either a verified BVN or verified NIN is sufficient.',

                bvn_and_nin:
                    'Both BVN and NIN verification must succeed before issuance.',
            };

            return descriptions[value] || '';
        };

        onMounted(loadSettings);

        return {
            loading,
            saving,
            successMessage,
            errorMessage,
            customMaximum,
            settings,
            maximumLabel,
            loadSettings,
            saveSettings,
            requirementLabel,
            requirementDescription,
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
    transition:
        border-color 150ms ease,
        box-shadow 150ms ease;
}

.settings-input:focus {
    border-color: rgb(99 102 241);
    box-shadow: 0 0 0 4px rgb(99 102 241 / 0.1);
}

.settings-input:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

:global(.dark) .settings-input {
    border-color: rgb(51 65 85);
    background: rgb(2 6 23);
    color: white;
}
</style>