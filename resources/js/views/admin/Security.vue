<template>
    <section class="mx-auto max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
        <h1 class="text-2xl font-bold">Administrator security</h1>
        <p class="text-sm text-slate-500">Use an authenticator app to protect administrator access. Keep recovery codes in a secure offline location.</p>
        <p v-if="error" role="alert" class="text-red-600">{{ error }}</p>
        <p v-if="message" role="status" class="text-emerald-600">{{ message }}</p>
        <label class="block">Current password<input v-model="password" type="password" autocomplete="current-password" class="mt-2 w-full rounded-xl border bg-transparent p-3" /></label>
        <button v-if="!enabled && !secret" type="button" :disabled="busy" class="rounded-xl bg-indigo-600 px-5 py-3 text-white disabled:opacity-50" @click="setup">Set up authenticator</button>
        <div v-if="secret" class="space-y-3">
            <p>Add this setup key to your authenticator app with the account label MGS API. Never share it.</p>
            <code class="block break-all rounded-xl bg-slate-100 p-3 text-slate-950">{{ secret }}</code>
            <label class="block">Authenticator code<input v-model.trim="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="mt-2 w-full rounded-xl border bg-transparent p-3" /></label>
            <button type="button" :disabled="busy" class="rounded-xl bg-indigo-600 px-5 py-3 text-white" @click="confirm">Confirm MFA setup</button>
        </div>
        <div v-if="enabled && !codes.length" class="space-y-3">
            <p>MFA is enabled.</p>
            <label class="block">New authenticator code<input v-model.trim="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="mt-2 w-full rounded-xl border bg-transparent p-3" /></label>
            <button type="button" :disabled="busy" class="rounded-xl border px-5 py-3" @click="regenerate">Replace recovery codes</button>
        </div>
        <div v-if="codes.length" class="space-y-3"><p class="font-bold">Save these codes now. Each code works once and will not be shown again.</p><pre class="rounded-xl bg-slate-100 p-4 text-slate-950">{{ codes.join('\n') }}</pre><router-link to="/admin-panel" class="inline-block rounded-xl bg-indigo-600 px-5 py-3 text-white">Return to administrator login</router-link></div>
    </section>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
const password = ref(''), code = ref(''), secret = ref(''), codes = ref([]), enabled = ref(false), busy = ref(false), error = ref(''), message = ref('');
async function request(action) {
    busy.value = true; error.value = '';
    try { return (await axios.post(`/api/v1/admin/auth/mfa/${action}`, { password: password.value, code: code.value }, { suppressVerificationModal: true })).data; }
    catch (failure) { error.value = failure.response?.data?.message || 'Security settings could not be updated. Please retry.'; }
    finally { busy.value = false; }
}
async function setup() { const result = await request('setup'); if (result) secret.value = result.data.secret; }
async function confirm() {
    const result = await request('confirm');
    if (result) { secret.value = ''; password.value = ''; code.value = ''; codes.value = result.data.recovery_codes; message.value = result.message; localStorage.removeItem('admin_token'); localStorage.removeItem('admin_session'); localStorage.removeItem('admin'); }
}
async function regenerate() { const result = await request('recovery-codes'); if (result) { codes.value = result.data.recovery_codes; password.value = ''; code.value = ''; } }
onMounted(async () => { try { enabled.value = Boolean((await axios.get('/api/v1/admin/auth/me', { suppressVerificationModal: true })).data.data.admin.two_factor_confirmed_at); } catch { error.value = 'Please sign in again to manage administrator security.'; } });
</script>
