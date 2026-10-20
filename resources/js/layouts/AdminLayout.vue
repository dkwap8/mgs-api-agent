<template>
    <div
        class="min-h-screen bg-slate-50 text-slate-950 transition-colors duration-300 dark:bg-slate-950 dark:text-white"
    >
        <!-- Mobile sidebar overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-[280px] flex-col border-r border-slate-200 bg-white transition-transform duration-300 dark:border-slate-800 dark:bg-slate-950 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Logo -->
            <div
                class="flex h-20 items-center justify-between border-b border-slate-200 px-5 dark:border-slate-800"
            >
                <router-link
                    to="/admin-panel/dashboard"
                    class="flex items-center gap-3"
                    @click="closeSidebar"
                >
                    <span
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-700 text-base font-black text-white shadow-lg shadow-indigo-500/20"
                    >
                        M
                    </span>

                    <div>
                        <p
                            class="text-xl font-black tracking-tight text-slate-950 dark:text-white"
                        >
                            MGS Admin
                        </p>

                        <p
                            class="text-[10px] font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400"
                        >
                            Control Centre
                        </p>
                    </div>
                </router-link>

                <button
                    type="button"
                    class="rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 dark:hover:bg-slate-900 lg:hidden"
                    aria-label="Close navigation"
                    @click="sidebarOpen = false"
                >
                    ✕
                </button>
            </div>

            <!-- Admin profile summary -->
            <div class="px-4 pt-5">
                <div
                    class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/70"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-700 text-sm font-black text-white"
                        >
                            {{ initials }}
                        </div>

                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-bold text-slate-950 dark:text-white"
                            >
                                {{ user.name || 'Administrator' }}
                            </p>

                            <p
                                class="truncate text-xs text-slate-500 dark:text-slate-400"
                            >
                                {{ user.email || 'admin@mgs.com' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <span
                            class="h-2 w-2 rounded-full bg-emerald-500"
                        ></span>

                        <span
                            class="text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            Administrator online
                        </span>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="mt-5 flex-1 overflow-y-auto px-4 pb-5">
                <div
                    v-for="section in navigationSections"
                    :key="section.label"
                    class="mb-6"
                >
                    <p
                        class="mb-2 px-3 text-[10px] font-black uppercase tracking-[0.18em] text-slate-400"
                    >
                        {{ section.label }}
                    </p>

                    <router-link
                        v-for="item in section.items"
                        :key="item.to"
                        :to="item.to"
                        class="admin-nav-link"
                        active-class="admin-nav-link-active"
                        :exact-active-class="
                            item.to === '/admin-panel/dashboard'
                                ? 'admin-nav-link-active'
                                : ''
                        "
                        @click="closeSidebar"
                    >
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sm transition group-hover:bg-indigo-100 dark:bg-slate-900 dark:group-hover:bg-indigo-950"
                        >
                            {{ item.icon }}
                        </span>

                        <span class="min-w-0 flex-1 truncate">
                            {{ item.label }}
                        </span>

                        <span
                            v-if="item.badge"
                            class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-black text-red-700 dark:bg-red-950 dark:text-red-300"
                        >
                            {{ item.badge }}
                        </span>
                    </router-link>
                </div>
            </nav>

            <!-- Sidebar footer -->
            <div
                class="border-t border-slate-200 p-4 dark:border-slate-800"
            >
                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-sm font-bold text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30"
                    @click="logout"
                >
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-red-50 dark:bg-red-950/40"
                    >
                        ↪
                    </span>

                    Log out
                </button>
            </div>
        </aside>

        <!-- Main application -->
        <div class="min-h-screen lg:ml-[280px]">
            <!-- Header -->
            <header
                class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/95"
            >
                <div
                    class="flex min-h-20 items-center justify-between gap-4 px-4 py-3 sm:px-6"
                >
                    <!-- Header left -->
                    <div class="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            class="rounded-xl p-2 text-xl text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-900 lg:hidden"
                            aria-label="Open navigation"
                            @click="sidebarOpen = true"
                        >
                            ☰
                        </button>

                        <div class="min-w-0">
                            <p
                                class="text-xs font-black uppercase tracking-[0.17em] text-indigo-600 dark:text-indigo-400"
                            >
                                Administration
                            </p>

                            <h1
                                class="truncate text-xl font-black tracking-tight text-slate-950 dark:text-white"
                            >
                                {{ pageTitle }}
                            </h1>
                        </div>
                    </div>

                    <!-- Header right -->
                    <div class="flex items-center gap-2 sm:gap-3">
                        <!-- Global search -->
                        <div
                            class="relative hidden xl:block"
                        >
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"
                            >
                                ⌕
                            </span>

                            <input
                                v-model="globalSearch"
                                type="search"
                                placeholder="Search admin panel..."
                                class="w-64 rounded-2xl border border-slate-200 bg-slate-50 py-2.5 pl-11 pr-4 text-sm outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-800 dark:bg-slate-900 dark:focus:bg-slate-950"
                                @keyup.enter="performSearch"
                            />
                        </div>

                        <!-- Notification dropdown -->
                        <div
                            ref="notificationRef"
                            class="relative"
                        >
                            <button
                                type="button"
                                class="relative flex h-11 w-11 items-center justify-center rounded-2xl text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-900"
                                aria-label="Notifications"
                                @click.stop="toggleNotifications"
                            >
                                <span class="text-xl">🔔</span>

                                <span
                                    v-if="unreadNotifications > 0"
                                    class="absolute right-1 top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-black text-white"
                                >
                                    {{
                                        unreadNotifications > 9
                                            ? '9+'
                                            : unreadNotifications
                                    }}
                                </span>
                            </button>

                            <div
                                v-if="notificationOpen"
                                class="absolute right-0 top-[calc(100%+10px)] z-50 w-[340px] max-w-[90vw] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10 dark:border-slate-700 dark:bg-slate-900"
                            >
                                <div
                                    class="flex items-center justify-between border-b border-slate-200 px-4 py-4 dark:border-slate-800"
                                >
                                    <div>
                                        <p class="font-black">
                                            Notifications
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            Platform updates requiring attention
                                        </p>
                                    </div>

                                    <button
                                        v-if="unreadNotifications"
                                        type="button"
                                        class="text-xs font-bold text-indigo-600 hover:underline dark:text-indigo-400"
                                        @click="markAllNotificationsRead"
                                    >
                                        Mark all read
                                    </button>
                                </div>

                                <div class="max-h-80 overflow-y-auto">
                                    <button
                                        v-for="notification in notifications"
                                        :key="notification.id"
                                        type="button"
                                        class="flex w-full items-start gap-3 border-b border-slate-100 px-4 py-4 text-left transition last:border-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800"
                                        @click="openNotification(notification)"
                                    >
                                        <span
                                            class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                                            :class="
                                                notification.read
                                                    ? 'bg-slate-100 dark:bg-slate-800'
                                                    : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300'
                                            "
                                        >
                                            {{ notification.icon }}
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span
                                                class="block text-sm font-bold text-slate-950 dark:text-white"
                                            >
                                                {{ notification.title }}
                                            </span>

                                            <span
                                                class="mt-1 block text-xs leading-5 text-slate-500 dark:text-slate-400"
                                            >
                                                {{ notification.message }}
                                            </span>

                                            <span
                                                class="mt-1 block text-[11px] font-semibold text-slate-400"
                                            >
                                                {{ notification.time }}
                                            </span>
                                        </span>

                                        <span
                                            v-if="!notification.read"
                                            class="mt-2 h-2 w-2 shrink-0 rounded-full bg-indigo-600"
                                        ></span>
                                    </button>

                                    <p
                                        v-if="notifications.length === 0"
                                        class="px-4 py-10 text-center text-sm text-slate-500"
                                    >
                                        {{
                                            notificationsLoading
                                                ? 'Loading notifications…'
                                                : 'No unread notifications.'
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Profile dropdown -->
                        <div
                            ref="profileRef"
                            class="relative"
                        >
                            <button
                                type="button"
                                class="flex items-center gap-2 rounded-2xl p-1.5 transition hover:bg-slate-100 dark:hover:bg-slate-900"
                                @click.stop="toggleProfile"
                            >
                                <span
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-700 text-sm font-black text-white shadow-lg shadow-indigo-500/20"
                                >
                                    {{ initials }}
                                </span>

                                <span
                                    class="hidden text-left md:block"
                                >
                                    <span
                                        class="block max-w-32 truncate text-sm font-bold text-slate-950 dark:text-white"
                                    >
                                        {{ user.name || 'Administrator' }}
                                    </span>

                                    <span
                                        class="block text-[11px] font-semibold text-slate-500"
                                    >
                                        Super administrator
                                    </span>
                                </span>

                                <span
                                    class="hidden text-xs text-slate-400 md:block"
                                >
                                    ▾
                                </span>
                            </button>

                            <div
                                v-if="profileOpen"
                                class="absolute right-0 top-[calc(100%+10px)] z-50 w-72 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10 dark:border-slate-700 dark:bg-slate-900"
                            >
                                <!-- User -->
                                <div
                                    class="flex items-center gap-3 border-b border-slate-200 p-4 dark:border-slate-800"
                                >
                                    <span
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-100 font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                                    >
                                        {{ initials }}
                                    </span>

                                    <div class="min-w-0">
                                        <p class="truncate font-black">
                                            {{ user.name || 'Administrator' }}
                                        </p>

                                        <p class="truncate text-sm text-slate-500">
                                            {{ user.email || 'admin@mgs.com' }}
                                        </p>
                                    </div>
                                </div>

                                <router-link
                                    to="/admin-panel/settings"
                                    class="flex items-center gap-3 px-4 py-3 text-sm font-bold transition hover:bg-slate-50 dark:hover:bg-slate-800"
                                    @click="closeDropdowns"
                                >
                                    <span>⚙️</span>
                                    Admin settings
                                </router-link>

                                <!-- Theme -->
                                <div
                                    class="border-y border-slate-200 px-4 py-4 dark:border-slate-800"
                                >
                                    <p
                                        class="mb-3 text-xs font-black uppercase tracking-wider text-slate-400"
                                    >
                                        Theme mode
                                    </p>

                                    <div class="grid grid-cols-3 gap-2">
                                        <button
                                            v-for="theme in themeOptions"
                                            :key="theme.value"
                                            type="button"
                                            class="rounded-xl border px-2 py-2 text-center text-xs font-bold transition"
                                            :class="
                                                currentTheme === theme.value
                                                    ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300'
                                                    : 'border-slate-200 text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800'
                                            "
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
                                    class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm font-bold text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30"
                                    @click="logout"
                                >
                                    <span>↪</span>
                                    Log out
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Route content -->
            <main class="px-4 py-6 sm:px-6 lg:px-7">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script>
import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';

import {
    useRoute,
    useRouter,
} from 'vue-router';

import axios from 'axios';

export default {
    name: 'AdminLayout',

    setup() {
        const route = useRoute();
        const router = useRouter();

        const sidebarOpen = ref(false);
        const notificationOpen = ref(false);
        const profileOpen = ref(false);

        const notificationRef = ref(null);
        const profileRef = ref(null);

        const globalSearch = ref('');

        const user = JSON.parse(
            localStorage.getItem('user') || '{}'
        );

        const currentTheme = ref(
            localStorage.getItem('theme') || 'light'
        );

        const notifications = ref([]);
        const unreadNotifications = ref(0);
        const notificationsLoading = ref(false);
        let notificationTimer = null;

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

        const navigationSections = computed(() => [
            {
                label: 'Overview',
                items: [
                    {
                        to: '/admin-panel/dashboard',
                        label: 'Dashboard',
                        icon: '▦',
                    },
                ],
            },
            {
                label: 'Management',
                items: [
                    {
                        to: '/admin-panel/business-activations',
                        label: 'Business Activations',
                        icon: '🏢',
                    },
                    {
                        to: '/admin-panel/merchants',
                        label: 'Merchants',
                        icon: '🏪',
                    },
                    {
                        to: '/admin-panel/customers',
                        label: 'Customers',
                        icon: '♟',
                    },
                    {
                        to: '/admin-panel/transactions',
                        label: 'Transactions',
                        icon: '↻',
                    },
                    {
                        to: '/admin-panel/payouts',
                        label: 'Payouts',
                        icon: '⇧',
                    },
                    {
                        label: 'Withdrawal Settings',
                        to: '/admin-panel/withdrawal-settings',
                        icon: '⚙️',
                    },
                    {to:'/admin-panel/kyc-requests',label:'KYC Requests',icon:'?'},
                    {to:'/admin-panel/audit-logs',label:'Audit Logs',icon:'?'},
                    {to:'/admin-panel/webhook-logs',label:'Webhook Deliveries',icon:'?'},
                    {to:'/admin-panel/virtual-accounts',label:'Virtual Accounts',icon:'?'},
                    {to:'/admin-panel/pricing',label:'Pricing',icon:'?'},
                    {
                        to: '/admin-panel/providers',
                        label: 'Providers',
                        icon: '◎',
                    },
                ],
            },
            {
                label: 'Operations',
                items: [
                    {
                        to: '/admin-panel/reports',
                        label: 'Reports',
                        icon: '◩',
                    },
                    {
                        to: '/admin-panel/notifications',
                        label: 'Notifications',
                        icon: '🔔',
                        badge: unreadNotifications.value > 0
                            ? (
                                unreadNotifications.value > 99
                                    ? '99+'
                                    : unreadNotifications.value
                            )
                            : null,
                    },
                    {
                        to: '/admin-panel/support',
                        label: 'Support Tickets',
                        icon: '◉',
                    },
                    {
                        to: '/admin-panel/settings',
                        label: 'Settings',
                        icon: '⚙',
                    },
                    { to: '/admin-panel/security', label: 'Security & MFA', icon: '🔐' },
                ],
            },
        ]);

        const pageTitle = computed(() => {
            return route.meta?.title || 'Dashboard';
        });

        const initials = computed(() => {
            return (user.name || 'Admin')
                .split(' ')
                .filter(Boolean)
                .map((word) => word.charAt(0))
                .join('')
                .substring(0, 2)
                .toUpperCase();
        });


        const closeSidebar = () => {
            sidebarOpen.value = false;
        };

        const closeDropdowns = () => {
            notificationOpen.value = false;
            profileOpen.value = false;
        };

        const toggleNotifications = () => {
            profileOpen.value = false;
            notificationOpen.value = !notificationOpen.value;
        };

        const toggleProfile = () => {
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
                const prefersDark = window.matchMedia(
                    '(prefers-color-scheme: dark)'
                ).matches;

                root.classList.toggle('dark', prefersDark);
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
                notificationRef.value?.contains(target) ||
                profileRef.value?.contains(target)
            ) {
                return;
            }

            closeDropdowns();
        };

        const handleEscape = (event) => {
            if (event.key === 'Escape') {
                closeDropdowns();
                sidebarOpen.value = false;
            }
        };

        const notificationIcon = (type) => {
            const icons = {
                customer_registered: '👤',
                kyc_verified: '🪪',
                virtual_account_created: '🏦',
                virtual_account_failed: '⚠️',
                business_approved: '✅',
                business_rejected: '❌',
                payout_approved: '💸',
                payout_rejected: '⛔',
                system_alert: '🔔',
            };

            return icons[type] || '🔔';
        };

        const notificationRoute = (notification) => {
            const data = notification.data || {};

            if (data.activation_id) {
                return '/admin-panel/business-activations';
            }

            if (data.payout_id) {
                return '/admin-panel/payouts';
            }

            if (data.customer_id) {
                return '/admin-panel/customers';
            }

            if (notification.type?.startsWith('virtual_account')) {
                return '/admin-panel/customers';
            }

            return '/admin-panel/notifications';
        };

        const formatNotificationTime = (date) => {
            if (!date) {
                return '';
            }

            const created = new Date(date);
            const seconds = Math.floor(
                (Date.now() - created.getTime()) / 1000
            );

            if (seconds < 60) {
                return 'Just now';
            }

            if (seconds < 3600) {
                return `${Math.floor(seconds / 60)}m ago`;
            }

            if (seconds < 86400) {
                return `${Math.floor(seconds / 3600)}h ago`;
            }

            return created.toLocaleDateString();
        };

        const loadUnreadNotifications = async () => {
            notificationsLoading.value = true;

            try {
                const response = await axios.get(
                    '/api/v1/admin/notifications/unread'
                );

                const data = response.data?.data || {};

                unreadNotifications.value = Number(
                    data.count || 0
                );

                notifications.value = Array.isArray(data.items)
                    ? data.items.map((item) => ({
                        ...item,
                        icon: notificationIcon(item.type),
                        read: Boolean(item.is_read),
                        route: notificationRoute(item),
                        time: formatNotificationTime(
                            item.created_at
                        ),
                    }))
                    : [];
            } catch (error) {
                console.error(
                    'Failed to load admin notifications:',
                    error.response?.data || error
                );
            } finally {
                notificationsLoading.value = false;
            }
        };

        const markAllNotificationsRead = async () => {
            try {
                await axios.post(
                    '/api/v1/admin/notifications/read-all'
                );

                unreadNotifications.value = 0;
                notifications.value = notifications.value.map(
                    (notification) => ({
                        ...notification,
                        read: true,
                        is_read: true,
                    })
                );
            } catch (error) {
                console.error(
                    'Failed to mark notifications as read:',
                    error.response?.data || error
                );
            }
        };

        const openNotification = async (notification) => {
            try {
                if (!notification.read) {
                    await axios.post(
                        `/api/v1/admin/notifications/${notification.id}/read`
                    );

                    notification.read = true;
                    notification.is_read = true;
                    unreadNotifications.value = Math.max(
                        0,
                        unreadNotifications.value - 1
                    );
                }
            } catch (error) {
                console.error(
                    'Failed to mark notification as read:',
                    error.response?.data || error
                );
            } finally {
                notificationOpen.value = false;

                if (notification.route) {
                    await router.push(notification.route);
                }
            }
        };

        const performSearch = async () => {
            const search = globalSearch.value
                .trim()
                .toLowerCase();

            if (!search) {
                return;
            }

            const matchedRoute = navigationSections.value
                .flatMap((section) => section.items)
                .find((item) =>
                    item.label.toLowerCase().includes(search)
                );

            if (matchedRoute) {
                globalSearch.value = '';
                await router.push(matchedRoute.to);
            }
        };

        const logout = async () => {
            try {
                await axios.post('/api/v1/admin/auth/logout');
            } catch (error) {
                console.error(
                    'Admin logout request failed:',
                    error.response?.data || error
                );
            } finally {
                [
                    'admin_token',
                    'admin',
                ].forEach((key) => {
                    localStorage.removeItem(key);
                });

                window.location.href = '/admin-panel';
            }
        };

        watch(
            () => route.fullPath,
            () => {
                closeSidebar();
                closeDropdowns();
            }
        );

        onMounted(() => {
            applyTheme(currentTheme.value);
            loadUnreadNotifications();

            notificationTimer = window.setInterval(
                loadUnreadNotifications,
                30000
            );

            document.addEventListener(
                'mousedown',
                handleOutsideClick,
                true
            );

            document.addEventListener(
                'keydown',
                handleEscape
            );
        });

        onBeforeUnmount(() => {
            if (notificationTimer) {
                window.clearInterval(notificationTimer);
            }

            document.removeEventListener(
                'mousedown',
                handleOutsideClick,
                true
            );

            document.removeEventListener(
                'keydown',
                handleEscape
            );
        });

        return {
            sidebarOpen,
            notificationOpen,
            profileOpen,
            notificationRef,
            profileRef,
            globalSearch,
            user,
            currentTheme,
            notifications,
            themeOptions,
            navigationSections,
            pageTitle,
            initials,
            unreadNotifications,
            notificationsLoading,
            loadUnreadNotifications,
            closeSidebar,
            closeDropdowns,
            toggleNotifications,
            toggleProfile,
            setTheme,
            markAllNotificationsRead,
            openNotification,
            performSearch,
            logout,
        };
    },
};
</script>

<style>
.admin-nav-link {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.25rem;
    border-radius: 0.9rem;
    padding: 0.65rem 0.75rem;
    color: rgb(71 85 105);
    font-size: 0.875rem;
    font-weight: 700;
    text-decoration: none;
    transition:
        background-color 150ms ease,
        color 150ms ease,
        transform 150ms ease;
}

.admin-nav-link:hover {
    background: rgb(248 250 252);
    color: rgb(79 70 229);
    transform: translateX(2px);
}

.admin-nav-link-active {
    background: rgb(238 242 255);
    color: rgb(79 70 229);
}

.dark .admin-nav-link {
    color: rgb(203 213 225);
}

.dark .admin-nav-link:hover {
    background: rgb(15 23 42);
    color: rgb(165 180 252);
}

.dark .admin-nav-link-active {
    background: rgb(49 46 129 / 0.32);
    color: rgb(165 180 252);
}
</style>
