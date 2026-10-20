<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-950 dark:text-white">API Keys</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Manage your API credentials and access tokens</p>
            </div>
            <button
                @click="openPasswordModal('generate')"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-60"
                :disabled="loading"
            >
                <span>＋</span>
                {{ loading ? 'Please wait...' : 'Generate API Key' }}
            </button>
        </div>

        <!-- API Credentials Card -->
        <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
            <h3 class="mb-4 font-semibold text-slate-950 dark:text-white">API Credentials</h3>
            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Use these keys to integrate with the PayDrex API</p>

            <div v-if="loading" class="py-8 text-center text-slate-500 dark:text-slate-400">
                Loading API keys...
            </div>

            <div v-else-if="apiKeys.length === 0" class="py-8 text-center">
                <p class="text-slate-500 dark:text-slate-400">No API keys found</p>
                <p class="text-sm text-slate-400 dark:text-slate-500">Generate your first API key to start integrating</p>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="key in apiKeys"
                    :key="key.id"
                    class="rounded-xl border border-slate-200 p-4 dark:border-slate-700"
                >
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Merchant ID</span>
                                <span class="font-mono text-sm text-slate-950 dark:text-white">{{ key.merchant_id || 'HID412240B9844' }}</span>
                            </div>

                            <div class="mt-2 flex items-center gap-3">
                                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Secret Key</span>
                                <span class="font-mono text-sm text-slate-950 dark:text-white">
                                    {{ visibleKeys[key.id] ? formatKey(key.public_key) : 'sk__••••••••' }}
                                </span>
                                <span class="text-xs text-slate-400 dark:text-slate-500">The full secret is shown only once, right after you generate it.</span>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-4 text-xs">
                                <span class="text-slate-500 dark:text-slate-400">Webhook URL: <span class="text-slate-700 dark:text-slate-300">Not set — add one in Webhooks</span></span>
                                <span class="text-slate-500 dark:text-slate-400">KEY GENERATED: {{ formatDate(key.created_at) }}</span>
                                <span class="text-slate-500 dark:text-slate-400">KEYS: {{ apiKeys.length }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                @click="visibleKeys[key.id] ? hideKey(key.id) : openPasswordModal('show', key)"
                                class="rounded-xl border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-[#2a2a2a]"
                            >
                                {{ visibleKeys[key.id] ? 'Hide' : 'Show' }}
                            </button>
                            <button
                                v-if="visibleKeys[key.id]"
                                @click="openPasswordModal('copy', key)"
                                class="rounded-xl bg-indigo-600 px-3 py-2 text-sm text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                            >
                                Copy
                            </button>
                            <button
                                @click="viewDocumentation"
                                class="rounded-xl border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-[#2a2a2a]"
                            >
                                📄 View Documentation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Required Badge -->
        <div class="flex items-center gap-2 text-sm text-yellow-600 dark:text-yellow-400">
            <span>⚠️ Action required :</span>
            <span class="font-medium">All Bookmarks</span>
        </div>

        <!-- Password Modal -->
        <div v-if="passwordModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-950">
                <h2 class="text-xl font-bold text-slate-950 dark:text-white">{{ modalTitle }}</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                    Enter your account password to continue.
                </p>

                <div class="mt-4">
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Password
                    </label>
                    <div class="relative">
                        <input
                            v-model="password"
                            :type="showPassword ? 'text' : 'password'"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 pr-12 text-slate-950 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                            placeholder="Enter password"
                            @keyup.enter="confirmPasswordAction"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-500 hover:text-indigo-600"
                        >
                            {{ showPassword ? '🙈' : '👁️' }}
                        </button>
                    </div>
                    <p v-if="passwordError" class="mt-2 text-sm font-medium text-red-600">{{ passwordError }}</p>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button
                        @click="closePasswordModal"
                        class="rounded-xl border border-slate-200 px-4 py-2 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-[#2a2a2a]"
                        :disabled="modalLoading"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmPasswordAction"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-60"
                        :disabled="modalLoading"
                    >
                        {{ modalLoading ? 'Checking...' : 'Confirm' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { computed, ref, onMounted } from 'vue';
import axios from 'axios';

export default {
    name: 'ApiKeys',

    setup() {
        const loading = ref(false);
        const modalLoading = ref(false);
        const apiKeys = ref([]);
        const visibleKeys = ref({});
        const copiedKeyId = ref(null);

        const passwordModalOpen = ref(false);
        const password = ref('');
        const passwordError = ref('');
        const showPassword = ref(false);
        const pendingAction = ref(null);
        const selectedKey = ref(null);

        const modalTitle = computed(() => {
            if (pendingAction.value === 'generate') return 'Generate API Key';
            if (pendingAction.value === 'show') return 'Show API Key';
            if (pendingAction.value === 'copy') return 'Copy API Key';
            return 'Confirm Password';
        });

        const formatKey = (key) => {
            if (!key) return '';
            return key.replace(/^sk__[a-zA-Z0-9]+$/, (match) => {
                if (match.length > 12) {
                    return match.substring(0, 12) + '••••••••••••';
                }
                return match;
            });
        };

        const hideKey = (id) => {
            visibleKeys.value[id] = false;
        };

        const openPasswordModal = (action, key = null) => {
            pendingAction.value = action;
            selectedKey.value = key;
            password.value = '';
            passwordError.value = '';
            showPassword.value = false;
            passwordModalOpen.value = true;
        };

        const closePasswordModal = () => {
            passwordModalOpen.value = false;
            pendingAction.value = null;
            selectedKey.value = null;
            password.value = '';
            passwordError.value = '';
            showPassword.value = false;
        };

        const loadApiKeys = async () => {
            loading.value = true;
            try {
                const response = await axios.get('/api/v1/merchant/api-keys');
                apiKeys.value = response.data.data || [];
            } catch (error) {
                console.error('Failed to load API keys:', error.response?.data || error);
                apiKeys.value = [];
            } finally {
                loading.value = false;
            }
        };

        const generateApiKeyWithPassword = async () => {
            await axios.post('/api/v1/merchant/api-keys', {
                environment: 'test',
                password: password.value,
            });
            await loadApiKeys();
        };

        const revealApiKeyWithPassword = async (key) => {
            const response = await axios.post(`/api/v1/merchant/api-keys/${key.id}/reveal`, {
                password: password.value,
            });
            const realKey = response.data?.data?.public_key;
            apiKeys.value = apiKeys.value.map(item => {
                if (item.id === key.id) {
                    return { ...item, public_key: realKey };
                }
                return item;
            });
            visibleKeys.value[key.id] = true;
            return realKey;
        };

        const copyKey = async (keyValue, id) => {
            if (!keyValue) {
                passwordError.value = 'No API key to copy.';
                return;
            }
            await navigator.clipboard.writeText(keyValue);
            copiedKeyId.value = id;
            setTimeout(() => { copiedKeyId.value = null; }, 2000);
        };

        const confirmPasswordAction = async () => {
            if (!password.value) {
                passwordError.value = 'Password is required.';
                return;
            }
            modalLoading.value = true;
            passwordError.value = '';

            try {
                if (pendingAction.value === 'generate') {
                    await generateApiKeyWithPassword();
                }
                if (pendingAction.value === 'show' && selectedKey.value) {
                    await revealApiKeyWithPassword(selectedKey.value);
                }
                if (pendingAction.value === 'copy' && selectedKey.value) {
                    let keyValue = selectedKey.value.public_key;
                    if (!keyValue || !visibleKeys.value[selectedKey.value.id]) {
                        keyValue = await revealApiKeyWithPassword(selectedKey.value);
                    }
                    await copyKey(keyValue, selectedKey.value.id);
                }
                closePasswordModal();
            } catch (error) {
                console.error('Password action failed:', error.response?.data || error);
                passwordError.value = error.response?.data?.message || 'Incorrect password or request failed.';
            } finally {
                modalLoading.value = false;
            }
        };

        const viewDocumentation = () => {
            window.open('/user/documentation', '_blank');
        };

        const formatDate = (date) => {
            if (!date) return 'N/A';
            return new Date(date).toLocaleString('en-US', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        };

        onMounted(loadApiKeys);

        return {
            loading,
            modalLoading,
            apiKeys,
            visibleKeys,
            copiedKeyId,
            passwordModalOpen,
            password,
            passwordError,
            showPassword,
            modalTitle,
            formatKey,
            hideKey,
            openPasswordModal,
            closePasswordModal,
            confirmPasswordAction,
            viewDocumentation,
            formatDate,
        };
    },
};
</script>