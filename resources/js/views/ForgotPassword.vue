<template>
    <div class="flex min-h-screen flex-col bg-slate-50 dark:bg-slate-950">
        <!-- Header -->
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
                            Secure account recovery
                        </p>
                    </div>
                </router-link>

                <router-link
                    to="/user/login"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                >
                    Back to sign in
                </router-link>
            </div>
        </header>

        <!-- Main -->
        <main class="flex flex-1 items-center justify-center px-4 py-12 sm:px-6">
            <div
                class="grid w-full max-w-5xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10 dark:border-slate-800 dark:bg-slate-900 lg:grid-cols-5"
            >
                <!-- Information panel -->
                <section
                    class="relative hidden overflow-hidden bg-gradient-to-br from-indigo-700 via-indigo-800 to-slate-950 p-10 text-white lg:col-span-2 lg:flex lg:flex-col lg:justify-between"
                >
                    <div
                        class="absolute -left-20 top-20 h-64 w-64 rounded-full bg-cyan-400/20 blur-3xl"
                    ></div>

                    <div
                        class="absolute -bottom-24 right-0 h-80 w-80 rounded-full bg-purple-500/20 blur-3xl"
                    ></div>

                    <div class="relative z-10">
                        <span
                            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 text-2xl"
                        >
                            🔑
                        </span>

                        <h1 class="mt-7 text-3xl font-bold leading-tight">
                            Recover access securely.
                        </h1>

                        <p class="mt-4 leading-7 text-indigo-100">
                            Enter the email connected to your MGS API account.
                            We will send recovery instructions if the account
                            exists.
                        </p>
                    </div>

                    <div
                        class="relative z-10 rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur"
                    >
                        <p class="font-semibold">
                            Account protection
                        </p>

                        <p class="mt-2 text-sm leading-6 text-indigo-100">
                            Never share password reset links, OTP codes or
                            authentication credentials with another person.
                        </p>
                    </div>
                </section>

                <!-- Form -->
                <section class="p-6 sm:p-10 lg:col-span-3 lg:p-12">
                    <div class="mx-auto max-w-md">
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-100 text-2xl dark:bg-indigo-950"
                        >
                            ✉️
                        </div>

                        <h2
                            class="mt-6 text-3xl font-bold text-slate-950 dark:text-white"
                        >
                            Forgot your password?
                        </h2>

                        <p
                            class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400"
                        >
                            No problem. Enter your registered email address and
                            we will send password recovery instructions.
                        </p>

                        <!-- Success -->
                        <div
                            v-if="submitted"
                            class="mt-8"
                        >
                            <div
                                class="rounded-2xl border border-green-200 bg-green-50 p-5 dark:border-green-900/50 dark:bg-green-950/40"
                            >
                                <div class="flex items-start gap-4">
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-100 text-lg dark:bg-green-900/50"
                                    >
                                        ✓
                                    </span>

                                    <div>
                                        <p
                                            class="font-semibold text-green-800 dark:text-green-300"
                                        >
                                            Check your email
                                        </p>

                                        <p
                                            class="mt-1 text-sm leading-6 text-green-700 dark:text-green-400"
                                        >
                                            {{
                                                successMessage ||
                                                'Password recovery instructions have been sent.'
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 space-y-3">
                                <router-link
                                    to="/user/login"
                                    class="flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3.5 font-semibold text-white transition hover:bg-indigo-700"
                                >
                                    Return to sign in
                                </router-link>

                                <button
                                    type="button"
                                    class="w-full rounded-2xl px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                                    @click="tryAnotherEmail"
                                >
                                    Try another email address
                                </button>
                            </div>
                        </div>

                        <!-- Request form -->
                        <form
                            v-else
                            class="mt-8 space-y-5"
                            novalidate
                            @submit.prevent="submitRequest"
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
                                    Registered email address
                                </label>

                                <input
                                    id="email"
                                    v-model.trim="email"
                                    type="email"
                                    inputmode="email"
                                    autocomplete="email"
                                    placeholder="name@example.com"
                                    class="form-input"
                                    :class="emailError ? 'form-input-error' : ''"
                                    @input="clearError"
                                />

                                <p
                                    v-if="emailError"
                                    class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                                >
                                    {{ emailError }}
                                </p>
                            </div>

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
                                        ? 'Sending instructions...'
                                        : 'Send recovery instructions'
                                }}
                            </button>

                            <div
                                class="flex items-center justify-center gap-2 text-sm text-slate-500 dark:text-slate-400"
                            >
                                <span>Remember your password?</span>

                                <router-link
                                    to="/user/login"
                                    class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                                >
                                    Sign in
                                </router-link>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </main>

        <!-- Footer -->
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
                    <a href="#" class="hover:text-indigo-600" @click.prevent>
                        Privacy
                    </a>

                    <a href="#" class="hover:text-indigo-600" @click.prevent>
                        Terms
                    </a>

                    <router-link
                        to="/user/register"
                        class="hover:text-indigo-600"
                    >
                        Create account
                    </router-link>
                </div>
            </div>
        </footer>
    </div>
</template>

<script>
import {
    ref,
} from 'vue';

import axios from 'axios';

export default {
    name: 'ForgotPassword',

    setup() {
        const currentYear = new Date().getFullYear();

        const email = ref('');
        const emailError = ref('');
        const generalError = ref('');
        const successMessage = ref('');

        const loading = ref(false);
        const submitted = ref(false);

        const clearError = () => {
            emailError.value = '';
            generalError.value = '';
        };

        const validateEmail = () => {
            const value = email.value.trim();

            if (!value) {
                emailError.value =
                    'Please enter your registered email address.';
                return false;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                emailError.value =
                    'Please enter a valid email address.';
                return false;
            }

            return true;
        };

        const submitRequest = async () => {
            clearError();

            if (!validateEmail()) {
                return;
            }

            loading.value = true;

            try {
                const response = await axios.post(
                    '/api/v1/auth/forgot-password',
                    {
                        email: email.value.trim().toLowerCase(),
                    }
                );

                successMessage.value =
                    response.data?.message ||
                    'Password recovery instructions have been sent.';

                submitted.value = true;
            } catch (error) {
                const response = error.response?.data;

                const emailMessages =
                    response?.errors?.email;

                emailError.value = Array.isArray(emailMessages)
                    ? emailMessages[0]
                    : '';

                generalError.value =
                    response?.message ||
                    'We could not process your password recovery request.';
            } finally {
                loading.value = false;
            }
        };

        const tryAnotherEmail = () => {
            submitted.value = false;
            successMessage.value = '';
            generalError.value = '';
            emailError.value = '';
            email.value = '';
        };

        return {
            currentYear,
            email,
            emailError,
            generalError,
            successMessage,
            loading,
            submitted,
            clearError,
            submitRequest,
            tryAnotherEmail,
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