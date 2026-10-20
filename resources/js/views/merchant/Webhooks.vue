<template>
    <div class="space-y-6">
        <section>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400">Developer</p>
            <div class="mt-1 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-950 dark:text-white">Webhooks</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Configure signed callbacks and monitor delivery attempts for the active business.</p>
                </div>
                <button type="button" class="secondary-button" :disabled="loadingEndpoint || loadingLogs" @click="refreshPage">
                    {{ loadingEndpoint || loadingLogs ? 'Refreshing…' : '↻ Refresh' }}
                </button>
            </div>
        </section>

        <section v-if="pageMessage" class="rounded-2xl border p-4 text-sm font-semibold" :class="pageMessageSuccess ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300' : 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300'">
            {{ pageMessage }}
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.45fr_0.85fr]">
            <article class="panel-card">
                <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-lg font-black text-slate-950 dark:text-white">Webhook configuration</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">MGS API will send signed POST requests to this URL.</p>
                        </div>
                        <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-black" :class="form.status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300'">
                            {{ form.status === 'active' ? 'Active' : 'Disabled' }}
                        </span>
                    </div>
                </div>

                <form class="space-y-5 p-5" @submit.prevent="saveWebhook">
                    <div>
                        <label class="field-label">Endpoint URL</label>
                        <input v-model.trim="form.url" type="url" required placeholder="https://api.yoursite.com/webhooks/mgs" class="field-input" />
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">HTTPS is required in production.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="field-label">Delivery status</label>
                            <select v-model="form.status" class="field-input">
                                <option value="active">Active</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Environment</label>
                            <div class="field-input flex items-center"><span class="font-black capitalize">{{ endpoint?.environment || 'test' }}</span></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <label class="field-label mb-0">Subscribed events</label>
                            <span class="text-xs font-semibold text-slate-400">Select at least one</span>
                        </div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label v-for="event in supportedEvents" :key="event" class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:border-indigo-300 dark:border-slate-700 dark:hover:border-indigo-700">
                                <input v-model="form.events" type="checkbox" :value="event" class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                <span>
                                    <span class="block font-black text-slate-950 dark:text-white">{{ event }}</span>
                                    <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">Sent after a successful virtual-account collection.</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div v-if="displaySecret" class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-900/60 dark:bg-indigo-950/30">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-300">Signing secret</p>
                                <p class="mt-2 break-all font-mono text-sm font-bold text-slate-950 dark:text-white">{{ secretVisible ? secret : maskedSecret }}</p>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <button type="button" class="secondary-button" @click="secretVisible = !secretVisible">{{ secretVisible ? 'Hide' : 'Reveal' }}</button>
                                <button type="button" class="secondary-button" @click="copySecret">Copy</button>
                            </div>
                        </div>
                        <label class="mt-4 flex items-center gap-3 text-sm font-semibold text-slate-700 dark:text-slate-300">
                            <input v-model="form.regenerate_secret" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            Generate a new secret when saving
                        </label>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="primary-button" :disabled="saving">{{ saving ? 'Saving…' : 'Save configuration' }}</button>
                        <button type="button" class="secondary-button" :disabled="testing || !endpoint" @click="testWebhook">{{ testing ? 'Sending test…' : 'Send test webhook' }}</button>
                    </div>
                </form>
            </article>

            <article class="panel-card p-5">
                <h2 class="text-lg font-black text-slate-950 dark:text-white">Webhook security</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Verify each callback before processing it.</p>
                <div class="mt-5 space-y-4">
                    <div class="security-row"><p class="security-label">Event header</p><code class="security-code">X-MGS-Event</code></div>
                    <div class="security-row"><p class="security-label">Signature header</p><code class="security-code">X-MGS-Signature</code></div>
                    <div class="security-row"><p class="security-label">Algorithm</p><code class="security-code">HMAC SHA-256</code></div>
                </div>
                <div class="mt-5 rounded-2xl bg-slate-50 p-4 dark:bg-slate-950">
                    <p class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Verification example</p>
                    <pre class="mt-3 overflow-x-auto whitespace-pre-wrap break-words text-xs leading-6 text-slate-700 dark:text-slate-300">hash_hmac(
    'sha256',
    $rawRequestBody,
    $webhookSecret
)</pre>
                </div>
            </article>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="flex flex-col gap-4 border-b border-slate-200 p-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-950 dark:text-white">Webhook delivery logs</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Review delivery responses and retry failed callbacks.</p>
                </div>
                <select v-model="statusFilter" class="field-input w-full sm:w-48" @change="fetchLogs(1)">
                    <option value="">All statuses</option>
                    <option value="delivered">Delivered</option>
                    <option value="failed">Failed</option>
                    <option value="pending">Pending</option>
                </select>
            </div>

            <div v-if="loadingLogs" class="py-16 text-center text-sm text-slate-500 dark:text-slate-400">Loading delivery logs…</div>
            <div v-else-if="logs.length === 0" class="py-16 text-center">
                <p class="font-black text-slate-700 dark:text-slate-200">No webhook deliveries yet</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Send a test webhook or process a collection to create the first delivery log.</p>
            </div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
                        <tr class="text-left text-[11px] font-black uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-4">Event</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">HTTP</th><th class="px-5 py-4">Attempts</th><th class="px-5 py-4">Last error</th><th class="px-5 py-4">Date</th><th class="px-5 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="log in logs" :key="log.id" class="align-top hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                            <td class="px-5 py-4"><p class="font-black text-slate-950 dark:text-white">{{ log.event }}</p><p class="mt-1 max-w-xs truncate text-xs text-slate-400">{{ log.url }}</p></td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-black capitalize" :class="statusClass(log.status)">{{ log.status }}</span></td>
                            <td class="px-5 py-4 text-sm font-bold text-slate-700 dark:text-slate-300">{{ log.status_code || '—' }}</td>
                            <td class="px-5 py-4 text-sm font-bold text-slate-700 dark:text-slate-300">{{ log.attempt || 0 }}</td>
                            <td class="max-w-sm px-5 py-4 text-sm text-slate-500 dark:text-slate-400">{{ log.last_error || '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500 dark:text-slate-400">{{ formatDate(log.created_at) }}</td>
                            <td class="px-5 py-4 text-right">
                                <button v-if="log.status === 'failed'" type="button" class="secondary-button" :disabled="retryingId === log.id" @click="retryLog(log.id)">{{ retryingId === log.id ? 'Retrying…' : 'Retry' }}</button>
                                <span v-else class="text-xs font-semibold text-slate-400">No action</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="pagination.lastPage > 1" class="flex items-center justify-between border-t border-slate-200 p-4 dark:border-slate-800">
                <button type="button" class="secondary-button" :disabled="pagination.currentPage <= 1" @click="fetchLogs(pagination.currentPage - 1)">Previous</button>
                <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">Page {{ pagination.currentPage }} of {{ pagination.lastPage }}</span>
                <button type="button" class="secondary-button" :disabled="pagination.currentPage >= pagination.lastPage" @click="fetchLogs(pagination.currentPage + 1)">Next</button>
            </div>
        </section>
    </div>
</template>

<script>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

export default {
    name: 'MerchantWebhooks',
    setup() {
        const endpoint = ref(null);
        const supportedEvents = ref(['collection.success']);
        const form = ref({ url: '', events: ['collection.success'], status: 'active', regenerate_secret: false });
        const secret = ref('');
        const secretVisible = ref(false);
        const loadingEndpoint = ref(false);
        const saving = ref(false);
        const testing = ref(false);
        const loadingLogs = ref(false);
        const retryingId = ref(null);
        const logs = ref([]);
        const statusFilter = ref('');
        const pagination = ref({ currentPage: 1, lastPage: 1 });
        const pageMessage = ref('');
        const pageMessageSuccess = ref(true);

        const displaySecret = computed(() => Boolean(secret.value || endpoint.value));
        const maskedSecret = computed(() => secret.value ? `${secret.value.slice(0, 8)}${'•'.repeat(20)}${secret.value.slice(-4)}` : '••••••••••••••••••••••••••••••••');

        const showMessage = (message, success = true) => {
            pageMessage.value = message;
            pageMessageSuccess.value = success;
            window.setTimeout(() => { if (pageMessage.value === message) pageMessage.value = ''; }, 6000);
        };

        const fetchWebhook = async () => {
            loadingEndpoint.value = true;
            try {
                const response = await axios.get('/api/v1/webhooks/endpoint');
                const data = response.data?.data || {};
                const loadedEndpoint = data.endpoint || null;
                endpoint.value = loadedEndpoint;
                supportedEvents.value = Array.isArray(data.supported_events) && data.supported_events.length ? data.supported_events : ['collection.success'];
                form.value = {
                    url: loadedEndpoint?.url || '',
                    events: Array.isArray(loadedEndpoint?.events) && loadedEndpoint.events.length ? loadedEndpoint.events : ['collection.success'],
                    status: loadedEndpoint?.status || 'active',
                    regenerate_secret: false,
                };
            } catch (error) {
                showMessage(error.response?.data?.message || 'Webhook configuration could not be loaded.', false);
            } finally {
                loadingEndpoint.value = false;
            }
        };

        const fetchLogs = async (page = 1) => {
            loadingLogs.value = true;
            try {
                const response = await axios.get('/api/v1/webhooks/logs', { params: { page, status: statusFilter.value || undefined, per_page: 20 } });
                const paginator = response.data?.data || {};
                logs.value = Array.isArray(paginator.data) ? paginator.data : [];
                pagination.value = { currentPage: Number(paginator.current_page || 1), lastPage: Number(paginator.last_page || 1) };
            } catch (error) {
                logs.value = [];
                showMessage(error.response?.data?.message || 'Webhook delivery logs could not be loaded.', false);
            } finally {
                loadingLogs.value = false;
            }
        };

        const saveWebhook = async () => {
            if (!form.value.events.length) return showMessage('Select at least one webhook event.', false);
            saving.value = true;
            try {
                const response = await axios.post('/api/v1/webhooks/endpoint', form.value);
                const data = response.data?.data || {};
                endpoint.value = data.endpoint || endpoint.value;
                if (data.secret) { secret.value = data.secret; secretVisible.value = true; }
                form.value.regenerate_secret = false;
                showMessage(response.data?.message || 'Webhook configuration saved successfully.');
                await fetchWebhook();
            } catch (error) {
                showMessage(error.response?.data?.message || error.response?.data?.errors?.url?.[0] || 'Webhook configuration could not be saved.', false);
            } finally {
                saving.value = false;
            }
        };

        const testWebhook = async () => {
            testing.value = true;
            try {
                const response = await axios.post('/api/v1/webhooks/test');
                showMessage(response.data?.message || 'Webhook test completed.', response.data?.success !== false);
                await fetchLogs(1);
            } catch (error) {
                showMessage(error.response?.data?.message || 'Webhook test failed.', false);
                await fetchLogs(1);
            } finally {
                testing.value = false;
            }
        };

        const retryLog = async (id) => {
            retryingId.value = id;
            try {
                const response = await axios.post(`/api/v1/webhooks/logs/${id}/retry`);
                showMessage(response.data?.message || 'Webhook retry completed.', response.data?.success !== false);
                await fetchLogs(pagination.value.currentPage);
            } catch (error) {
                showMessage(error.response?.data?.message || 'Webhook retry failed.', false);
            } finally {
                retryingId.value = null;
            }
        };

        const copySecret = async () => {
            if (!secret.value) return showMessage('Regenerate the secret to reveal and copy it.', false);
            try { await navigator.clipboard.writeText(secret.value); showMessage('Webhook secret copied.'); }
            catch { showMessage('Webhook secret could not be copied.', false); }
        };

        const refreshPage = async () => Promise.all([fetchWebhook(), fetchLogs(1)]);
        const statusClass = (status) => ({
            delivered: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            failed: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
            pending: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        }[status] || 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300');
        const formatDate = (date) => date ? new Date(date).toLocaleString('en-NG', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';

        onMounted(refreshPage);
        return { endpoint, supportedEvents, form, secret, secretVisible, loadingEndpoint, saving, testing, loadingLogs, retryingId, logs, statusFilter, pagination, pageMessage, pageMessageSuccess, displaySecret, maskedSecret, saveWebhook, testWebhook, retryLog, fetchLogs, copySecret, refreshPage, statusClass, formatDate };
    },
};
</script>

<style scoped>
.panel-card { border: 1px solid rgb(226 232 240); border-radius: 1.5rem; background: white; box-shadow: 0 1px 2px rgb(15 23 42 / 0.04), 0 12px 32px rgb(15 23 42 / 0.05); }
.field-label { margin-bottom: 0.5rem; display: block; font-size: 0.875rem; font-weight: 800; color: rgb(51 65 85); }
.field-input { min-height: 2.75rem; width: 100%; border-radius: 0.875rem; border: 1px solid rgb(226 232 240); background: white; padding: 0.7rem 0.9rem; color: rgb(15 23 42); outline: none; }
.field-input:focus { border-color: rgb(99 102 241); box-shadow: 0 0 0 3px rgb(99 102 241 / 0.12); }
.primary-button { border-radius: 0.875rem; background: rgb(79 70 229); padding: 0.7rem 1rem; font-size: 0.875rem; font-weight: 900; color: white; }
.primary-button:hover { background: rgb(67 56 202); }
.primary-button:disabled, .secondary-button:disabled { cursor: not-allowed; opacity: 0.5; }
.secondary-button { border-radius: 0.875rem; border: 1px solid rgb(226 232 240); background: white; padding: 0.65rem 0.9rem; font-size: 0.8rem; font-weight: 900; color: rgb(51 65 85); }
.secondary-button:hover { background: rgb(248 250 252); }
.security-row { border-radius: 1rem; background: rgb(248 250 252); padding: 1rem; }
.security-label { font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; color: rgb(100 116 139); }
.security-code { margin-top: 0.4rem; display: inline-block; font-size: 0.85rem; font-weight: 800; color: rgb(15 23 42); }
:global(.dark) .panel-card { border-color: rgb(30 41 59); background: rgb(15 23 42); }
:global(.dark) .field-label { color: rgb(203 213 225); }
:global(.dark) .field-input { border-color: rgb(51 65 85); background: rgb(2 6 23); color: white; }
:global(.dark) .secondary-button { border-color: rgb(51 65 85); background: rgb(15 23 42); color: rgb(226 232 240); }
:global(.dark) .secondary-button:hover, :global(.dark) .security-row { background: rgb(2 6 23); }
:global(.dark) .security-code { color: rgb(226 232 240); }
</style>
