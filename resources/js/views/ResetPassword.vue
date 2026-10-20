<template>
    <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6">
        <section class="ui-card grid w-full max-w-5xl overflow-hidden lg:grid-cols-[.9fr_1.1fr]">
            <div class="relative hidden overflow-hidden bg-gradient-to-br from-indigo-700 via-violet-700 to-slate-950 p-10 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="absolute -left-20 top-16 h-64 w-64 rounded-full bg-cyan-400/20 blur-3xl"></div>
                <div class="absolute -bottom-24 right-0 h-80 w-80 rounded-full bg-fuchsia-500/20 blur-3xl"></div>
                <div class="relative z-10">
                    <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold backdrop-blur">Secure account recovery</span>
                    <h1 class="mt-7 text-4xl font-black leading-tight">Create a strong new password.</h1>
                    <p class="mt-5 max-w-md leading-7 text-indigo-100">Your new password signs out existing sessions and protects every business connected to your merchant account.</p>
                </div>
                <div class="relative z-10 rounded-2xl border border-white/15 bg-white/10 p-5 text-sm leading-6 text-indigo-100 backdrop-blur">
                    Use at least 10 characters with uppercase, lowercase, numbers and symbols. Never reuse a password from another service.
                </div>
            </div>

            <div class="p-6 sm:p-10 lg:p-12">
                <div class="mx-auto max-w-md">
                    <p class="text-xs font-black uppercase tracking-[.2em] text-indigo-600 dark:text-indigo-400">Password reset</p>
                    <h2 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">Set a new password</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">Enter the email address from the recovery message and choose a secure password.</p>

                    <form class="mt-8 space-y-5" novalidate @submit.prevent="submit">
                        <div v-if="generalError" role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300">{{ generalError }}</div>

                        <div class="form-field">
                            <input id="reset-email" v-model.trim="form.email" class="form-control" type="email" inputmode="email" autocomplete="email" placeholder=" " :aria-invalid="Boolean(errors.email)" @input="clear('email')">
                            <label class="form-label-floating" for="reset-email">Email address</label>
                            <p v-if="errors.email" class="form-help-error" role="alert">{{ errors.email }}</p>
                        </div>

                        <div class="form-field">
                            <input id="reset-password" v-model="form.password" class="form-control pr-14" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" placeholder=" " :aria-invalid="Boolean(errors.password)" @input="clear('password')" @keydown="detectCapsLock" @keyup="detectCapsLock">
                            <label class="form-label-floating" for="reset-password">New password</label>
                            <button type="button" class="absolute right-3 top-3 flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-xs font-bold text-indigo-600 hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-slate-800" :aria-label="showPassword ? 'Hide password' : 'Show password'" @click="showPassword = !showPassword">{{ showPassword ? 'Hide' : 'Show' }}</button>
                            <p v-if="capsLock" class="mt-2 text-xs font-bold text-amber-600 dark:text-amber-400">Caps Lock is on.</p>
                            <p v-if="errors.password" class="form-help-error" role="alert">{{ errors.password }}</p>
                        </div>

                        <div aria-live="polite">
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span class="text-slate-500 dark:text-slate-400">Password strength</span>
                                <span :class="strengthTextClass">{{ strengthLabel }}</span>
                            </div>
                            <div class="mt-2 grid grid-cols-4 gap-2" aria-hidden="true">
                                <span v-for="index in 4" :key="index" class="h-1.5 rounded-full" :class="index <= strength ? strengthBarClass : 'bg-slate-200 dark:bg-slate-700'"></span>
                            </div>
                        </div>

                        <div class="form-field">
                            <input id="reset-password-confirmation" v-model="form.password_confirmation" class="form-control pr-14" :type="showConfirmation ? 'text' : 'password'" autocomplete="new-password" placeholder=" " :aria-invalid="Boolean(errors.password_confirmation)" @input="clear('password_confirmation')">
                            <label class="form-label-floating" for="reset-password-confirmation">Confirm new password</label>
                            <button type="button" class="absolute right-3 top-3 flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-xs font-bold text-indigo-600 hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-slate-800" :aria-label="showConfirmation ? 'Hide password confirmation' : 'Show password confirmation'" @click="showConfirmation = !showConfirmation">{{ showConfirmation ? 'Hide' : 'Show' }}</button>
                            <p v-if="errors.password_confirmation" class="form-help-error" role="alert">{{ errors.password_confirmation }}</p>
                        </div>

                        <button type="submit" class="ui-button ui-button-primary w-full" :disabled="loading">
                            <svg v-if="loading" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            {{ loading ? 'Resetting password…' : 'Reset password' }}
                        </button>

                        <router-link to="/user/login" class="flex min-h-11 items-center justify-center rounded-xl text-sm font-bold text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-slate-800">Return to sign in</router-link>
                    </form>
                </div>
            </div>
        </section>
    </main>
</template>

<script>
import verificationModal from '../services/verificationModal.js';

export default {
    name: 'ResetPassword',
    data() {
        return {
            loading: false,
            showPassword: false,
            showConfirmation: false,
            capsLock: false,
            generalError: '',
            errors: {},
            form: {
                email: String(this.$route.query.email || ''),
                token: String(this.$route.query.token || ''),
                password: '',
                password_confirmation: '',
            },
        };
    },
    computed: {
        strength() {
            const value = this.form.password;
            if (!value) return 0;
            let score = 0;
            if (value.length >= 10) score += 1;
            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score += 1;
            if (/\d/.test(value)) score += 1;
            if (/[^A-Za-z0-9]/.test(value)) score += 1;
            return score;
        },
        strengthLabel() { return ['Not set', 'Weak', 'Fair', 'Good', 'Strong'][this.strength]; },
        strengthBarClass() { return ['bg-slate-300', 'bg-red-500', 'bg-amber-500', 'bg-sky-500', 'bg-emerald-500'][this.strength]; },
        strengthTextClass() { return ['text-slate-500', 'text-red-600', 'text-amber-600', 'text-sky-600', 'text-emerald-600'][this.strength]; },
    },
    mounted() {
        if (!this.form.token) {
            this.generalError = 'This password reset link is incomplete. Request a new recovery email.';
        }
    },
    methods: {
        clear(field) { delete this.errors[field]; this.generalError = ''; },
        detectCapsLock(event) { this.capsLock = Boolean(event.getModifierState?.('CapsLock')); },
        validate() {
            const errors = {};
            if (!/^\S+@\S+\.\S+$/.test(this.form.email)) errors.email = 'Enter a valid email address.';
            if (!this.form.token) this.generalError = 'The reset token is missing. Request a new recovery email.';
            if (this.form.password.length < 10) errors.password = 'Use at least 10 characters.';
            if (this.strength < 3) errors.password = 'Use uppercase, lowercase, a number and preferably a symbol.';
            if (this.form.password_confirmation !== this.form.password) errors.password_confirmation = 'The passwords do not match.';
            this.errors = errors;
            return Object.keys(errors).length === 0 && Boolean(this.form.token);
        },
        async submit() {
            if (!this.validate()) return;
            this.loading = true;
            try {
                const response = await window.axios.post('/api/v1/auth/reset-password', this.form, { suppressVerificationModal: true });
                verificationModal.success({ title: 'Password reset successful', message: response.data?.message || 'Your password has been updated. Sign in again with the new password.' });
                await this.$router.replace('/user/login');
            } catch (error) {
                const payload = error.response?.data || {};
                this.errors = payload.errors || {};
                this.generalError = payload.message || 'The password could not be reset. Request a new recovery link and try again.';
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
