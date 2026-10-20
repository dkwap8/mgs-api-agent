<template>
    <div
        class="min-h-screen bg-slate-50 text-slate-950 transition-colors duration-300 dark:bg-slate-950 dark:text-white"
    >
        <!-- Mobile overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-30 bg-slate-950/60 backdrop-blur-sm md:hidden"
            @click="sidebarOpen = false"
        ></div>

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-40 w-[280px] border-r border-slate-200 bg-white transition-transform duration-300 dark:border-slate-800 dark:bg-slate-950 md:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-full flex-col">
                <!-- Logo -->
                <div class="flex h-20 items-center justify-between px-5">
                    <router-link
                        to="/user/dashboard"
                        class="flex items-center gap-3"
                    >
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-700 text-sm font-black text-white shadow-lg shadow-indigo-500/20"
                        >
                            M
                        </span>

                        <div>
                            <p class="text-xl font-black tracking-tight text-indigo-600 dark:text-indigo-400">
                                MGS
                            </p>

                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">
                                Merchant
                            </p>
                        </div>
                    </router-link>

                    <button
                        type="button"
                        class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-900 md:hidden"
                        @click="sidebarOpen = false"
                    >
                        ✕
                    </button>
                </div>

                <!-- Business card -->
                <div class="px-4">
                    <div
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/70"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-100 text-xl dark:bg-indigo-950"
                            >
                                🏢
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold text-slate-950 dark:text-white">
                                    {{ currentBusiness.name }}
                                </p>

                                <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                    {{ user.name || 'Merchant' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 border-t border-slate-200 pt-3 dark:border-slate-800">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                Business code
                            </p>

                            <div class="mt-1 flex items-center justify-between gap-2">
                                <span class="truncate font-mono text-xs font-bold text-slate-700 dark:text-slate-300">
                                    {{ currentBusiness.code }}
                                </span>

                                <button
                                    type="button"
                                    class="rounded-lg p-1.5 text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-950"
                                    @click="copyBusinessCode"
                                >
                                    ⧉
                                </button>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
                            <span
                                class="h-2 w-2 rounded-full"
                                :class="statusDotClass"
                            ></span>

                            <span
                                class="text-xs font-semibold"
                                :class="statusTextClass"
                            >
                                {{ formatStatus(currentBusiness.status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="mt-6 flex-1 overflow-y-auto px-4 pb-5">
                    <NavigationSection
                        label="Overview"
                        :items="overviewItems"
                        @navigate="closeAllMenus"
                    />

                    <NavigationSection
                        label="Collect"
                        :items="collectItems"
                        @navigate="closeAllMenus"
                    />

                    <NavigationSection
                        label="Payouts"
                        :items="payoutItems"
                        @navigate="closeAllMenus"
                    />

                    <NavigationSection
                        label="Developer"
                        :items="developerItems"
                        @navigate="closeAllMenus"
                    />

                    <p class="mb-2 mt-6 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">
                        Account
                    </p>

                    <router-link
                        to="/user/settings"
                        class="sidebar-link"
                        active-class="sidebar-link-active"
                        @click="closeAllMenus"
                    >
                        <span>⚙️</span>
                        Profile & Settings
                    </router-link>

                    <router-link
                        to="/user/help"
                        class="sidebar-link"
                        active-class="sidebar-link-active"
                        @click="closeAllMenus"
                    >
                        <span>❓</span>
                        Help & Support
                    </router-link>
                </nav>

                <!-- Logout -->
                <div class="border-t border-slate-200 p-4 dark:border-slate-800">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                        @click="logout"
                    >
                        <span>↪</span>
                        Log out
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="min-h-screen md:ml-[280px]">
            <!-- Header -->
            <header
                class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/95"
            >
                <div class="flex min-h-20 items-center justify-between gap-4 px-4 py-3 sm:px-6">
                    <!-- Left -->
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <button
                            type="button"
                            class="rounded-xl p-2 text-xl text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-900 md:hidden"
                            @click="sidebarOpen = true"
                        >
                            ☰
                        </button>

                        <!-- Business switcher -->
                        <div
                            ref="businessRef"
                            class="relative min-w-0"
                        >
                            <button
                                type="button"
                                class="flex w-[230px] max-w-[55vw] items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left shadow-sm transition hover:border-indigo-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-indigo-700 dark:hover:bg-slate-800 sm:w-[290px]"
                                @click.stop="toggleBusinessMenu"
                            >
                                <span class="flex min-w-0 items-center gap-3">
                                    <span
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-sm dark:bg-indigo-950"
                                    >
                                        🏢
                                    </span>

                                    <span class="min-w-0">
                                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                            Business account
                                        </span>

                                        <span class="block truncate text-sm font-bold text-slate-800 dark:text-white">
                                            {{ currentBusiness.name }}
                                        </span>
                                    </span>
                                </span>

                                <span class="text-xs text-slate-400">
                                    ▾
                                </span>
                            </button>

                            <div
                                v-if="businessMenuOpen"
                                class="absolute left-0 top-[calc(100%+10px)] z-50 w-[310px] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10 dark:border-slate-700 dark:bg-slate-900"
                            >
                                <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                        Your businesses
                                    </p>
                                </div>

                                <div class="max-h-72 overflow-y-auto py-2">
                                    <button
                                        v-for="businessItem in businesses"
                                        :key="businessItem.id"
                                        type="button"
                                        class="flex w-full items-center justify-between gap-4 px-4 py-3 text-left transition hover:bg-slate-50 disabled:opacity-50 dark:hover:bg-slate-800"
                                        :disabled="switching"
                                        @click="selectBusiness(businessItem)"
                                    >
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-bold text-slate-900 dark:text-white">
                                                {{ businessItem.name }}
                                            </span>

                                            <span
                                                class="mt-0.5 block text-xs font-medium"
                                                :class="businessItem.verification_status === 'approved'
                                                    ? 'text-emerald-600 dark:text-emerald-400'
                                                    : businessItem.verification_status === 'rejected'
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : 'text-amber-600 dark:text-amber-400'"
                                            >
                                                {{ formatStatus(businessItem.verification_status) }}
                                            </span>
                                        </span>

                                        <span
                                            v-if="Number(businessItem.id) === Number(activeBusinessId)"
                                            class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400"
                                        >
                                            ✓
                                        </span>
                                    </button>

                                    <p
                                        v-if="!businesses.length"
                                        class="px-4 py-5 text-center text-sm text-slate-500"
                                    >
                                        No businesses found.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="flex w-full items-center gap-3 border-t border-slate-200 px-4 py-3 text-sm font-bold text-indigo-600 transition hover:bg-indigo-50 dark:border-slate-800 dark:text-indigo-400 dark:hover:bg-indigo-950/40"
                                    @click="openAddBusiness"
                                >
                                    <span class="text-lg">＋</span>
                                    Add business
                                </button>
                            </div>
                        </div>

                        <!-- Activation button -->
                        <router-link
                            v-if="currentBusiness.status !== 'approved'"
                            to="/user/activate"
                            class="hidden shrink-0 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:-translate-y-0.5 hover:shadow-xl sm:inline-flex"
                        >
                            Activate Your Business
                        </router-link>
                    </div>

                    <!-- Right: notification + profile only -->
                    <div class="flex items-center gap-3">
                        <!-- Notifications -->
                        <div
                            ref="notificationRef"
                            class="relative"
                        >
                            <button
                                type="button"
                                class="relative flex h-11 w-11 items-center justify-center rounded-2xl text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-900"
                                @click.stop="toggleNotifications"
                            >
                                <span class="text-xl">🔔</span>

                                <span
                                    v-if="notifications.length"
                                    class="absolute right-1.5 top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-black text-white"
                                >
                                    {{ notifications.length }}
                                </span>
                            </button>

                            <div
                                v-if="notificationOpen"
                                class="absolute right-0 top-[calc(100%+10px)] z-50 w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
                            >
                                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                                    <p class="font-bold">
                                        Notifications
                                    </p>

                                    <span class="text-xs text-slate-400">
                                        {{ notifications.length }} new
                                    </span>
                                </div>

                                <p v-if="notificationError" role="alert" class="p-3 text-sm text-red-600">{{ notificationError }}</p>
                                <p v-if="!notifications.length && !notificationError" class="p-3 text-sm">No unread notifications.</p>
                                <div
                                    v-for="note in notifications"
                                    :key="note.id"
                                    class="border-b border-slate-100 px-4 py-3 last:border-0 dark:border-slate-800"
                                >
                                    <p class="text-sm font-semibold">
                                        {{ note.title }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ note.time }}
                                        <button class="ml-2 text-indigo-600" @click="markNotificationRead(note.id)">Mark read</button>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Profile -->
                        <div
                            ref="profileRef"
                            class="relative"
                        >
                            <button
                                type="button"
                                class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-700 text-sm font-black text-white shadow-lg shadow-indigo-500/20"
                                @click.stop="toggleProfile"
                            >
                                {{ initials }}
                            </button>

                            <div
                                v-if="profileOpen"
                                class="absolute right-0 top-[calc(100%+10px)] z-50 w-72 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
                            >
                                <!-- User info -->
                                <div class="flex items-center gap-3 border-b border-slate-200 p-4 dark:border-slate-800">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-100 font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                                    >
                                        {{ initials }}
                                    </div>

                                    <div class="min-w-0">
                                        <p class="truncate font-bold">
                                            {{ user.name || 'Merchant' }}
                                        </p>

                                        <p class="truncate text-sm text-slate-500">
                                            {{ user.email || 'merchant@example.com' }}
                                        </p>
                                    </div>
                                </div>

                                <router-link
                                    to="/user/settings"
                                    class="flex items-center gap-3 px-4 py-3 text-sm font-semibold transition hover:bg-slate-50 dark:hover:bg-slate-800"
                                    @click="closeAllMenus"
                                >
                                    <span>⚙️</span>
                                    Settings
                                </router-link>

                                <!-- Theme inside dropdown -->
                                <div class="border-y border-slate-200 px-4 py-4 dark:border-slate-800">
                                    <p class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">
                                        Theme mode
                                    </p>

                                    <div class="grid grid-cols-3 gap-2">
                                        <button
                                            v-for="theme in themeOptions"
                                            :key="theme.value"
                                            type="button"
                                            class="rounded-xl border px-2 py-2 text-center text-xs font-semibold transition"
                                            :class="currentTheme === theme.value
                                                ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300'
                                                : 'border-slate-200 text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800'"
                                            @click="setTheme(theme.value)"
                                        >
                                            <span class="block text-base">
                                                {{ theme.icon }}
                                            </span>

                                            {{ theme.label }}
                                        </button>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm font-semibold text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30"
                                    @click="logout"
                                >
                                    <span>↪</span>
                                    Log out
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile activation -->
                <div
                    v-if="currentBusiness.status !== 'approved'"
                    class="border-t border-slate-200 px-4 py-3 dark:border-slate-800 sm:hidden"
                >
                    <router-link
                        to="/user/activate"
                        class="flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white"
                    >
                        Activate Your Business
                    </router-link>
                </div>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-7">
                <router-view />
            </main>
        </div>

        <!-- Add business modal -->
        <div
            v-if="addBusinessOpen"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-black">
                            Add a new business
                        </h3>

                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Create another business under your merchant account.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                        @click="closeAddBusiness"
                    >
                        ✕
                    </button>
                </div>

                <div class="mt-6">
                    <label class="mb-2 block text-sm font-bold">
                        Business name
                    </label>

                    <input
                        v-model="newBusinessName"
                        type="text"
                        maxlength="255"
                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-950"
                        placeholder="e.g. MGS Retail Limited"
                        @keyup.enter="submitAddBusiness"
                    />

                    <p
                        v-if="addBusinessError"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ addBusinessError }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-xl px-5 py-3 text-sm font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                        :disabled="creatingBusiness"
                        @click="closeAddBusiness"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-700 disabled:opacity-50"
                        :disabled="creatingBusiness"
                        @click="submitAddBusiness"
                    >
                        {{ creatingBusiness ? 'Creating…' : 'Create business' }}
                    </button>
                </div>
            </div>
        </div>
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
    watch,
} from 'vue';

import {
    RouterLink,
    useRoute,
} from 'vue-router';

import axios from 'axios';
import { verificationModal } from '../services/verificationModal';

import {
    createBusiness,
    fetchMerchantProfile,
    listBusinesses,
    switchBusiness as switchBusinessApi,
} from '../services/business';

const NavigationSection = defineComponent({
    name: 'NavigationSection',

    props: {
        label: {
            type: String,
            required: true,
        },

        items: {
            type: Array,
            required: true,
        },
    },

    emits: ['navigate'],

    setup(props, { emit }) {
        return () =>
            h('div', { class: 'mb-5' }, [
                h(
                    'p',
                    {
                        class:
                            'mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400',
                    },
                    props.label
                ),

                ...props.items.map((item) =>
                    h(
                        RouterLink,
                        {
                            to: item.to,
                            class: 'sidebar-link',
                            activeClass: 'sidebar-link-active',
                            onClick: () => emit('navigate'),
                        },
                        {
                            default: () => [
                                h('span', item.icon),
                                item.label,
                            ],
                        }
                    )
                ),
            ]);
    },
});

export default {
    name: 'MerchantLayout',

    components: {
        NavigationSection,
    },

    setup() {
        const route = useRoute();

        const sidebarOpen = ref(false);
        const businessMenuOpen = ref(false);
        const notificationOpen = ref(false);
        const profileOpen = ref(false);
        const addBusinessOpen = ref(false);

        const businessRef = ref(null);
        const notificationRef = ref(null);
        const profileRef = ref(null);

        const user = JSON.parse(localStorage.getItem('user') || '{}');

        const merchant = ref(
            JSON.parse(localStorage.getItem('merchant') || '{}')
        );

        const business = ref(
            JSON.parse(localStorage.getItem('business') || '{}')
        );

        const storedBusinesses = JSON.parse(
            localStorage.getItem('businesses') || '[]'
        );

        const businesses = ref(
            Array.isArray(storedBusinesses)
                ? storedBusinesses.filter(
                    (item) =>
                        item &&
                        Number(item.id) > 0 &&
                        Number(item.merchant_id) > 0
                )
                : []
        );

        const currentTheme = ref(
            localStorage.getItem('theme') || 'light'
        );

        const switching = ref(false);
        const creatingBusiness = ref(false);
        const newBusinessName = ref('');
        const addBusinessError = ref('');

        const notifications = ref([]);
        const notificationError = ref('');
        const loadNotifications = async () => {
            notificationError.value='';
            try { const {data}=await axios.get('/api/v1/notifications/unread'); notifications.value=data.data.items.map(n=>({...n,time:new Date(n.created_at).toLocaleString()})); }
            catch { notificationError.value='Notifications could not be loaded.'; }
        };
        const markNotificationRead = async (id) => {
            try { await axios.post('/api/v1/notifications/'+id+'/read'); await loadNotifications(); }
            catch { notificationError.value='Notification could not be marked read.'; }
        };

        const themeOptions = [
            {
                value: 'light',
                label: 'Light',
                icon: '☀️',
            },
            {
                value: 'dark',
                label: 'Dark',
                icon: '🌙',
            },
            {
                value: 'system',
                label: 'System',
                icon: '💻',
            },
        ];

        const activeBusinessId = computed(() =>
            Number(business.value?.id || 0)
        );

        const currentBusiness = computed(() => ({
            name:
                business.value?.name ||
                merchant.value?.business_name ||
                'Untitled Business',

            code:
                business.value?.business_code ||
                `MGS${String(business.value?.id || '').padStart(6, '0')}`,

            status:
                business.value?.verification_status ||
                'pending',
        }));

        const statusDotClass = computed(() => {
            if (currentBusiness.value.status === 'approved') {
                return 'bg-emerald-500';
            }

            if (currentBusiness.value.status === 'rejected') {
                return 'bg-red-500';
            }

            return 'bg-amber-500';
        });

        const statusTextClass = computed(() => {
            if (currentBusiness.value.status === 'approved') {
                return 'text-emerald-600 dark:text-emerald-400';
            }

            if (currentBusiness.value.status === 'rejected') {
                return 'text-red-600 dark:text-red-400';
            }

            return 'text-amber-600 dark:text-amber-400';
        });

        const initials = computed(() => {
            return (user.name || 'Merchant')
                .split(' ')
                .filter(Boolean)
                .map((word) => word.charAt(0))
                .join('')
                .slice(0, 2)
                .toUpperCase();
        });

        const overviewItems = [
            {
                to: '/user/dashboard',
                icon: '▦',
                label: 'Dashboard',
            },
            {
                to: '/user/analytics',
                icon: '◩',
                label: 'Analytics',
            },
        ];

        const collectItems = [
        {
                to: '/user/customers',
                icon: '♟',
                label: 'Customers',
        },
        {
                to: '/user/transactions',
                icon: '↻',
                label: 'Transactions',
        },
        {
                to: '/user/accounts',
                icon: '▰',
                label: 'Virtual Accounts',
        },
        
            ];
        const payoutItems = [
            {to:'/user/bank-accounts',icon:'?',label:'Bank Accounts'},
            {
                to: '/user/payouts',
                icon: '⇧',
                label: 'Payouts',
            },
        ];

        const developerItems = [
            {to:'/user/api-logs',icon:'?',label:'API Logs'},
            {
                to: '/user/api-keys',
                icon: '⚿',
                label: 'API Keys',
            },
            {
                to: '/user/webhooks',
                icon: '◎',
                label: 'Webhooks',
            },
            {
                to: '/user/documentation',
                icon: '▤',
                label: 'Documentation',
            },
        ];

        const formatStatus = (status) => {
            const labels = {
                pending: 'Pending activation',
                under_review: 'Under review',
                approved: 'Active',
                rejected: 'Rejected',
                suspended: 'Suspended',
            };

            return labels[status] || 'Pending activation';
        };

        const closeAllDropdowns = () => {
            businessMenuOpen.value = false;
            notificationOpen.value = false;
            profileOpen.value = false;
        };

        const closeAllMenus = () => {
            closeAllDropdowns();
            sidebarOpen.value = false;
        };

        const toggleBusinessMenu = () => {
            notificationOpen.value = false;
            profileOpen.value = false;
            businessMenuOpen.value = !businessMenuOpen.value;
        };

        const toggleNotifications = () => {
            businessMenuOpen.value = false;
            profileOpen.value = false;
            notificationOpen.value = !notificationOpen.value;
            if (notificationOpen.value) loadNotifications();
        };

        const toggleProfile = () => {
            businessMenuOpen.value = false;
            notificationOpen.value = false;
            profileOpen.value = !profileOpen.value;
        };

        const applyTheme = (theme) => {
            const root = document.documentElement;

            if (theme === 'dark') {
                root.classList.add('dark');
            } else if (theme === 'light') {
                root.classList.remove('dark');
            } else {
                root.classList.toggle(
                    'dark',
                    window.matchMedia('(prefers-color-scheme: dark)').matches
                );
            }

            currentTheme.value = theme;
        };

        const setTheme = (theme) => {
            localStorage.setItem('theme', theme);
            applyTheme(theme);
        };

        const handleOutsideClick = (event) => {
            const target = event.target;

            if (
                businessRef.value?.contains(target) ||
                notificationRef.value?.contains(target) ||
                profileRef.value?.contains(target)
            ) {
                return;
            }

            closeAllDropdowns();
        };

        const copyBusinessCode = async () => {
            try {
                await navigator.clipboard.writeText(
                    currentBusiness.value.code
                );
            } catch (error) {
                console.error('Could not copy business code:', error);
            }
        };

        const loadProfile = async () => {
            try {
                localStorage.removeItem('merchants');

                const data = await fetchMerchantProfile();

                merchant.value = data.current_merchant || {};
                business.value = data.current_business || {};

                let businessItems = Array.isArray(data.businesses)
                    ? data.businesses
                    : [];

                if (!businessItems.length) {
                    businessItems = await listBusinesses();
                }

                businesses.value = businessItems;
            } catch (error) {
                console.error(
                    'Failed to load merchant profile:',
                    error.response?.data || error
                );
            }
        };

        const selectBusiness = async (selectedBusiness) => {
            const selectedId = Number(selectedBusiness?.id);

            if (
                switching.value ||
                selectedId === Number(activeBusinessId.value)
            ) {
                closeAllDropdowns();
                return;
            }

            switching.value = true;

            try {
                const selected = await switchBusinessApi(selectedId);

                business.value = selected;

                closeAllDropdowns();

                window.location.href =
                    selected.verification_status === 'approved'
                        ? '/user/dashboard'
                        : '/user/activate';
            } catch (error) {
                // The global API interceptor presents the structured failure modal.
                console.error('Could not switch business:', error.response?.data || error);
            } finally {
                switching.value = false;
            }
        };

        const openAddBusiness = () => {
            closeAllDropdowns();
            newBusinessName.value = '';
            addBusinessError.value = '';
            addBusinessOpen.value = true;
        };

        const closeAddBusiness = () => {
            if (creatingBusiness.value) {
                return;
            }

            addBusinessOpen.value = false;
        };

        const submitAddBusiness = async () => {
            const name = newBusinessName.value.trim();

            if (!name) {
                addBusinessError.value = 'Business name is required.';
                return;
            }

            creatingBusiness.value = true;
            addBusinessError.value = '';

            try {
                const created = await createBusiness({ name });

                business.value = created;

                await loadProfile();

                window.location.href = '/user/activate';
            } catch (error) {
                addBusinessError.value =
                    error.response?.data?.errors?.name?.[0] ||
                    error.response?.data?.message ||
                    'Could not create business.';
            } finally {
                creatingBusiness.value = false;
            }
        };

        const logout = async () => {
            try {
                await axios.post('/api/v1/auth/logout');
            } catch (error) {
                console.error('Logout request failed:', error);
            } finally {
                [
                    'token',
                    'merchant_token',
                    'user',
                    'merchant',
                    'merchants',
                    'business',
                    'businesses',
                ].forEach((key) => localStorage.removeItem(key));

                window.location.href = '/user/login';
            }
        };

        watch(
            () => route.fullPath,
            closeAllMenus
        );

        onMounted(() => {
            document.addEventListener(
                'mousedown',
                handleOutsideClick,
                true
            );

            applyTheme(currentTheme.value);
            loadProfile();
        });

        onBeforeUnmount(() => {
            document.removeEventListener(
                'mousedown',
                handleOutsideClick,
                true
            );
        });

        return {
            sidebarOpen,
            businessMenuOpen,
            notificationOpen,
            profileOpen,
            addBusinessOpen,
            businessRef,
            notificationRef,
            profileRef,
            user,
            businesses,
            currentBusiness,
            activeBusinessId,
            statusDotClass,
            statusTextClass,
            initials,
            currentTheme,
            switching,
            creatingBusiness,
            newBusinessName,
            addBusinessError,
            notifications, notificationError, markNotificationRead,
            themeOptions,
            overviewItems,
            collectItems,
            payoutItems,
            developerItems,
            formatStatus,
            closeAllMenus,
            toggleBusinessMenu,
            toggleNotifications,
            toggleProfile,
            setTheme,
            copyBusinessCode,
            selectBusiness,
            openAddBusiness,
            closeAddBusiness,
            submitAddBusiness,
            logout,
        };
    },
};
</script>

<style>
.sidebar-link {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.25rem;
    border-radius: 0.85rem;
    padding: 0.75rem 0.85rem;
    font-size: 0.875rem;
    font-weight: 600;
    line-height: 1.25rem;
    color: rgb(71 85 105);
    text-decoration: none;
    transition:
        background-color 150ms ease,
        color 150ms ease,
        transform 150ms ease;
}

.sidebar-link > span {
    display: inline-flex;
    width: 1.25rem;
    min-width: 1.25rem;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    line-height: 1;
}

.sidebar-link:hover {
    background: rgb(248 250 252);
    color: rgb(79 70 229);
    transform: translateX(2px);
}

.sidebar-link-active {
    background: rgb(238 242 255);
    color: rgb(79 70 229);
}

.dark .sidebar-link {
    color: rgb(203 213 225);
}

.dark .sidebar-link:hover {
    background: rgb(15 23 42);
    color: rgb(165 180 252);
}

.dark .sidebar-link-active {
    background: rgb(49 46 129 / 0.3);
    color: rgb(165 180 252);
}
</style>
