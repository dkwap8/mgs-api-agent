import {
    createRouter,
    createWebHistory,
} from 'vue-router';

const routes = [
    { path: '/admin-panel', component: () => import('../views/Login.vue'), meta: { guest: true, context: 'admin' } },
    { path: '/user', redirect: '/user/dashboard' },
    ...['login', 'register', 'forgot-password', 'reset-password'].map(page => ({
        path: `/${page}`, redirect: to => ({ path: `/user/${page}`, query: to.query }),
    })),
    { path: '/admin', redirect: '/admin-panel/dashboard' },
    { path: '/merchant', redirect: '/user/dashboard' },
    { path: '/admin/:page(merchants|providers|transactions|payouts|reports|settings|notifications|support|customers|business-activations|withdrawal-settings|activity-logs)', redirect: to => `/admin-panel/${to.params.page}` },
    { path: '/merchant/:page(analytics|customers|accounts|transactions|wallet|payouts|api-keys|webhooks|documentation|help|settings|activate)', redirect: to => `/user/${to.params.page}` },

    {
        path: '/user/login',
        component: () =>
            import('../views/Login.vue'),
        meta: {
            guest: true,
            context: 'user',
        },
    },

    {
        path: '/user/register',
        component: () =>
            import('../views/Register.vue'),
        meta: {
            guest: true,
            context: 'user',
        },
    },

    {
        path: '/user/forgot-password',
        component: () =>
            import('../views/ForgotPassword.vue'),
        meta: {
            guest: true,
            context: 'user',
        },
    },

    {
        path: '/user/reset-password',
        component: () =>
            import('../views/ResetPassword.vue'),
        meta: {
            guest: true,
            context: 'user',
        },
    },

    /*
    |--------------------------------------------------------------------------
    | Merchant Routes
    |--------------------------------------------------------------------------
    */

    {
        path: '/user',
        component: () =>
            import('../layouts/MerchantLayout.vue'),

        meta: {
            requiresMerchant: true,
        },

        children: [
            { path:'api-logs', component:()=>import('../views/merchant/ApiLogs.vue'), meta:{title:'API Logs'} },
            { path:'bank-accounts', component:()=>import('../views/merchant/BankAccounts.vue'), meta:{title:'Settlement bank accounts'} },
            {
                path: 'dashboard',
                component: () =>
                    import(
                        '../views/merchant/Dashboard.vue'
                    ),
                meta: {
                    title: 'Dashboard',
                },
            },

            {
                path: 'analytics',
                component: () =>
                    import(
                        '../views/merchant/Analytics.vue'
                    ),
                meta: {
                    title: 'Analytics',
                },
            },

            {
                path: 'customers',
                component: () =>
                    import(
                        '../views/merchant/Customers.vue'
                    ),
                meta: {
                    title: 'Customers',
                },
            },

            {
                path: 'accounts',
                component: () =>
                    import(
                        '../views/merchant/VirtualAccounts.vue'
                    ),
                meta: {
                    title: 'Virtual Accounts',
                },
            },

            {
                path: 'transactions',
                component: () =>
                    import(
                        '../views/merchant/Transactions.vue'
                    ),
                meta: {
                    title: 'Transactions',
                },
            },

            {
                path: 'wallet',
                component: () =>
                    import(
                        '../views/merchant/Wallet.vue'
                    ),
                meta: {
                    title: 'Wallet',
                },
            },

            {
                path: 'payouts',
                component: () =>
                    import(
                        '../views/merchant/Payouts.vue'
                    ),
                meta: {
                    title: 'Payouts',
                },
            },

            {
                path: 'api-keys',
                component: () =>
                    import(
                        '../views/merchant/ApiKeys.vue'
                    ),
                meta: {
                    title: 'API Keys',
                },
            },

            {
                path: 'webhooks',
                component: () =>
                    import(
                        '../views/merchant/Webhooks.vue'
                    ),
                meta: {
                    title: 'Webhooks',
                },
            },

            {
                path: 'documentation',
                component: () =>
                    import(
                        '../views/merchant/Documentation.vue'
                    ),
                meta: {
                    title: 'Documentation',
                },
            },

            {
                path: 'help',
                name: 'HelpSupport',
                component: () =>
                    import(
                        '../views/merchant/HelpSupport.vue'
                    ),
                meta: {
                    title: 'Help & Support',
                },
            },

            {
                path: 'settings',
                component: () =>
                    import(
                        '../views/merchant/Settings.vue'
                    ),
                meta: {
                    title: 'Settings',
                },
            },

            {
                path: 'activate',
                component: () =>
                    import(
                        '../views/merchant/ActivateBusiness.vue'
                    ),
                meta: {
                    title: 'Activate Business',
                },
            },
        ],
    },

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */

    {
        path: '/admin-panel',
        component: () =>
            import('../layouts/AdminLayout.vue'),

        meta: {
            requiresAdmin: true,
        },

        children: [
            { path:'kyc-requests', component:()=>import('../views/admin/KycRequests.vue'), meta:{title:'KYC Requests'} },
            { path:'audit-logs', component:()=>import('../views/admin/AuditLogs.vue'), meta:{title:'Audit Logs'} },
            { path:'webhook-logs', component:()=>import('../views/admin/WebhookLogs.vue'), meta:{title:'Webhook Deliveries'} },
            { path:'virtual-accounts', component:()=>import('../views/admin/VirtualAccounts.vue'), meta:{title:'Virtual Accounts'} },
            { path:'pricing', component:()=>import('../views/admin/Pricing.vue'), meta:{title:'Pricing'} },
            {
                path: 'dashboard',
                component: () =>
                    import(
                        '../views/admin/Dashboard.vue'
                    ),
                meta: {
                    title: 'Dashboard',
                },
            },

            { path: 'security', component: () => import('../views/admin/Security.vue'), meta: { title: 'Administrator security' } },

            {
                path: 'merchants',
                component: () =>
                    import(
                        '../views/admin/Merchants.vue'
                    ),
                meta: {
                    title: 'Merchants',
                },
            },

            {
                path: 'providers',
                component: () =>
                    import(
                        '../views/admin/Providers.vue'
                    ),
                meta: {
                    title: 'Providers',
                },
            },

            {
                path: 'transactions',
                component: () =>
                    import(
                        '../views/admin/Transactions.vue'
                    ),
                meta: {
                    title: 'Transactions',
                },
            },

            {
                path: 'payouts',
                component: () =>
                    import(
                        '../views/admin/Payouts.vue'
                    ),
                meta: {
                    title: 'Payouts',
                },
            },

            {
                path: 'reports',
                component: () =>
                    import(
                        '../views/admin/Reports.vue'
                    ),
                meta: {
                    title: 'Reports',
                },
            },

            {
                path: 'settings',
                component: () =>
                    import(
                        '../views/admin/Settings.vue'
                    ),
                meta: {
                    title: 'Settings',
                },
            },

            {
                path: 'notifications',
                name: 'AdminNotifications',
                component: () =>
                    import(
                        '../views/admin/Notifications.vue'
                    ),
                meta: {
                    title: 'Notifications',
                },
            },

            {
                path: 'support',
                component: () =>
                    import(
                        '../views/admin/SupportTickets.vue'
                    ),
                meta: {
                    title: 'Support Tickets',
                },
            },

            {
                path: 'customers',
                component: () =>
                    import(
                        '../views/admin/Customers.vue'
                    ),
                meta: {
                    title: 'Customers',
                },
            },

            {
                path: 'business-activations',
                name: 'AdminBusinessActivations',
                component: () =>
                    import(
                        '../views/admin/BusinessActivations.vue'
                    ),
                meta: {
                    title: 'Business Activations',
                },
            },

            {
                path: 'withdrawal-settings',
                component: () =>
                    import(
                        '../views/admin/WithdrawalSettings.vue'
                    ),
                meta: {
                    title: 'Withdrawal Settings',
                },
            },

            {
                path: 'activity-logs',
                component: () =>
                    import(
                        '../views/admin/ActivityLogs.vue'
                    ),
                meta: {
                    title: 'Activity Logs',
                },
            },
        ],
    },

    /*
    |--------------------------------------------------------------------------
    | Default Route
    |--------------------------------------------------------------------------
    */

    {
        path: '/',
        redirect: () => {
            const adminToken =
                localStorage.getItem('admin_session');

            const merchantToken =
                localStorage.getItem('merchant_session');

            if (adminToken) {
                return '/admin-panel/dashboard';
            }

            if (merchantToken) {
                return '/user/dashboard';
            }

            return '/user/login';
        },
    },

    /*
    |--------------------------------------------------------------------------
    | Not Found
    |--------------------------------------------------------------------------
    */

    {
        path: '/:pathMatch(.*)*',
        component: () => import('../views/NotFound.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const adminToken =
        localStorage.getItem('admin_session');

    const merchantToken =
        localStorage.getItem('merchant_session');

    const requiresAdmin =
        to.matched.some(
            (record) =>
                record.meta.requiresAdmin
        );

    const requiresMerchant =
        to.matched.some(
            (record) =>
                record.meta.requiresMerchant
        );

    const isGuestRoute =
        to.matched.some(
            (record) =>
                record.meta.guest
        );

    /*
    |--------------------------------------------------------------------------
    | Protect Admin Routes
    |--------------------------------------------------------------------------
    */

    if (
        requiresAdmin &&
        !adminToken
    ) {
        return next('/admin-panel');
    }

    /*
    |--------------------------------------------------------------------------
    | Protect Merchant Routes
    |--------------------------------------------------------------------------
    */

    if (
        requiresMerchant &&
        !merchantToken
    ) {
        return next('/user/login');
    }

    /*
    |--------------------------------------------------------------------------
    | Redirect Logged-In Users Away From Guest Pages
    |--------------------------------------------------------------------------
    */

    if (isGuestRoute) {
        if (to.meta.context === 'admin' && adminToken) return next('/admin-panel/dashboard');
        if (to.meta.context === 'user' && merchantToken) return next('/user/dashboard');
    }

    /*
    |--------------------------------------------------------------------------
    | Page Title
    |--------------------------------------------------------------------------
    */

    document.title = to.meta.title
        ? `${to.meta.title} - MGS API`
        : 'MGS API';

    return next();
});

export default router;
