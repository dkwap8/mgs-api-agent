<template>
    <section aria-label="Account security" class="space-y-4">
        <p v-if="error" role="alert" class="text-sm text-red-600">{{ error }}</p>
        <p v-if="notice" role="status" class="text-sm text-green-700 dark:text-green-300">{{ notice }}</p>
        <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
            <h4 class="font-semibold">Authenticator authentication</h4>
            <p class="mt-1 text-sm text-slate-500">{{ enabled ? 'Enabled. An authenticator or single-use recovery code is required at login.' : 'Use an authenticator app for a second login factor.' }}</p>
            <form class="mt-3 space-y-3" @submit.prevent="secret ? confirm() : setup()">
                <label class="block text-sm">Current password<input v-model="password" type="password" autocomplete="current-password" class="mt-1 block w-full rounded-xl border bg-transparent p-2" required></label>
                <template v-if="secret">
                    <p class="break-all text-sm">Add this setup key to your authenticator: <code>{{ secret }}</code></p>
                    <p class="text-xs text-slate-500">Account: {{ accountEmail }}; issuer: MGSAPI; six digits, every 30 seconds. Keep the key private.</p>
                </template>
                <label v-if="secret || enabled" class="block text-sm">Authenticator code<input v-model="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="mt-1 block w-full rounded-xl border bg-transparent p-2"></label>
                <button v-if="!enabled" type="submit" :disabled="busy" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm text-white disabled:opacity-50">{{ secret ? 'Confirm authenticator' : 'Set up authenticator' }}</button>
                <div v-else class="flex flex-wrap gap-2">
                    <button type="button" :disabled="busy" class="rounded-xl border px-4 py-2 text-sm disabled:opacity-50" @click="regenerate">Replace recovery codes</button>
                    <button type="button" :disabled="busy" class="rounded-xl border px-4 py-2 text-sm text-red-600 disabled:opacity-50" @click="disable">Disable authenticator</button>
                </div>
            </form>
            <div v-if="recoveryCodes.length" class="mt-4 rounded-xl border p-3">
                <p class="text-sm font-semibold">Save these recovery codes privately. Each works once.</p>
                <ul class="mt-2 grid grid-cols-2 gap-2 font-mono text-sm"><li v-for="item in recoveryCodes" :key="item">{{ item }}</li></ul>
                <button type="button" class="mt-3 text-sm underline" @click="recoveryCodes=[]">I saved my codes</button>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
            <div class="flex items-center justify-between"><h4 class="font-semibold">Devices</h4><button type="button" class="text-sm underline" :disabled="busy" @click="refresh">Refresh devices</button></div>
            <p v-if="!devices.length" class="mt-3 text-sm text-slate-500">No devices recorded.</p>
            <ul class="divide-y dark:divide-slate-700"><li v-for="device in devices" :key="device.id" class="flex items-center justify-between gap-3 py-3">
                <div class="min-w-0"><p class="break-words text-sm">{{ device.device_name || device.platform || 'Unknown device' }}</p><p class="text-xs text-slate-500">{{ device.ip_address || 'IP unavailable' }} · Last seen {{ device.last_seen_at ? new Date(device.last_seen_at).toLocaleString() : 'unknown' }}</p></div>
                <span v-if="device.revoked_at" class="text-xs text-slate-500">Revoked</span><button v-else type="button" :disabled="busy" class="text-sm text-red-600" @click="revoke(device)">Revoke device</button>
            </li></ul>
            <button type="button" :disabled="busy" class="mt-3 text-sm text-red-600 underline" @click="logoutAll">Sign out all sessions</button>
        </div>
    </section>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
const busy=ref(false), enabled=ref(false), password=ref(''), code=ref(''), secret=ref(''), recoveryCodes=ref([]), devices=ref([]), error=ref(''), notice=ref(''), accountEmail=ref('');
async function operation(work) { if(busy.value)return; busy.value=true;error.value='';notice.value='';try {await work();}catch(failure){error.value=failure.response?.data?.message || 'The security operation could not be completed.';}finally{busy.value=false;} }
async function refresh() { await operation(async()=>{const [me,list]=await Promise.all([axios.get('/api/v1/auth/me',{suppressVerificationModal:true}),axios.get('/api/v1/auth/devices',{suppressVerificationModal:true})]);const user=me.data.data?.user || {}; enabled.value=Boolean(user.two_factor_confirmed_at);accountEmail.value=user.email || '';devices.value=list.data.data || [];}); }
async function setup() { await operation(async()=>{const result=await axios.post('/api/v1/auth/setup-two-factor',{password:password.value},{suppressVerificationModal:true});secret.value=result.data.data.secret;}); }
async function confirm() { await operation(async()=>{const result=await axios.post('/api/v1/auth/verify-two-factor',{password:password.value,code:code.value},{suppressVerificationModal:true});recoveryCodes.value=result.data.data.recovery_codes;enabled.value=true;secret.value='';password.value='';code.value='';notice.value='Authenticator enabled. Save the recovery codes below.';}); }
async function regenerate() { await operation(async()=>{const result=await axios.post('/api/v1/auth/two-factor/recovery-codes',{password:password.value,code:code.value},{suppressVerificationModal:true});recoveryCodes.value=result.data.data.recovery_codes;password.value='';code.value='';}); }
async function disable() { await operation(async()=>{await axios.delete('/api/v1/auth/two-factor',{data:{password:password.value,code:code.value},suppressVerificationModal:true});enabled.value=false;password.value='';code.value='';recoveryCodes.value=[];notice.value='Authenticator disabled.';}); }
async function revoke(device) { await operation(async()=>{await axios.delete(`/api/v1/auth/devices/${device.id}`,{suppressVerificationModal:true});device.revoked_at=new Date().toISOString();notice.value='Device access revoked.';}); }
async function logoutAll() { await operation(async()=>{await axios.post('/api/v1/auth/logout-all',{}, {suppressVerificationModal:true});localStorage.removeItem('merchant_token'); localStorage.removeItem('merchant_session');localStorage.removeItem('user');window.location.assign('/user/login');}); }
onMounted(refresh);
</script>
