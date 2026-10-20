<template>
    <section class="space-y-5 text-slate-900 dark:text-slate-100">
        <div><h1 class="text-2xl font-bold">{{ title }}</h1><p class="mt-1 text-sm text-slate-500">{{ description }}</p></div>
        <form class="flex flex-wrap gap-3" @submit.prevent="load(1)">
            <input v-model="search" :placeholder="searchPlaceholder" aria-label="Search records" class="rounded-lg border bg-white p-2 dark:bg-slate-900" />
            <input v-if="statusCode" v-model="status" type="number" min="100" max="599" placeholder="HTTP status" aria-label="HTTP status" class="rounded-lg border bg-white p-2 dark:bg-slate-900" />
            <select v-else v-model="status" aria-label="Status" class="rounded-lg border bg-white p-2 dark:bg-slate-900"><option value="">All statuses</option><option v-for="s in statuses" :key="s">{{ s }}</option></select>
            <input v-if="providerFilter" v-model="provider" type="number" min="1" placeholder="Provider ID" aria-label="Provider ID" class="rounded-lg border bg-white p-2 dark:bg-slate-900" />
            <button :disabled="loading" class="rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ loading ? 'Loading?' : 'Filter' }}</button>
        </form>
        <p v-if="error" role="alert" class="text-red-600">{{ error }}</p>
        <div class="overflow-x-auto rounded-xl border"><table class="w-full text-left text-sm"><thead><tr><th v-for="c in columns" :key="c.key" class="p-3">{{ c.label }}</th></tr></thead><tbody><tr v-for="r in records" :key="r.id" class="border-t"><td v-for="c in columns" :key="c.key" class="p-3">{{ value(r,c.key) }}</td></tr><tr v-if="!records.length && !loading"><td :colspan="columns.length" class="p-5">No records match these filters.</td></tr></tbody></table></div>
        <div class="flex items-center gap-4"><button :disabled="loading || page<=1" @click="load(page-1)">Previous</button><span>Page {{ page }} of {{ lastPage }}</span><button :disabled="loading || page>=lastPage" @click="load(page+1)">Next</button></div>
    </section>
</template>
<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';
const props=defineProps({title:String,description:String,endpoint:String,columns:Array,statuses:{type:Array,default:()=>[]},statusCode:Boolean,providerFilter:Boolean,searchKey:{type:String,default:'search'},searchPlaceholder:{type:String,default:'Search'}});
const records=ref([]),search=ref(''),status=ref(''),provider=ref(''),error=ref(''),loading=ref(false),page=ref(1),lastPage=ref(1);
const route=useRoute();
const value=(row,key)=>key.split('.').reduce((v,k)=>v?.[k],row)??'?';
async function load(target=1){loading.value=true;error.value='';try{const {data}=await axios.get(props.endpoint,{params:{page:target,per_page:20,[props.searchKey]:search.value||undefined,[props.statusCode?'status_code':'status']:status.value||undefined,provider_id:provider.value||undefined}});records.value=Array.isArray(data.data)?data.data:data.data?.data||[];page.value=data.meta?.current_page||data.data?.current_page||1;lastPage.value=data.meta?.last_page||data.data?.last_page||1;}catch(e){records.value=[];error.value=e.response?.data?.message||'Records could not be loaded.';}finally{loading.value=false;}}
onMounted(()=>load());watch(()=>route.fullPath,()=>load());
</script>
