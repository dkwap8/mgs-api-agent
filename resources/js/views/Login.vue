<template>
    <div class="flex min-h-screen flex-col bg-slate-50 dark:bg-slate-950">
        <header
            class="border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90"
        >
            <div
                class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
            >
                <router-link
                    to="/"
                    class="flex items-center gap-3"
                >
                    <span
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-600 text-lg font-black text-white shadow-lg shadow-indigo-600/20"
                    >
                        M
                    </span>

                    <div>
                        <p
                            class="text-lg font-bold leading-none text-slate-950 dark:text-white"
                        >
                            MGS API
                        </p>

                        <p
                            class="mt-1 text-xs text-slate-500 dark:text-slate-400"
                        >
                            Payment infrastructure
                        </p>
                    </div>
                </router-link>

                <div
                    v-if="loginType === 'merchant'"
                    class="flex items-center gap-3"
                >
                    <span
                        class="hidden text-sm text-slate-500 dark:text-slate-400 sm:inline"
                    >
                        New to MGS API?
                    </span>

                    <router-link
                        to="/user/register"
                        class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"
                    >
                        Create account
                    </router-link>
                </div>
            </div>
        </header>

        <main class="flex flex-1 items-center justify-center px-4 py-12 sm:px-6">
            <div
                class="grid w-full max-w-6xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10 dark:border-slate-800 dark:bg-slate-900 lg:grid-cols-2"
            >
                <section
                    class="relative hidden overflow-hidden bg-gradient-to-br from-indigo-700 via-indigo-800 to-slate-950 p-10 text-white lg:flex lg:flex-col lg:justify-between"
                >
                    <div
                        class="absolute -left-24 top-12 h-72 w-72 rounded-full bg-cyan-400/20 blur-3xl"
                    ></div>

                    <div
                        class="absolute -bottom-24 right-0 h-96 w-96 rounded-full bg-purple-500/20 blur-3xl"
                    ></div>

                    <div class="relative z-10">
                        <span
                            class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-medium text-indigo-100 backdrop-blur"
                        >
                            {{
                                loginType === 'admin'
                                    ? 'Secure administrator access'
                                    : 'Secure merchant access'
                            }}
                        </span>

                        <h1 class="mt-7 text-4xl font-bold leading-tight">
                            {{
                                loginType === 'admin'
                                    ? 'Manage and supervise the MGS API platform.'
                                    : 'Welcome back to your payment operations centre.'
                            }}
                        </h1>

                        <p class="mt-5 max-w-md text-base leading-7 text-indigo-100">
                            {{
                                loginType === 'admin'
                                    ? 'Review businesses, manage merchants, payouts, providers, transactions and platform settings from one secure dashboard.'
                                    : 'Manage businesses, collect payments, create virtual accounts, monitor transactions and access your API tools from one secure dashboard.'
                            }}
                        </p>
                    </div>

                    <div class="relative z-10 space-y-4">
                        <div
                            class="flex items-start gap-4 rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur"
                        >
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15"
                            >
                                🔐
                            </span>

                            <div>
                                <p class="font-semibold">
                                    Secure authentication
                                </p>

                                <p class="mt-1 text-sm text-indigo-100">
                                    Sanctum-powered access with separate merchant
                                    and administrator sessions.
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start gap-4 rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur"
                        >
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15"
                            >
                                {{
                                    loginType === 'admin'
                                        ? '🛡️'
                                        : '⚡'
                                }}
                            </span>

                            <div>
                                <p class="font-semibold">
                                    {{
                                        loginType === 'admin'
                                            ? 'Platform administration'
                                            : 'One account, multiple businesses'
                                    }}
                                </p>

                                <p class="mt-1 text-sm text-indigo-100">
                                    {{
                                        loginType === 'admin'
                                            ? 'Administrator accounts are authenticated directly from the admins table.'
                                            : 'Switch between all businesses under your merchant account.'
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="p-6 sm:p-10 lg:p-12">
                    <div class="mx-auto max-w-md">


                        <div>
                            <p
                                class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400"
                            >
                                {{
                                    loginType === 'admin'
                                        ? 'Administrator sign in'
                                        : 'Merchant sign in'
                                }}
                            </p>

                            <h2
                                class="mt-2 text-3xl font-bold text-slate-950 dark:text-white"
                            >
                                {{
                                    loginType === 'admin'
                                        ? 'Sign in to the admin panel'
                                        : 'Sign in to your account'
                                }}
                            </h2>

                            <p
                                class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400"
                            >
                                {{
                                    loginType === 'admin'
                                        ? 'Enter an active administrator email address and password.'
                                        : 'Enter your registered merchant email address and password.'
                                }}
                            </p>
                        </div>

                        <form
                            class="mt-8 space-y-5"
                            novalidate
                            @submit.prevent="login"
                        >
                            <div
                                v-if="generalError"
                                class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300"
                            >
                                {{ generalError }}
                            </div>

                            <div>
                                <label
                                    for="email"
                                    class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                >
                                    Email address
                                </label>

                                <input
                                    id="email"
                                    v-model.trim="form.email"
                                    type="email"
                                    inputmode="email"
                                    autocomplete="email"
                                    placeholder="name@example.com"
                                    class="form-input"
                                    :class="errors.email ? 'form-input-error' : ''"
                                    @input="clearError('email')"
                                />

                                <p
                                    v-if="errors.email"
                                    class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                                >
                                    {{ errors.email }}
                                </p>
                            </div>

                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <label
                                        for="password"
                                        class="block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                    >
                                        Password
                                    </label>

                                    <router-link
                                        v-if="loginType === 'merchant'"
                                        to="/user/forgot-password"
                                        class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                                    >
                                        Forgot password?
                                    </router-link>
                                </div>

                                <div class="relative">
                                    <input
                                        id="password"
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        autocomplete="current-password"
                                        placeholder="Enter your password"
                                        class="form-input pr-16"
                                        :class="errors.password ? 'form-input-error' : ''"
                                        @input="clearError('password')"
                                    />

                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 flex items-center px-4 text-sm font-semibold text-slate-500 transition hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400"
                                        @click="showPassword = !showPassword"
                                    >
                                        {{ showPassword ? 'Hide' : 'Show' }}
                                    </button>
                                </div>

                                <p
                                    v-if="errors.password"
                                    class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                                >
                                    {{ errors.password }}
                                </p>
                            </div>

                            <div>
                                <label for="mfa-code" class="mb-2 block text-sm font-semibold">Authenticator code (if enabled)</label>
                                <input id="mfa-code" v-model.trim="form.two_factor_code" class="form-input" inputmode="numeric" autocomplete="one-time-code" maxlength="6" />
                                <details class="mt-3 text-sm">
                                    <summary>Use a recovery code</summary>
                                    <input v-model.trim="form.recovery_code" class="form-input mt-2" autocomplete="off" placeholder="Unused recovery code" />
                                </details>
                            </div>
                            <label class="flex items-center gap-3">
                                <input
                                    v-model="rememberMe"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />

                                <span
                                    class="text-sm text-slate-600 dark:text-slate-400"
                                >
                                    Remember my email address
                                </span>
                            </label>

                            <button
                                type="submit"
                                :disabled="loading"
                                class="flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3.5 font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <svg
                                    v-if="loading"
                                    class="h-5 w-5 animate-spin"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>

                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                    ></path>
                                </svg>

                                {{
                                    loading
                                        ? 'Signing in...'
                                        : loginType === 'admin'
                                          ? 'Sign in as administrator'
                                          : 'Sign in securely'
                                }}
                            </button>

                            <p
                                v-if="loginType === 'merchant'"
                                class="text-center text-sm text-slate-500 dark:text-slate-400"
                            >
                                Do not have an account?

                                <router-link
                                    to="/user/register"
                                    class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                                >
                                    Create one now
                                </router-link>
                            </p>
                        </form>
                    </div>
                </section>
            </div>
        </main>

        <footer
            class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950"
        >
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-6 text-sm text-slate-500 dark:text-slate-400 sm:flex-row sm:px-6 lg:px-8"
            >
                <p>
                    © {{ currentYear }} MGS API. All rights reserved.
                </p>

                <div class="flex items-center gap-5">
                    <a
                        href="#"
                        class="hover:text-indigo-600"
                        @click.prevent
                    >
                        Privacy
                    </a>

                    <a
                        href="#"
                        class="hover:text-indigo-600"
                        @click.prevent
                    >
                        Terms
                    </a>

                    <router-link
                        v-if="loginType === 'merchant'"
                        to="/user/forgot-password"
                        class="hover:text-indigo-600"
                    >
                        Account recovery
                    </router-link>
                </div>
            </div>
        </footer>
    </div>
</template>

<script>
import {
    computed,
    onMounted,
    ref,
} from 'vue';

import {
    useRoute,
    useRouter,
} from 'vue-router';

import axios from 'axios';

export default {
    name: 'Login',

    setup() {
        const route = useRoute();
        const router = useRouter();

        const currentYear = new Date().getFullYear();

        const form = ref({
            email: '',
            password: '',
            two_factor_code: '',
            recovery_code: '',
        });

        const loginType = computed(() => route.meta.context === 'admin' ? 'admin' : 'merchant');

        const errors = ref({});
        const generalError = ref('');
        const loading = ref(false);
        const showPassword = ref(false);
        const rememberMe = ref(false);

        const normalizeErrors = (serverErrors = {}) => {
            const normalized = {};

            Object.entries(serverErrors).forEach(([field, messages]) => {
                normalized[field] = Array.isArray(messages)
                    ? messages[0]
                    : String(messages);
            });

            return normalized;
        };

        const clearError = (field) => {
            if (errors.value[field]) {
                delete errors.value[field];
            }

            generalError.value = '';
        };

        const clearMerchantStorage = () => {
            [
                'token',
                'merchant_token',
                'user',
                'merchant',
                'business',
                'businesses',
                'merchants',
            ].forEach((key) => {
                localStorage.removeItem(key);
            });
        };

        const clearAdminStorage = () => {
            [
                'token',
                'admin_token',
                'admin',
            ].forEach((key) => {
                localStorage.removeItem(key);
            });
        };

        const validate = () => {
            const validationErrors = {};

            if (!form.value.email.trim()) {
                validationErrors.email =
                    'Please enter your email address.';
            } else if (
                !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
                    form.value.email.trim()
                )
            ) {
                validationErrors.email =
                    'Please enter a valid email address.';
            }

            if (!form.value.password) {
                validationErrors.password =
                    'Please enter your password.';
            }

            errors.value = validationErrors;

            return Object.keys(validationErrors).length === 0;
        };

        const storeMerchantSession = (data) => {
            clearMerchantStorage();

            localStorage.removeItem('merchant_token');
            localStorage.setItem('merchant_session', '1');
            localStorage.setItem('account_type', 'merchant');

            localStorage.setItem(
                'user',
                JSON.stringify(data.user || {})
            );

            localStorage.setItem(
                'merchant',
                JSON.stringify(data.merchant || {})
            );

            localStorage.setItem(
                'business',
                JSON.stringify(data.business || {})
            );

            localStorage.setItem(
                'businesses',
                JSON.stringify(data.businesses || [])
            );

            delete axios.defaults.headers.common.Authorization;
        };

        const storeAdminSession = (data) => {
            clearAdminStorage();

            localStorage.removeItem('admin_token');
            localStorage.setItem('admin_session', '1');
            localStorage.setItem('account_type', 'admin');

            localStorage.setItem(
                'admin',
                JSON.stringify(data.admin || {})
            );

            delete axios.defaults.headers.common.Authorization;
        };

        const login = async () => {
            generalError.value = '';

            if (!validate()) {
                return;
            }

            loading.value = true;

            try {
                const email =
                    form.value.email.trim().toLowerCase();

                const endpoint =
                    loginType.value === 'admin'
                        ? '/api/v1/admin/auth/login'
                        : '/api/v1/auth/login';

                const response = await axios.post(endpoint, {
                    email,
                    password: form.value.password,
                    two_factor_code: form.value.two_factor_code || undefined,
                    recovery_code: form.value.recovery_code || undefined,
                }, { suppressVerificationModal: true });

                const data = response.data?.data || {};

                if (!data.browser_session) {
                    throw new Error(
                        'The server did not establish a browser session.'
                    );
                }

                const rememberedEmailKey =
                    loginType.value === 'admin'
                        ? 'remembered_admin_email'
                        : 'remembered_merchant_email';

                if (rememberMe.value) {
                    localStorage.setItem(
                        rememberedEmailKey,
                        email
                    );
                } else {
                    localStorage.removeItem(
                        rememberedEmailKey
                    );
                }

                if (loginType.value === 'admin') {
                    storeAdminSession(data);
                    await router.replace(data.requires_mfa_setup ? '/admin-panel/security' : '/admin-panel/dashboard');
                    return;
                }

                storeMerchantSession(data);
                await router.replace('/user/dashboard');
            } catch (error) {
                const response = error.response?.data;

                errors.value = normalizeErrors(
                    response?.errors ||
                    response?.data?.errors ||
                    {}
                );

                generalError.value =
                    response?.message ||
                    response?.data?.message ||
                    error.message ||
                    'Unable to sign in. Please check your details and try again.';
            } finally {
                loading.value = false;
            }
        };

        onMounted(() => {
            const rememberedEmailKey =
                loginType.value === 'admin'
                    ? 'remembered_admin_email'
                    : 'remembered_merchant_email';

            const rememberedEmail =
                localStorage.getItem(rememberedEmailKey);

            if (rememberedEmail) {
                form.value.email = rememberedEmail;
                rememberMe.value = true;
            }
        });

        return {
            currentYear,
            form,
            loginType,
            errors,
            generalError,
            loading,
            showPassword,
            rememberMe,
            clearError,
            login,
        };
    },
};
</script>

<style scoped>
.form-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid rgb(203 213 225);
    background: white;
    padding: 0.85rem 1rem;
    color: rgb(15 23 42);
    outline: none;
    transition:
        border-color 150ms ease,
        box-shadow 150ms ease,
        background-color 150ms ease;
}

.form-input::placeholder {
    color: rgb(148 163 184);
}

.form-input:focus {
    border-color: rgb(99 102 241);
    box-shadow: 0 0 0 4px rgb(99 102 241 / 0.1);
}

.form-input-error {
    border-color: rgb(239 68 68);
}

.form-input-error:focus {
    border-color: rgb(239 68 68);
    box-shadow: 0 0 0 4px rgb(239 68 68 / 0.1);
}

:global(.dark) .form-input {
    border-color: rgb(51 65 85);
    background: rgb(2 6 23);
    color: white;
}
</style>
