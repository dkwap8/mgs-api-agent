<template>
    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950">
        <p class="font-bold">{{ label }}</p>
        <div v-if="path" class="mt-4 flex gap-2">
            <button type="button" :disabled="loading" class="rounded-xl bg-indigo-600 px-3 py-2 text-xs font-bold text-white disabled:opacity-50" @click="load(false)">{{ loading ? 'Loading…' : 'Preview' }}</button>
            <button type="button" :disabled="loading" class="rounded-xl border px-3 py-2 text-xs font-bold" @click="load(true)">Download</button>
        </div>
        <p v-else class="mt-3 text-sm text-slate-500">No document uploaded</p>
        <p v-if="error" role="alert" class="mt-3 text-sm text-red-600">{{ error }}</p>
        <Teleport to="body">
            <div v-if="previewUrl" role="dialog" aria-modal="true" :aria-label="label" class="fixed inset-0 z-[300] flex flex-col bg-slate-950/95 p-4" @keydown.esc.stop.prevent="close">
                <div class="mb-4 flex items-center justify-between text-white"><h2 class="font-bold">{{ label }}</h2><button ref="closeButton" type="button" class="rounded-xl bg-white px-4 py-2 text-slate-950" @click="close">Close preview</button></div>
                <img v-if="mime.startsWith('image/')" :src="previewUrl" :alt="label" class="min-h-0 flex-1 object-contain" />
                <iframe v-else :src="previewUrl" :title="label" class="min-h-0 w-full flex-1 rounded-xl bg-white"></iframe>
            </div>
        </Teleport>
    </article>
</template>

<script setup>
import { ref, nextTick, onBeforeUnmount } from 'vue';
import axios from 'axios';
const props = defineProps({ label: String, path: String, activationId: [Number, String], field: String, context: { type: String, default: 'admin' } });
const loading = ref(false), error = ref(''), previewUrl = ref(''), mime = ref(''), closeButton = ref(null);
let previousFocus;
function close() {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = '';
    previousFocus?.focus();
}
async function load(download) {
    loading.value = true; error.value = '';
    try {
        const prefix = props.context === 'admin' ? 'admin/business-activations' : 'merchant/activations';
        const response = await axios.get(`/api/v1/${prefix}/${props.activationId}/documents/${props.field}`, { params: { download: download ? 1 : 0 }, responseType: 'blob', suppressVerificationModal: true });
        mime.value = response.data.type;
        if (!['image/jpeg', 'image/png', 'application/pdf'].includes(mime.value)) throw new Error('The server returned an unsupported document.');
        const url = URL.createObjectURL(response.data);
        if (download) {
            const link = document.createElement('a'); link.href = url;
            link.download = `${props.field}.${mime.value === 'application/pdf' ? 'pdf' : mime.value === 'image/png' ? 'png' : 'jpg'}`;
            link.click(); setTimeout(() => URL.revokeObjectURL(url), 1000);
        } else {
            previousFocus = document.activeElement;
            close(); previewUrl.value = url;
            await nextTick(); closeButton.value?.focus();
        }
    } catch (failure) {
        if (failure.response?.data instanceof Blob) {
            try { error.value = JSON.parse(await failure.response.data.text()).message; } catch { /* fall through */ }
        }
        error.value ||= failure.message || 'The document could not be loaded. Please retry.';
    } finally { loading.value = false; }
}
onBeforeUnmount(close);
</script>
