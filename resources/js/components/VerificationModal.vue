<template>
    <Teleport to="body">
        <Transition name="verification-modal">
            <div
                v-if="modal.visible"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/70 px-4 py-8 backdrop-blur-sm"
                role="presentation"
                @mousedown.self="close"
            >
                <section
                    ref="panel"
                    role="alertdialog"
                    tabindex="-1"
                    aria-modal="true"
                    :aria-labelledby="titleId"
                    :aria-describedby="descriptionId"
                    class="w-full max-w-lg overflow-hidden rounded-3xl border border-white/10 bg-white shadow-2xl dark:bg-slate-900"
                    @keydown.esc="close"
                >
                    <div :class="['h-1.5 w-full', accent.bar]"></div>

                    <div class="p-6 sm:p-7">
                        <div class="flex items-start gap-4">
                            <div :class="['flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl', accent.iconWrap]">
                                <svg v-if="modal.type === 'success'" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                                </svg>
                                <svg v-else-if="modal.type === 'warning'" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 4.3 2.7 17.5A2 2 0 0 0 4.4 20h15.2a2 2 0 0 0 1.7-2.5L13.7 4.3a2 2 0 0 0-3.4 0Z" />
                                </svg>
                                <svg v-else-if="modal.type === 'error'" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" d="m7 7 10 10M17 7 7 17" />
                                </svg>
                                <svg v-else class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9" />
                                    <path stroke-linecap="round" d="M12 11v5m0-8h.01" />
                                </svg>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p :class="['text-xs font-bold uppercase tracking-[0.18em]', accent.label]">
                                    {{ modal.type }}
                                </p>
                                <h2 :id="titleId" class="mt-1 text-xl font-bold text-slate-950 dark:text-white">
                                    {{ modal.title }}
                                </h2>
                                <p :id="descriptionId" class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600 dark:text-slate-300">
                                    {{ modal.message }}
                                </p>
                            </div>

                            <button
                                v-if="modal.dismissible"
                                type="button"
                                class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-white"
                                aria-label="Close notification"
                                @click="close"
                            >
                                <span aria-hidden="true" class="text-xl">×</span>
                            </button>
                        </div>

                        <div v-if="validationItems.length" class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/60 dark:bg-rose-950/30">
                            <p class="text-sm font-semibold text-rose-800 dark:text-rose-300">Please review:</p>
                            <ul class="mt-2 space-y-1.5 text-sm text-rose-700 dark:text-rose-300">
                                <li v-for="item in validationItems" :key="item" class="flex gap-2">
                                    <span aria-hidden="true">•</span><span>{{ item }}</span>
                                </li>
                            </ul>
                        </div>

                        <div v-if="modal.requestId" class="mt-5 flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70">
                            <div class="min-w-0">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Request ID</p>
                                <p class="truncate font-mono text-xs text-slate-600 dark:text-slate-300">{{ modal.requestId }}</p>
                            </div>
                            <button type="button" class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-950/40" @click="copyRequestId">
                                {{ copied ? 'Copied' : 'Copy' }}
                            </button>
                        </div>

                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button
                                v-for="action in modal.actions"
                                :key="action.id"
                                type="button"
                                :disabled="modal.busy"
                                :class="buttonClass(action.style)"
                                @click="verificationModal.runAction(action)"
                            >
                                <span v-if="modal.busy" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-r-transparent"></span>
                                {{ action.label }}
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { verificationModal } from '../services/verificationModal';

const modal = verificationModal.state;
const panel = ref(null);
const copied = ref(false);
const titleId = 'platform-verification-modal-title';
const descriptionId = 'platform-verification-modal-description';

const accents = {
    success: { bar: 'bg-emerald-500', iconWrap: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300', label: 'text-emerald-600 dark:text-emerald-400' },
    warning: { bar: 'bg-amber-500', iconWrap: 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300', label: 'text-amber-600 dark:text-amber-400' },
    error: { bar: 'bg-rose-500', iconWrap: 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300', label: 'text-rose-600 dark:text-rose-400' },
    info: { bar: 'bg-indigo-500', iconWrap: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300', label: 'text-indigo-600 dark:text-indigo-400' },
};

const accent = computed(() => accents[modal.type] || accents.info);
const validationItems = computed(() => Object.values(modal.errors || {}).flatMap(value => Array.isArray(value) ? value : [value]).filter(Boolean).slice(0, 8));

function buttonClass(style) {
    const base = 'inline-flex min-h-11 items-center justify-center rounded-xl px-5 py-2.5 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-60';
    if (style === 'danger') return `${base} bg-rose-600 text-white hover:bg-rose-700`;
    if (style === 'secondary') return `${base} border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800`;
    return `${base} bg-indigo-600 text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600`;
}

function close() {
    verificationModal.close();
}

async function copyRequestId() {
    if (!modal.requestId) return;
    await navigator.clipboard.writeText(modal.requestId);
    copied.value = true;
    window.setTimeout(() => { copied.value = false; }, 1500);
}

watch(() => modal.visible, async visible => {
    document.documentElement.classList.toggle('overflow-hidden', visible);
    if (visible) {
        copied.value = false;
        await nextTick();
        panel.value?.focus?.();
    }
});
</script>

<style scoped>
.verification-modal-enter-active,
.verification-modal-leave-active { transition: opacity 180ms ease; }
.verification-modal-enter-active section,
.verification-modal-leave-active section { transition: transform 180ms ease, opacity 180ms ease; }
.verification-modal-enter-from,
.verification-modal-leave-to { opacity: 0; }
.verification-modal-enter-from section,
.verification-modal-leave-to section { opacity: 0; transform: translateY(12px) scale(.98); }
</style>
