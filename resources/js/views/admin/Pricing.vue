<template>
<section class="space-y-5 text-slate-900 dark:text-slate-100">
<h1 class="text-2xl font-bold">Collection Pricing</h1><p class="text-sm text-slate-500">Active collection ranges must not overlap. Confirm the Blueprint's decimal gaps and provider costs before activation. Payout fees are configured in Withdrawal Settings.</p>
<p v-if="message" role="status">{{ message }}</p><p v-if="error" role="alert" class="text-red-600">{{ error }}</p>
<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr><th class="p-2">Name</th><th>Minimum</th><th>Maximum</th><th>Fixed fee</th><th>Percentage</th><th>Status</th><th>Action</th></tr></thead><tbody><tr v-for="r in rules.filter(r=>r.type==='collection')" :key="r.id" class="border-t"><td class="p-2">{{ r.name }}</td><td>{{ r.min_amount??'No minimum' }}</td><td>{{ r.max_amount??'No maximum' }}</td><td>{{ r.fee_amount }}</td><td>{{ r.fee_percentage }}</td><td>{{ r.is_active?'Active':'Draft' }}</td><td><button class="text-indigo-600" @click="edit(r)">Edit</button></td></tr></tbody></table></div>
<form class="grid gap-4 rounded-xl border p-5 sm:grid-cols-2" @submit.prevent="save">
<label>Name<input v-model="form.name" required class="input" /></label>
<label>Minimum NGN<input v-model="form.min_amount" type="number" min="0" step="0.01" class="input" /></label>
<label>Maximum NGN (blank for no limit)<input v-model="form.max_amount" type="number" min="0" step="0.01" class="input" /></label>
<label>Fixed fee NGN<input v-model="form.fee_amount" required type="number" min="0" step="0.01" class="input" /></label>
<label>Percentage<input v-model="form.fee_percentage" required type="number" min="0" max="100" step="0.01" class="input" /></label>
<label class="flex items-center gap-2"><input v-model="form.is_active" type="checkbox" />Active</label>
<div class="flex gap-4"><button :disabled="busy" class="rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ busy?'Saving?':'Save rule' }}</button><button type="button" @click="reset">New rule</button></div>
</form></section>
</template>
<script setup>
import { ref,onMounted } from 'vue';import axios from 'axios';
const empty=()=>({name:'',type:'collection',min_amount:'',max_amount:'',fee_amount:'0.00',fee_percentage:'0.00',is_active:false});
const rules=ref([]),form=ref(empty()),id=ref(null),busy=ref(false),error=ref(''),message=ref('');
const reset=()=>{id.value=null;form.value=empty();};
const edit=r=>{id.value=r.id;form.value={...r,min_amount:r.min_amount??'',max_amount:r.max_amount??''};message.value='';};
async function load(){try{rules.value=(await axios.get('/api/v1/admin/pricing')).data.data||[];}catch(e){error.value=e.response?.data?.message||'Pricing could not be loaded.';}}
async function save(){busy.value=true;error.value='';message.value='';try{const v={...form.value,min_amount:form.value.min_amount===''?null:form.value.min_amount,max_amount:form.value.max_amount===''?null:form.value.max_amount};if(id.value)await axios.put('/api/v1/admin/pricing/'+id.value,v);else await axios.post('/api/v1/admin/pricing',v);message.value='Pricing saved.';reset();await load();}catch(e){error.value=Object.values(e.response?.data?.errors||{}).flat().join(' ')||e.response?.data?.message||'Pricing could not be saved.';}finally{busy.value=false;}}
onMounted(load);
</script>
<style scoped>.input{display:block;width:100%;border:1px solid #94a3b8;border-radius:.5rem;padding:.5rem;background:transparent;margin-top:.4rem;}</style>
