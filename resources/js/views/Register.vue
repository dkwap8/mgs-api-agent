<template>
    <div
        class="min-h-screen bg-slate-950 text-slate-950 dark:text-white"
    >
        <div class="grid min-h-screen lg:grid-cols-2">
            <!-- Brand panel -->
            <section
                class="relative hidden overflow-hidden bg-gradient-to-br from-indigo-700 via-indigo-800 to-slate-950 p-12 text-white lg:flex lg:flex-col lg:justify-between"
            >
                <div
                    class="absolute -left-24 top-20 h-72 w-72 rounded-full bg-cyan-400/20 blur-3xl"
                ></div>

                <div
                    class="absolute -bottom-24 right-0 h-96 w-96 rounded-full bg-purple-500/20 blur-3xl"
                ></div>

                <router-link
                    to="/"
                    class="relative z-10 inline-flex items-center gap-3"
                >
                    <span
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-xl font-black text-indigo-700 shadow-xl"
                    >
                        M
                    </span>

                    <span class="text-2xl font-bold">
                        MGS API
                    </span>
                </router-link>

                <div class="relative z-10 max-w-xl">
                    <span
                        class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-medium text-indigo-100 backdrop-blur"
                    >
                        Modern payment infrastructure
                    </span>

                    <h1
                        class="mt-6 text-4xl font-bold leading-tight xl:text-5xl"
                    >
                        Build, collect and manage payments from one platform.
                    </h1>

                    <p
                        class="mt-5 max-w-lg text-lg leading-8 text-indigo-100"
                    >
                        Create your merchant account, verify your email and
                        begin testing MGS payment services immediately.
                    </p>

                    <div class="mt-10 grid gap-4 sm:grid-cols-2">
                        <div
                            class="rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur"
                        >
                            <div class="text-2xl">
                                ⚡
                            </div>

                            <p class="mt-3 font-semibold">
                                Instant test access
                            </p>

                            <p class="mt-1 text-sm text-indigo-100">
                                Receive a test API key immediately after signup.
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur"
                        >
                            <div class="text-2xl">
                                🔐
                            </div>

                            <p class="mt-3 font-semibold">
                                Secure verification
                            </p>

                            <p class="mt-1 text-sm text-indigo-100">
                                Confirm your email with a time-limited OTP.
                            </p>
                        </div>
                    </div>
                </div>

                <p class="relative z-10 text-sm text-indigo-200">
                    © {{ currentYear }} MGS API. All rights reserved.
                </p>
            </section>

            <!-- Form panel -->
            <main
                class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10 dark:bg-slate-950 sm:px-6 lg:px-10"
            >
                <div class="w-full max-w-xl">
                    <!-- Mobile brand -->
                    <router-link
                        to="/"
                        class="mb-8 inline-flex items-center gap-3 lg:hidden"
                    >
                        <span
                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-600 text-lg font-black text-white"
                        >
                            M
                        </span>

                        <span
                            class="text-xl font-bold text-slate-950 dark:text-white"
                        >
                            MGS API
                        </span>
                    </router-link>

                    <div
                        class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/5 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <!-- Progress header -->
                        <div
                            class="border-b border-slate-200 px-6 py-5 dark:border-slate-800 sm:px-8"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p
                                        class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400"
                                    >
                                        Step {{ currentStep }} of 2
                                    </p>

                                    <h2
                                        class="mt-1 text-2xl font-bold text-slate-950 dark:text-white"
                                    >
                                        {{
                                            currentStep === 1
                                                ? 'Create your account'
                                                : 'Verify your email'
                                        }}
                                    </h2>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span
                                        class="h-2.5 w-10 rounded-full"
                                        :class="
                                            currentStep >= 1
                                                ? 'bg-indigo-600'
                                                : 'bg-slate-200 dark:bg-slate-700'
                                        "
                                    ></span>

                                    <span
                                        class="h-2.5 w-10 rounded-full"
                                        :class="
                                            currentStep >= 2
                                                ? 'bg-indigo-600'
                                                : 'bg-slate-200 dark:bg-slate-700'
                                        "
                                    ></span>
                                </div>
                            </div>
                        </div>

                        <!-- Registration step -->
                        <form
                            v-if="currentStep === 1"
                            class="space-y-5 p-6 sm:p-8"
                            novalidate
                            @submit.prevent="sendOtp"
                        >
                            <div>
                                <p
                                    class="text-sm leading-6 text-slate-500 dark:text-slate-400"
                                >
                                    Enter your account and business details.
                                    We will send a six-digit verification code
                                    to your email address.
                                </p>
                            </div>

                            <div
                                v-if="generalError"
                                class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300"
                            >
                                {{ generalError }}
                            </div>

                            <div>
                                <label
                                    for="business_name"
                                    class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                >
                                    Business name
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="business_name"
                                    v-model.trim="form.business_name"
                                    type="text"
                                    maxlength="255"
                                    autocomplete="organization"
                                    placeholder="e.g. Acme Technologies Ltd"
                                    class="form-input"
                                    :class="inputErrorClass('business_name')"
                                    @input="clearFieldError('business_name')"
                                />

                                <p
                                    v-if="errors.business_name"
                                    class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                                >
                                    {{ errors.business_name }}
                                </p>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label
                                        for="first_name"
                                        class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                    >
                                        First name
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        id="first_name"
                                        v-model.trim="form.first_name"
                                        type="text"
                                        maxlength="100"
                                        autocomplete="given-name"
                                        placeholder="First name"
                                        class="form-input"
                                        :class="inputErrorClass('first_name')"
                                        @input="clearFieldError('first_name')"
                                    />

                                    <p
                                        v-if="errors.first_name"
                                        class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                                    >
                                        {{ errors.first_name }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="last_name"
                                        class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                    >
                                        Last name
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        id="last_name"
                                        v-model.trim="form.last_name"
                                        type="text"
                                        maxlength="100"
                                        autocomplete="family-name"
                                        placeholder="Last name"
                                        class="form-input"
                                        :class="inputErrorClass('last_name')"
                                        @input="clearFieldError('last_name')"
                                    />

                                    <p
                                        v-if="errors.last_name"
                                        class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                                    >
                                        {{ errors.last_name }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label
                                    for="email"
                                    class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                >
                                    Email address
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="email"
                                    v-model.trim="form.email"
                                    type="email"
                                    maxlength="255"
                                    autocomplete="email"
                                    inputmode="email"
                                    placeholder="name@example.com"
                                    class="form-input"
                                    :class="inputErrorClass('email')"
                                    @input="clearFieldError('email')"
                                />

                                <p
                                    v-if="errors.email"
                                    class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                                >
                                    {{ errors.email }}
                                </p>
                            </div>

                            <div>
                                <label
                                    for="phone"
                                    class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                >
                                    Phone number
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-500 dark:text-slate-400"
                                    >
                                        +234
                                    </span>

                                    <input
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        maxlength="11"
                                        autocomplete="tel"
                                        inputmode="numeric"
                                        placeholder="08012345678"
                                        class="form-input pl-16"
                                        :class="inputErrorClass('phone')"
                                        @input="handlePhoneInput"
                                    />
                                </div>

                                <div
                                    class="mt-1.5 flex items-center justify-between"
                                >
                                    <p
                                        class="text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        Nigerian phone number containing exactly 11 digits.
                                    </p>

                                    <span
                                        class="text-xs font-medium"
                                        :class="
                                            form.phone.length === 11
                                                ? 'text-green-600 dark:text-green-400'
                                                : 'text-slate-400'
                                        "
                                    >
                                        {{ form.phone.length }}/11
                                    </span>
                                </div>

                                <p
                                    v-if="errors.phone"
                                    class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                                >
                                    {{ errors.phone }}
                                </p>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label
                                        for="password"
                                        class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                    >
                                        Password
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <div class="relative">
                                        <input
                                            id="password"
                                            v-model="form.password"
                                            :type="
                                                showPassword
                                                    ? 'text'
                                                    : 'password'
                                            "
                                            autocomplete="new-password"
                                            placeholder="At least 8 characters"
                                            class="form-input pr-12"
                                            :class="inputErrorClass('password')"
                                            @input="clearFieldError('password')"
                                        />

                                        <button
                                            type="button"
                                            class="absolute inset-y-0 right-0 flex items-center px-4 text-sm text-slate-500 hover:text-indigo-600 dark:text-slate-400"
                                            @click="
                                                showPassword = !showPassword
                                            "
                                        >
                                            {{
                                                showPassword
                                                    ? 'Hide'
                                                    : 'Show'
                                            }}
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
                                    <label
                                        for="password_confirmation"
                                        class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                    >
                                        Confirm password
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        :type="
                                            showPassword
                                                ? 'text'
                                                : 'password'
                                        "
                                        autocomplete="new-password"
                                        placeholder="Repeat password"
                                        class="form-input"
                                        :class="
                                            inputErrorClass(
                                                'password_confirmation'
                                            )
                                        "
                                        @input="
                                            clearFieldError(
                                                'password_confirmation'
                                            )
                                        "
                                    />

                                    <p
                                        v-if="errors.password_confirmation"
                                        class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                                    >
                                        {{ errors.password_confirmation }}
                                    </p>
                                </div>
                            </div>

                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 dark:border-slate-700"
                            >
                                <input
                                    v-model="acceptTerms"
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />

                                <span
                                    class="text-sm leading-6 text-slate-600 dark:text-slate-400"
                                >
                                    I agree to the
                                    <a
                                        href="#"
                                        class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                                        @click.prevent
                                    >
                                        Terms of Service
                                    </a>
                                    and
                                    <a
                                        href="#"
                                        class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                                        @click.prevent
                                    >
                                        Privacy Policy
                                    </a>.
                                </span>
                            </label>

                            <p
                                v-if="errors.accept_terms"
                                class="text-sm text-red-600 dark:text-red-400"
                            >
                                {{ errors.accept_terms }}
                            </p>

                            <button
                                type="submit"
                                :disabled="sendingOtp"
                                class="flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3.5 font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <svg
                                    v-if="sendingOtp"
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
                                    sendingOtp
                                        ? 'Sending verification code...'
                                        : 'Send verification code'
                                }}
                            </button>

                            <p
                                class="text-center text-sm text-slate-500 dark:text-slate-400"
                            >
                                Already have an account?

                                <router-link
                                    to="/user/login"
                                    class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                                >
                                    Sign in
                                </router-link>
                            </p>
                        </form>

                        <!-- OTP step -->
                        <form
                            v-else
                            class="space-y-6 p-6 sm:p-8"
                            novalidate
                            @submit.prevent="verifyOtp"
                        >
                            <div class="text-center">
                                <div
                                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-100 text-3xl dark:bg-indigo-950"
                                >
                                    ✉️
                                </div>

                                <h3
                                    class="mt-5 text-xl font-bold text-slate-950 dark:text-white"
                                >
                                    Check your email
                                </h3>

                                <p
                                    class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400"
                                >
                                    We sent a six-digit verification code to
                                </p>

                                <p
                                    class="font-semibold text-slate-950 dark:text-white"
                                >
                                    {{ form.email }}
                                </p>
                            </div>

                            <div
                                v-if="successMessage"
                                class="rounded-2xl border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-950/40 dark:text-green-300"
                            >
                                {{ successMessage }}
                            </div>

                            <div
                                v-if="generalError"
                                class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300"
                            >
                                {{ generalError }}
                            </div>

                            <div>
                                <label
                                    for="otp"
                                    class="mb-3 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300"
                                >
                                    Verification code
                                </label>

                                <input
                                    ref="otpInput"
                                    id="otp"
                                    v-model="otp"
                                    type="text"
                                    maxlength="6"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    placeholder="000000"
                                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-4 text-center font-mono text-3xl font-bold tracking-[0.5em] text-slate-950 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                                    :class="
                                        errors.otp
                                            ? 'border-red-500 focus:border-red-500 focus:ring-red-500/10'
                                            : ''
                                    "
                                    @input="handleOtpInput"
                                />

                                <p
                                    v-if="errors.otp"
                                    class="mt-2 text-center text-sm text-red-600 dark:text-red-400"
                                >
                                    {{ errors.otp }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl bg-slate-50 p-4 text-center dark:bg-slate-950"
                            >
                                <p
                                    v-if="secondsRemaining > 0"
                                    class="text-sm text-slate-500 dark:text-slate-400"
                                >
                                    You can request another code in
                                    <span
                                        class="font-semibold text-slate-950 dark:text-white"
                                    >
                                        {{ formattedCountdown }}
                                    </span>
                                </p>

                                <button
                                    v-else
                                    type="button"
                                    :disabled="resendingOtp"
                                    class="text-sm font-semibold text-indigo-600 hover:underline disabled:opacity-50 dark:text-indigo-400"
                                    @click="resendOtp"
                                >
                                    {{
                                        resendingOtp
                                            ? 'Sending...'
                                            : 'Resend verification code'
                                    }}
                                </button>
                            </div>

                            <button
                                type="submit"
                                :disabled="verifyingOtp || otp.length !== 6"
                                class="flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3.5 font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <svg
                                    v-if="verifyingOtp"
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
                                    verifyingOtp
                                        ? 'Creating your account...'
                                        : 'Verify and create account'
                                }}
                            </button>

                            <button
                                type="button"
                                class="w-full text-sm font-semibold text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400"
                                :disabled="verifyingOtp"
                                @click="returnToRegistration"
                            >
                                ← Change registration details
                            </button>
                        </form>
                    </div>

                    <p
                        class="mt-6 text-center text-xs leading-5 text-slate-400"
                    >
                        Your account information is protected with encrypted
                        connections and secure authentication.
                    </p>
                </div>
            </main>
        </div>
    </div>
