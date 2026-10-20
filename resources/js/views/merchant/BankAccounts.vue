<template>
<section class="space-y-5 text-slate-900 dark:text-slate-100"><h1 class="text-2xl font-bold">Settlement Bank Accounts</h1><p>Confirm a new account with an email code. Withdrawals to new accounts become available after 24 hours.</p>
<p v-if="error" role="alert" class="text-red-600">{{ error }}</p><p v-if="message" role="status">{{ message }}</p>
<div v-for="a in accounts" :key="a.id" class="flex flex-wrap justify-between gap-3 rounded-xl border p-4"><div><p class="font-bold">{{ a.bank_name }} ? {{ a.account_name }}</p><p>Account ending {{ a.account_number?.slice(-4) }}</p><p v-if="a.withdrawals_available_at">Withdrawals available {{ new Date(a.withdrawals_available_at).toLocaleString() }}</p></div><button :disabled="busy" @click="remove(a)">Remove</button></div>
<form class="grid gap-4 rounded-xl border p-5 sm:grid-cols-2" @submit.prevent="save">
<label>Bank name<input v-model="form.bank_name" required class="input" :disabled="!!challenge" /></label><label>Bank code<input v-model="form.bank_code" required pattern="[0-9]{3}" maxlength="3" class="input" :disabled="!!challenge" /></label>
<label>Account number<input v-model="form.account_number" required pattern="[0-9]{10}" maxlength="10" inputmode="numeric" class="input" :disabled="!!challenge" /></label><label>Account name<input v-model="form.account_name" required class="input" :disabled="!!challenge" /></label>
<label v-if="!challenge">Your password<input v-model="password" type="password" autocomplete="current-password" required class="input" /></label>
<label v-if="challenge">Email confirmation code<input v-model="otp" pattern="[0-9]{6}" maxlength="6" inputmode="numeric" autocomplete="one-time-code" required class="input" /></label>
<div class="flex gap-4"><button v-if="!challenge" type="button" :disabled="busy" class="rounded-lg bg-indigo-600 px-4 py-2 text-white" @click="send">Send confirmation code</button><button v-else :disabled="busy" class="rounded-lg bg-indigo-600 px-4 py-2 text-white">Verify and save</button><button v-if="challenge" type="button" @click="challenge='';otp=''">Start again</button></div>
</form></section>
</template>
<script setup>
import {ref,onMounted} from 'vue';import axios from 'axios';import {verificationModal} from '../../services/verificationModal';
const accounts=ref([]),form=ref({bank_name:'',bank_code:'',account_number:'',account_name:''}),password=ref(''),otp=ref(''),challenge=ref(''),busy=ref(false),error=ref(''),message=ref('');
const failure=e=>{error.value=Object.values(e.response?.data?.errors||{}).flat().join(' ')||e.response?.data?.message||'The bank operation could not be completed.';};
async function load(){try{accounts.value=(await axios.get('/api/v1/bank-accounts')).data.data||[];}catch(e){failure(e);}}
async function send(){busy.value=true;error.value='';try{const {data}=await axios.post('/api/v1/merchant/bank-change/send-otp',{bank_code:form.value.bank_code,account_number:form.value.account_number,password:password.value});challenge.value=data.data.challenge_reference;password.value='';message.value='Confirmation code sent to your registered email.';}catch(e){failure(e);}finally{busy.value=false;}}
async function save(){busy.value=true;error.value='';try{await axios.post('/api/v1/bank-accounts',{...form.value,otp:otp.value,challenge_reference:challenge.value},{headers:{'X-Idempotency-Key':challenge.value}});message.value='Verified account saved. Withdrawals to this account are held for 24 hours.';form.value={bank_name:'',bank_code:'',account_number:'',account_name:''};await load();}catch(e){failure(e);}finally{challenge.value='';otp.value='';busy.value=false;}}
async function remove(a){if(!await verificationModal.confirm({title:'Remove settlement account',message:'Remove the account ending '+a.account_number.slice(-4)+'?',confirmText:'Remove account'}))return;busy.value=true;error.value='';try{await axios.delete('/api/v1/bank-accounts/'+a.id);await load();}catch(e){failure(e);}finally{busy.value=false;}}
onMounted(load);
</script>
<style scoped>.input{display:block;width:100%;border:1px solid #94a3b8;border-radius:.5rem;padding:.5rem;background:transparent;margin-top:.4rem;}</style>