</template>

<script>
import {
    computed,
    nextTick,
    onBeforeUnmount,
    ref,
} from 'vue';

import {
    useRouter,
} from 'vue-router';

import axios from 'axios';

export default {
    name: 'Register',

    setup() {
        const router = useRouter();

        const currentYear = new Date().getFullYear();
        const currentStep = ref(1);

        const form = ref({
            business_name: '',
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            password: '',
            password_confirmation: '',
        });

        const otp = ref('');
        const otpInput = ref(null);
        const acceptTerms = ref(false);
        const showPassword = ref(false);

        const sendingOtp = ref(false);
        const verifyingOtp = ref(false);
        const resendingOtp = ref(false);

        const errors = ref({});
        const generalError = ref('');
        const successMessage = ref('');

        const secondsRemaining = ref(0);
        let countdownTimer = null;

        const formattedCountdown = computed(() => {
            const minutes = Math.floor(secondsRemaining.value / 60);
            const seconds = secondsRemaining.value % 60;

            return `${String(minutes).padStart(2, '0')}:${String(
                seconds
            ).padStart(2, '0')}`;
        });

        const clearCountdown = () => {
            if (countdownTimer) {
                window.clearInterval(countdownTimer);
                countdownTimer = null;
            }
        };

        const startCountdown = (seconds = 60) => {
            clearCountdown();

            secondsRemaining.value = seconds;

            countdownTimer = window.setInterval(() => {
                if (secondsRemaining.value <= 1) {
                    secondsRemaining.value = 0;
                    clearCountdown();
                    return;
                }

                secondsRemaining.value -= 1;
            }, 1000);
        };

        const normalizeErrors = (serverErrors = {}) => {
            const normalized = {};

            Object.entries(serverErrors).forEach(([field, messages]) => {
                normalized[field] = Array.isArray(messages)
                    ? messages[0]
                    : String(messages);
            });

            return normalized;
        };

        const clearFieldError = (field) => {
            if (errors.value[field]) {
                delete errors.value[field];
            }

            generalError.value = '';
        };

        const inputErrorClass = (field) => {
            return errors.value[field]
                ? 'border-red-500 focus:border-red-500 focus:ring-red-500/10'
                : '';
        };

        const handlePhoneInput = (event) => {
            form.value.phone = String(event.target.value || '')
                .replace(/\D/g, '')
                .slice(0, 11);

            clearFieldError('phone');
        };

        const handleOtpInput = (event) => {
            otp.value = String(event.target.value || '')
                .replace(/\D/g, '')
                .slice(0, 6);

            clearFieldError('otp');

            if (otp.value.length === 6 && !verifyingOtp.value) {
                verifyOtp();
            }
        };

        const validateRegistrationForm = () => {
            const validationErrors = {};

            if (!form.value.business_name.trim()) {
                validationErrors.business_name =
                    'Please enter your business name.';
            }

            if (!form.value.first_name.trim()) {
                validationErrors.first_name =
                    'Please enter your first name.';
            }

            if (!form.value.last_name.trim()) {
                validationErrors.last_name =
                    'Please enter your last name.';
            }

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

            if (!/^\d{11}$/.test(form.value.phone)) {
                validationErrors.phone =
                    'Your phone number must contain exactly 11 digits.';
            }

            if (!form.value.password) {
                validationErrors.password =
                    'Please enter a password.';
            } else if (form.value.password.length < 8) {
                validationErrors.password =
                    'Your password must contain at least 8 characters.';
            }

            if (
                form.value.password_confirmation !==
                form.value.password
            ) {
                validationErrors.password_confirmation =
                    'The password confirmation does not match.';
            }

            if (!acceptTerms.value) {
                validationErrors.accept_terms =
                    'You must accept the Terms of Service and Privacy Policy.';
            }

            errors.value = validationErrors;

            return Object.keys(validationErrors).length === 0;
        };

        const sendOtp = async () => {
            generalError.value = '';
            successMessage.value = '';

            if (!validateRegistrationForm()) {
                return;
            }

            sendingOtp.value = true;

            try {
                const response = await axios.post(
                    '/api/v1/auth/register/send-otp',
                    {
                        business_name:
                            form.value.business_name.trim(),
                        first_name:
                            form.value.first_name.trim(),
                        last_name:
                            form.value.last_name.trim(),
                        email:
                            form.value.email.trim().toLowerCase(),
                        phone: form.value.phone,
                        password: form.value.password,
                        password_confirmation:
                            form.value.password_confirmation,
                    }
                );

                currentStep.value = 2;
                otp.value = '';
                errors.value = {};
                successMessage.value =
                    response.data?.message ||
                    'A verification code has been sent.';

                startCountdown(60);

                await nextTick();

                otpInput.value?.focus();
            } catch (error) {
                const response = error.response?.data;

                errors.value = normalizeErrors(
                    response?.errors || {}
                );

                generalError.value =
                    response?.message ||
                    'We could not send the verification code. Please try again.';
            } finally {
                sendingOtp.value = false;
            }
        };

        const storeAuthenticatedSession = (data) => {
            localStorage.removeItem('token');
            localStorage.removeItem('merchant_token');
            localStorage.setItem('merchant_session', '1');

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
                JSON.stringify(
                    data.businesses ||
                    (data.business ? [data.business] : [])
                )
            );

            localStorage.removeItem('merchants');

            delete axios.defaults.headers.common.Authorization;
        };

        const verifyOtp = async () => {
            if (verifyingOtp.value) {
                return;
            }

            generalError.value = '';
            successMessage.value = '';

            if (!/^\d{6}$/.test(otp.value)) {
                errors.value.otp =
                    'Please enter the complete six-digit verification code.';
                return;
            }

            verifyingOtp.value = true;

            try {
                const response = await axios.post(
                    '/api/v1/auth/register/verify-otp',
                    {
                        email:
                            form.value.email.trim().toLowerCase(),
                        otp: otp.value,
                    }
                );

                const data = response.data?.data || {};

                if (!data.browser_session) {
                    throw new Error(
                        'The server did not establish a browser session.'
                    );
                }

                storeAuthenticatedSession(data);

                clearCountdown();

                await router.replace('/user/dashboard');
            } catch (error) {
                const response = error.response?.data;

                errors.value = {
                    ...errors.value,
                    ...normalizeErrors(response?.errors || {}),
                };

                generalError.value =
                    response?.message ||
                    error.message ||
                    'The verification could not be completed. Please try again.';

                otp.value = '';

                await nextTick();

                otpInput.value?.focus();
            } finally {
                verifyingOtp.value = false;
            }
        };

        const resendOtp = async () => {
            if (resendingOtp.value) {
                return;
            }

            generalError.value = '';
            successMessage.value = '';
            errors.value.otp = '';

            resendingOtp.value = true;

            try {
                const response = await axios.post(
                    '/api/v1/auth/register/resend-otp',
                    {
                        email:
                            form.value.email.trim().toLowerCase(),
                    }
                );

                successMessage.value =
                    response.data?.message ||
                    'A new verification code has been sent.';

                otp.value = '';
                startCountdown(60);

                await nextTick();

                otpInput.value?.focus();
            } catch (error) {
                generalError.value =
                    error.response?.data?.message ||
                    'We could not resend the verification code.';
            } finally {
                resendingOtp.value = false;
            }
        };

        const returnToRegistration = () => {
            clearCountdown();

            currentStep.value = 1;
            otp.value = '';
            errors.value = {};
            generalError.value = '';
            successMessage.value = '';
        };

        onBeforeUnmount(() => {
            clearCountdown();
        });

        return {
            currentYear,
            currentStep,
            form,
            otp,
            otpInput,
            acceptTerms,
            showPassword,
            sendingOtp,
            verifyingOtp,
            resendingOtp,
            errors,
            generalError,
            successMessage,
            secondsRemaining,
            formattedCountdown,
            clearFieldError,
            inputErrorClass,
            handlePhoneInput,
            handleOtpInput,
            sendOtp,
            verifyOtp,
            resendOtp,
            returnToRegistration,
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
    padding: 0.8rem 1rem;
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

:global(.dark) .form-input {
    border-color: rgb(51 65 85);
    background: rgb(2 6 23);
    color: white;
}
</style>