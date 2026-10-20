<template>
    <div class="min-h-full pb-10">
        <div class="relative overflow-hidden rounded-[28px] bg-slate-950 px-5 py-7 text-white shadow-2xl shadow-slate-950/10 sm:px-8 sm:py-9">
            <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-indigo-500/20 blur-3xl"></div>
            <div class="absolute -bottom-32 left-1/3 h-72 w-72 rounded-full bg-cyan-400/10 blur-3xl"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-semibold text-slate-200 backdrop-blur">
                        <ShieldCheckIcon class="h-4 w-4 text-emerald-400" />
                        Secure merchant verification
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Activate your business</h1>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-slate-300 sm:text-base">
                        Tell us about your business and submit the required documents. Your information is encrypted and reviewed securely.
                    </p>
                </div>

                <div class="w-full max-w-sm rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-300">Application progress</span>
                        <span class="font-bold text-white">{{ progress }}%</span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/10">
                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-cyan-400 transition-all duration-500" :style="{ width: `${progress}%` }"></div>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">Step {{ currentStep + 1 }} of {{ steps.length }} · {{ activeStep.title }}</p>
                </div>
            </div>
        </div>

        <div v-if="notice.message" class="mt-5 flex items-start gap-3 rounded-2xl border p-4 text-sm shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)]" :class="notice.type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300' : 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300'">
            <CheckCircleIcon v-if="notice.type === 'success'" class="mt-0.5 h-5 w-5 shrink-0" />
            <ExclamationCircleIcon v-else class="mt-0.5 h-5 w-5 shrink-0" />
            <div class="flex-1">
                <p class="font-semibold">{{ notice.type === 'success' ? 'Application submitted' : 'Please check your information' }}</p>
                <p class="mt-0.5">{{ notice.message }}</p>
            </div>
            <button type="button" class="rounded-xl p-1 hover:bg-black/5 dark:hover:bg-white/5" @click="clearNotice">
                <XMarkIcon class="h-4 w-4" />
            </button>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="hidden lg:block">
                <div class="sticky top-6 rounded-3xl border border-slate-200 bg-white p-4 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:border-white/10 dark:bg-[#111318]">
                    <div class="px-2 pb-4 pt-2">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Application steps</p>
                    </div>

                    <nav class="space-y-1.5">
                        <button
                            v-for="(step, index) in steps"
                            :key="step.key"
                            type="button"
                            class="group flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-left transition"
                            :class="index === currentStep ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300' : index < currentStep || isStepComplete(step.key) ? 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-white/5' : 'text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5'"
                            @click="goToStep(index)"
                        >
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border text-sm font-bold transition" :class="stepIconClass(step, index)">
                                <CheckIcon v-if="isStepComplete(step.key)" class="h-5 w-5" />
                                <component v-else :is="step.icon" class="h-5 w-5" />
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold">{{ step.title }}</span>
                                <span class="mt-0.5 block truncate text-xs opacity-70">{{ step.subtitle }}</span>
                            </span>
                        </button>
                    </nav>

                    <div class="mt-5 rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                        <div class="flex items-center gap-2 text-sm font-semibold text-slate-800 dark:text-slate-100">
                            <LockClosedIcon class="h-4 w-4 text-emerald-500" />
                            Your data is protected
                        </div>
                        <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">Documents are used only for verification and compliance review.</p>
                    </div>
                </div>
            </aside>

            <main class="min-w-0">
                <div class="mb-4 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:border-white/10 dark:bg-[#111318] lg:hidden">
                    <div class="flex min-w-max items-center gap-1">
                        <button v-for="(step, index) in steps" :key="step.key" type="button" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-xs font-semibold" :class="index === currentStep ? 'bg-indigo-600 text-white' : 'text-slate-500 dark:text-slate-400'" @click="goToStep(index)">
                            <span class="flex h-6 w-6 items-center justify-center rounded-xl" :class="index === currentStep ? 'bg-white/15' : isStepComplete(step.key) ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-slate-100 dark:bg-white/5'">
                                <CheckIcon v-if="isStepComplete(step.key)" class="h-4 w-4" />
                                <span v-else>{{ index + 1 }}</span>
                            </span>
                            {{ step.shortTitle || step.title }}
                        </button>
                    </div>
                </div>

                <form class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:border-white/10 dark:bg-[#111318]" @submit.prevent="handlePrimaryAction">
                    <div class="border-b border-slate-100 px-5 py-5 dark:border-white/10 sm:px-7 sm:py-6">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300">
                                <component :is="activeStep.icon" class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-600 dark:text-indigo-400">Step {{ currentStep + 1 }}</p>
                                <h2 class="mt-1 text-xl font-bold text-slate-900 dark:text-white sm:text-2xl">{{ activeStep.heading }}</h2>
                                <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ activeStep.description }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-6 sm:px-7 sm:py-8">
                        <section v-if="activeStep.key === 'business'" class="space-y-7">
                            <div>
                                <FieldLabel label="Business type" required />
                                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                    <label v-for="option in businessTypes" :key="option.value" class="relative cursor-pointer rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)]" :class="form.business_type === option.value ? 'border-indigo-500 bg-indigo-50/70 ring-4 ring-indigo-500/5 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-white/10'">
                                        <input v-model="form.business_type" type="radio" :value="option.value" class="sr-only" />
                                        <div class="flex items-start gap-3">
                                            <span class="flex h-10 w-10 items-center justify-center rounded-2xl" :class="form.business_type === option.value ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-300'">
                                                <component :is="option.icon" class="h-5 w-5" />
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="flex items-center justify-between gap-2">
                                                    <span class="font-semibold text-slate-900 dark:text-white">{{ option.title }}</span>
                                                    <CheckCircleIcon v-if="form.business_type === option.value" class="h-5 w-5 text-indigo-600" />
                                                </span>
                                                <span class="mt-1 block text-xs leading-5 text-slate-500 dark:text-slate-400">{{ option.description }}</span>
                                            </span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <FormField label="Business name" required :error="errors.business_name" class="sm:col-span-2">
                                    <input v-model.trim="form.business_name" type="text" autocomplete="organization" placeholder="e.g. MGS Technologies Limited" :class="inputClass(errors.business_name)" @input="clearFieldError('business_name')" />
                                </FormField>

                                <template v-if="form.business_type === 'starter'">
                                    <FormField label="First name" required :error="errors.first_name">
                                        <input v-model.trim="form.first_name" type="text" autocomplete="given-name" placeholder="Enter first name" :class="inputClass(errors.first_name)" @input="clearFieldError('first_name')" />
                                    </FormField>

                                    <FormField label="Last name / Surname" required :error="errors.last_name">
                                        <input v-model.trim="form.last_name" type="text" autocomplete="family-name" placeholder="Enter surname" :class="inputClass(errors.last_name)" @input="clearFieldError('last_name')" />
                                    </FormField>

                                    <FormField label="Middle name" hint="Optional" :error="errors.middle_name" class="sm:col-span-2">
                                        <input v-model.trim="form.middle_name" type="text" autocomplete="additional-name" placeholder="Enter middle name (optional)" :class="inputClass(errors.middle_name)" @input="clearFieldError('middle_name')" />
                                    </FormField>
                                </template>

                                <FormField label="Business email" required :error="errors.business_email">
                                    <input v-model.trim="form.business_email" type="email" autocomplete="email" placeholder="business@example.com" :class="inputClass(errors.business_email)" @input="clearFieldError('business_email')" />
                                </FormField>
                                <FormField label="Business phone" required hint="Exactly 11 digits" :error="errors.business_phone">
                                    <div class="relative">
                                        <PhoneIcon class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                        <input v-model="form.business_phone" type="tel" inputmode="numeric" maxlength="11" autocomplete="tel" placeholder="08012345678" :class="[inputClass(errors.business_phone), 'pl-11']" @input="onPhoneInput" />
                                    </div>
                                </FormField>
                                <FormField label="Website URL" required hint="Include https://" :error="errors.website_url" class="sm:col-span-2">
                                    <div class="relative">
                                        <GlobeAltIcon class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                        <input v-model.trim="form.website_url" type="url" inputmode="url" autocomplete="url" placeholder="https://yourbusiness.com" :class="[inputClass(errors.website_url), 'pl-11']" @input="clearFieldError('website_url')" />
                                    </div>
                                </FormField>
                                <FormField label="Business category" required :error="errors.business_category" class="sm:col-span-2">
                                    <select v-model="form.business_category" :class="inputClass(errors.business_category)" @change="clearFieldError('business_category')">
                                        <option value="" disabled>Select your industry</option>
                                        <option>VTU / Data Services</option>
                                        <option>E-commerce</option>
                                        <option>Financial Services</option>
                                        <option>Education</option>
                                        <option>Logistics</option>
                                        <option>Other</option>
                                    </select>
                                </FormField>
                                <FormField label="Business description" required :hint="`${form.business_description.length}/50 minimum characters`" :error="errors.business_description" class="sm:col-span-2">
                                    <textarea v-model.trim="form.business_description" rows="5" placeholder="Briefly describe what your business offers, who your customers are, and how you intend to use MGS API..." :class="inputClass(errors.business_description)" @input="clearFieldError('business_description')"></textarea>
                                </FormField>
                            </div>
                        </section>

                        <section v-else-if="activeStep.key === 'address'" class="space-y-6">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <FormField label="Country" required :error="errors.country">
                                    <input v-model.trim="form.country" type="text" autocomplete="country-name" placeholder="Nigeria" :class="inputClass(errors.country)" @input="clearFieldError('country')" />
                                </FormField>
                                <FormField label="State" required :error="errors.state">
                                    <input v-model.trim="form.state" type="text" autocomplete="address-level1" placeholder="e.g. Lagos" :class="inputClass(errors.state)" @input="clearFieldError('state')" />
                                </FormField>
                                <FormField label="City / LGA" required :error="errors.city">
                                    <input v-model.trim="form.city" type="text" autocomplete="address-level2" placeholder="e.g. Ikeja" :class="inputClass(errors.city)" @input="clearFieldError('city')" />
                                </FormField>
                                <FormField label="Nearest landmark" :error="errors.landmark">
                                    <input v-model.trim="form.landmark" type="text" placeholder="Optional landmark" :class="inputClass(errors.landmark)" @input="clearFieldError('landmark')" />
                                </FormField>
                                <FormField label="Street address" required :error="errors.street_address" class="sm:col-span-2">
                                    <textarea v-model.trim="form.street_address" rows="3" autocomplete="street-address" placeholder="House number, street name and area" :class="inputClass(errors.street_address)" @input="clearFieldError('street_address')"></textarea>
                                </FormField>
                            </div>
                            <FileUploader label="Proof of address" required description="Upload a recent utility bill, bank statement or tenancy document." :file="files.proof_of_address" :error="errors.proof_of_address" @change="setFile($event, 'proof_of_address')" @remove="removeFile('proof_of_address')" />
                        </section>

                        <section v-else-if="activeStep.key === 'identity'" class="space-y-6">
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-300">
                                <div class="flex gap-3"><InformationCircleIcon class="mt-0.5 h-5 w-5 shrink-0" /><p>Your NIN and BVN must belong to the business owner or authorized representative.</p></div>
                            </div>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <FormField label="National Identification Number (NIN)" required hint="Exactly 11 digits" :error="errors.nin">
                                    <input v-model="form.nin" type="text" inputmode="numeric" maxlength="11" placeholder="Enter 11-digit NIN" :class="inputClass(errors.nin)" @input="onDigitInput('nin', 11)" />
                                </FormField>
                                <FormField label="Bank Verification Number (BVN)" required hint="Exactly 11 digits" :error="errors.bvn">
                                    <input v-model="form.bvn" type="text" inputmode="numeric" maxlength="11" placeholder="Enter 11-digit BVN" :class="inputClass(errors.bvn)" @input="onDigitInput('bvn', 11)" />
                                </FormField>
                            </div>
                        </section>

                        <section v-else-if="activeStep.key === 'settlement'" class="space-y-6">
                            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                                <div class="flex gap-3"><BanknotesIcon class="mt-0.5 h-5 w-5 shrink-0 text-indigo-600" /><div><p class="font-semibold text-slate-900 dark:text-white">Where should we settle your funds?</p><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Use an active Nigerian bank account that matches your identity or registered company name.</p></div></div>
                            </div>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <FormField label="Bank name" required :error="errors.bank_name">
                                    <select v-model="form.bank_name" :class="inputClass(errors.bank_name)" @change="clearFieldError('bank_name')">
                                        <option value="" disabled>Select bank</option>
                                        <option>Access Bank</option><option>GTBank</option><option>First Bank</option><option>Zenith Bank</option><option>UBA</option><option>Moniepoint</option><option>OPay</option><option>PalmPay</option>
                                    </select>
                                </FormField>
                                <FormField label="Account number" required hint="Exactly 10 digits" :error="errors.account_number">
                                    <input v-model="form.account_number" type="text" inputmode="numeric" maxlength="10" placeholder="0123456789" :class="inputClass(errors.account_number)" @input="onDigitInput('account_number', 10)" />
                                </FormField>
                                <FormField label="Account name" required :error="errors.account_name" class="sm:col-span-2">
                                    <input v-model.trim="form.account_name" type="text" autocomplete="name" placeholder="Name shown on the bank account" :class="inputClass(errors.account_name)" @input="clearFieldError('account_name')" />
                                </FormField>
                            </div>
                            <p v-if="form.business_type === 'registered'" class="flex items-center gap-2 text-sm font-medium text-amber-700 dark:text-amber-300"><ExclamationTriangleIcon class="h-4 w-4" /> Corporate account name should match the registered company name.</p>
                        </section>

                        <section v-else-if="activeStep.key === 'cac'" class="space-y-6">
                            <FormField label="CAC / RC number" required :error="errors.cac_number">
                                <input v-model.trim="form.cac_number" type="text" placeholder="e.g. RC1234567" :class="inputClass(errors.cac_number)" @input="clearFieldError('cac_number')" />
                            </FormField>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <FileUploader label="CAC certificate" required description="Upload the certificate issued by CAC." :file="files.cac_certificate" :error="errors.cac_certificate" @change="setFile($event, 'cac_certificate')" @remove="removeFile('cac_certificate')" />
                                <FileUploader label="CAC status report" required description="Upload your latest CAC status report." :file="files.cac_status_report" :error="errors.cac_status_report" @change="setFile($event, 'cac_status_report')" @remove="removeFile('cac_status_report')" />
                            </div>
                        </section>

                        <section v-else class="space-y-6">
                            <div class="rounded-3xl border border-slate-200 p-5 dark:border-white/10 sm:p-6">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"><ClipboardDocumentCheckIcon class="h-6 w-6" /></div>
                                    <div><h3 class="font-bold text-slate-900 dark:text-white">Review and confirm</h3><p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">Confirm that the details and documents provided are complete and accurate before submission.</p></div>
                                </div>
                                <dl class="mt-5 grid gap-3 rounded-2xl bg-slate-50 p-4 text-sm dark:bg-white/5 sm:grid-cols-2">
                                    <div><dt class="text-xs text-slate-400">Business</dt><dd class="mt-1 font-semibold text-slate-800 dark:text-slate-100">{{ form.business_name || 'Not provided' }}</dd></div>
                                    <div v-if="form.business_type === 'starter'"><dt class="text-xs text-slate-400">Business owner</dt><dd class="mt-1 font-semibold text-slate-800 dark:text-slate-100">{{ [form.first_name, form.middle_name, form.last_name].filter(Boolean).join(' ') || 'Not provided' }}</dd></div>
                                    <div><dt class="text-xs text-slate-400">Type</dt><dd class="mt-1 font-semibold capitalize text-slate-800 dark:text-slate-100">{{ form.business_type }}</dd></div>
                                    <div><dt class="text-xs text-slate-400">Website</dt><dd class="mt-1 truncate font-semibold text-slate-800 dark:text-slate-100">{{ form.website_url || 'Not provided' }}</dd></div>
                                    <div><dt class="text-xs text-slate-400">Settlement bank</dt><dd class="mt-1 font-semibold text-slate-800 dark:text-slate-100">{{ form.bank_name || 'Not provided' }}</dd></div>
                                </dl>
                            </div>
                            <label class="flex cursor-pointer items-start gap-4 rounded-2xl border p-4 transition" :class="form.accept_terms ? 'border-indigo-500 bg-indigo-50/60 ring-4 ring-indigo-500/5 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-white/10'">
                                <input v-model="form.accept_terms" type="checkbox" class="mt-0.5 h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-transparent" @change="clearFieldError('accept_terms')" />
                                <span class="text-sm leading-6 text-slate-600 dark:text-slate-300">I confirm that the information supplied is accurate, and I agree to the <a href="#" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Merchant Agreement</a>, <a href="#" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Privacy Policy</a>, and indemnity terms.</span>
                            </label>
                            <p v-if="errors.accept_terms" class="text-sm font-medium text-rose-600">{{ errors.accept_terms }}</p>
                        </section>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/70 px-5 py-4 dark:border-white/10 dark:bg-white/[0.02] sm:flex-row sm:items-center sm:justify-between sm:px-7">
                        <button v-if="currentStep > 0" type="button" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10" @click="prevStep"><ArrowLeftIcon class="h-4 w-4" /> Back</button>
                        <span v-else class="hidden sm:block"></span>
                        <button type="submit" :disabled="loading" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                            <ArrowPathIcon v-if="loading" class="h-4 w-4 animate-spin" />
                            <template v-if="currentStep < steps.length - 1">Continue <ArrowRightIcon class="h-4 w-4" /></template>
                            <template v-else>{{ loading ? 'Submitting application...' : 'Submit application' }}<PaperAirplaneIcon v-if="!loading" class="h-4 w-4" /></template>
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, defineComponent, h, reactive, ref, watch } from 'vue';
import axios from 'axios';
import {
    ArrowLeftIcon, ArrowPathIcon, ArrowRightIcon, BanknotesIcon, BuildingOffice2Icon,
    CheckCircleIcon, CheckIcon, ClipboardDocumentCheckIcon, DocumentTextIcon,
    ExclamationCircleIcon, ExclamationTriangleIcon, GlobeAltIcon, HomeModernIcon,
    IdentificationIcon, InformationCircleIcon, LockClosedIcon, PaperAirplaneIcon,
    PhoneIcon, ShieldCheckIcon, UserIcon, XMarkIcon,
} from '@heroicons/vue/24/outline';

const FieldLabel = defineComponent({
    props: { label: String, required: Boolean },
    setup(props) { return () => h('label', { class: 'block text-sm font-semibold text-slate-800 dark:text-slate-200' }, [props.label, props.required ? h('span', { class: 'ml-1 text-rose-500' }, '*') : null]); },
});

const FormField = defineComponent({
    props: { label: String, required: Boolean, hint: String, error: String },
    setup(props, { slots, attrs }) {
        return () => h('div', attrs, [
            h('div', { class: 'mb-2 flex items-center justify-between gap-3' }, [h(FieldLabel, { label: props.label, required: props.required }), props.hint ? h('span', { class: 'text-xs text-slate-400' }, props.hint) : null]),
            slots.default?.(),
            props.error ? h('p', { class: 'mt-2 flex items-center gap-1.5 text-xs font-medium text-rose-600' }, [h(ExclamationCircleIcon, { class: 'h-4 w-4' }), props.error]) : null,
        ]);
    },
});

const FileUploader = defineComponent({
    props: { label: String, required: Boolean, description: String, file: Object, error: String },
    emits: ['change', 'remove'],
    setup(props, { emit }) {
        const input = ref(null);
        return () => h('div', {}, [
            h('div', { class: 'mb-2 flex items-center justify-between' }, [h(FieldLabel, { label: props.label, required: props.required }), h('span', { class: 'text-xs text-slate-400' }, 'PDF, JPG or PNG')]),
            h('div', { class: ['rounded-2xl border-2 border-dashed p-5 text-center transition', props.error ? 'border-rose-300 bg-rose-50/40 dark:bg-rose-950/10' : props.file ? 'border-emerald-300 bg-emerald-50/40 dark:border-emerald-800 dark:bg-emerald-950/10' : 'border-slate-200 hover:border-indigo-400 hover:bg-indigo-50/30 dark:border-white/10 dark:hover:border-indigo-500/60 dark:hover:bg-indigo-500/5'] }, [
                h('input', { ref: input, type: 'file', accept: '.pdf,.jpg,.jpeg,.png', class: 'hidden', onChange: e => emit('change', e) }),
                props.file ? h('div', { class: 'flex items-center gap-3 text-left' }, [
                    h('span', { class: 'flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' }, [h(DocumentTextIcon, { class: 'h-6 w-6' })]),
                    h('div', { class: 'min-w-0 flex-1' }, [h('p', { class: 'truncate text-sm font-semibold text-slate-800 dark:text-slate-100' }, props.file.name), h('p', { class: 'mt-0.5 text-xs text-slate-400' }, `${(props.file.size / 1024 / 1024).toFixed(2)} MB`)]),
                    h('button', { type: 'button', class: 'rounded-xl p-2 text-slate-400 hover:bg-white hover:text-rose-600 dark:hover:bg-white/5', onClick: () => emit('remove') }, [h(XMarkIcon, { class: 'h-5 w-5' })]),
                ]) : h('button', { type: 'button', class: 'w-full', onClick: () => input.value?.click() }, [h('span', { class: 'mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-300' }, [h(DocumentTextIcon, { class: 'h-6 w-6' })]), h('span', { class: 'mt-3 block text-sm font-semibold text-slate-700 dark:text-slate-200' }, 'Choose a file to upload'), h('span', { class: 'mt-1 block text-xs leading-5 text-slate-400' }, props.description)]),
            ]),
            props.error ? h('p', { class: 'mt-2 flex items-center gap-1.5 text-xs font-medium text-rose-600' }, [h(ExclamationCircleIcon, { class: 'h-4 w-4' }), props.error]) : null,
        ]);
    },
});

const loading = ref(false);
const currentStep = ref(0);
const notice = reactive({ type: '', message: '' });
const errors = reactive({});
const files = reactive({ proof_of_address: null, cac_certificate: null, cac_status_report: null });
const form = reactive({ business_type: 'starter', business_name: '', first_name: '', last_name: '', middle_name: '', business_email: '', business_phone: '', website_url: '', business_category: '', business_description: '', cac_number: '', country: 'Nigeria', state: '', city: '', street_address: '', landmark: '', nin: '', bvn: '', account_number: '', bank_name: '', account_name: '', accept_terms: false });

const businessTypes = [
    { value: 'starter', title: 'Starter business', description: 'For individuals, freelancers and small businesses that are not CAC registered.', icon: UserIcon },
    { value: 'registered', title: 'Registered business', description: 'For CAC-registered business names, companies and incorporated entities.', icon: BuildingOffice2Icon },
];

const steps = computed(() => {
    const items = [
        { key: 'business', title: 'Business details', shortTitle: 'Business', subtitle: 'Profile and contact details', heading: 'Tell us about your business', description: 'Provide the public and contact information customers use to identify your business.', icon: BuildingOffice2Icon },
        { key: 'address', title: 'Business address', shortTitle: 'Address', subtitle: 'Location and proof', heading: 'Confirm your operating address', description: 'Enter your current business location and upload a valid proof of address.', icon: HomeModernIcon },
        { key: 'identity', title: 'Identity verification', shortTitle: 'Identity', subtitle: 'NIN and BVN', heading: 'Verify the business owner', description: 'We use these identity details to meet regulatory and security requirements.', icon: IdentificationIcon },
        { key: 'settlement', title: 'Settlement account', shortTitle: 'Bank', subtitle: 'Where payouts are sent', heading: 'Add a settlement bank account', description: 'This is the Nigerian bank account where your business settlements will be paid.', icon: BanknotesIcon },
    ];
    if (form.business_type === 'registered') items.push({ key: 'cac', title: 'CAC documents', shortTitle: 'CAC', subtitle: 'Registration evidence', heading: 'Upload company documents', description: 'Provide your CAC registration number, certificate and latest status report.', icon: DocumentTextIcon });
    items.push({ key: 'agreement', title: 'Review & submit', shortTitle: 'Submit', subtitle: 'Confirm your application', heading: 'Review and submit your application', description: 'Check the information provided and accept the merchant terms before submission.', icon: ClipboardDocumentCheckIcon });
    return items;
});

const activeStep = computed(() => steps.value[currentStep.value] || steps.value[0]);
const isValidWebsiteUrl = value => { try { return ['http:', 'https:'].includes(new URL(value).protocol); } catch { return false; } };
const isStepComplete = key => ({
    business: Boolean(form.business_name && (form.business_type !== 'starter' || (form.first_name && form.last_name)) && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.business_email) && /^\d{11}$/.test(form.business_phone) && isValidWebsiteUrl(form.website_url) && form.business_category && form.business_description.length >= 50),
    address: Boolean(form.country && form.state && form.city && form.street_address && files.proof_of_address),
    identity: /^\d{11}$/.test(form.nin) && /^\d{11}$/.test(form.bvn),
    settlement: /^\d{10}$/.test(form.account_number) && Boolean(form.bank_name && form.account_name),
    cac: Boolean(form.cac_number && files.cac_certificate && files.cac_status_report),
    agreement: form.accept_terms,
}[key] ?? false);
const progress = computed(() => Math.round((steps.value.filter(s => isStepComplete(s.key)).length / steps.value.length) * 100));

watch(steps, value => { if (currentStep.value >= value.length) currentStep.value = value.length - 1; });
const inputClass = error => ['w-full rounded-2xl border bg-white px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:ring-4 dark:bg-white/[0.03] dark:text-white', error ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/10 dark:border-rose-500' : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/10 dark:border-white/10 dark:focus:border-indigo-500'];
const clearNotice = () => { notice.type = ''; notice.message = ''; };
const clearFieldError = key => { delete errors[key]; clearNotice(); };
const digitsOnly = (value, max) => String(value ?? '').replace(/\D/g, '').slice(0, max);
const onPhoneInput = () => { form.business_phone = digitsOnly(form.business_phone, 11); clearFieldError('business_phone'); };
const onDigitInput = (key, max) => { form[key] = digitsOnly(form[key], max); clearFieldError(key); };
const setFile = (event, key) => { files[key] = event.target.files?.[0] || null; clearFieldError(key); };
const removeFile = key => { files[key] = null; };
const stepIconClass = (step, index) => isStepComplete(step.key) ? 'border-emerald-200 bg-emerald-50 text-emerald-600 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300' : index === currentStep.value ? 'border-indigo-200 bg-indigo-600 text-white dark:border-indigo-500' : 'border-slate-200 bg-white text-slate-400 dark:border-white/10 dark:bg-white/5';

const validateStep = key => {
    Object.keys(errors).forEach(k => delete errors[k]);
    if (key === 'business') {
        if (!form.business_name) errors.business_name = 'Business name is required.';
        if (form.business_type === 'starter') {
            if (!form.first_name) errors.first_name = 'First name is required for a starter business.';
            if (!form.last_name) errors.last_name = 'Last name / surname is required for a starter business.';
        }
        if (!form.business_email) errors.business_email = 'Business email is required.'; else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.business_email)) errors.business_email = 'Enter a valid business email address.';
        if (!form.business_phone) errors.business_phone = 'Business phone number is required.'; else if (!/^\d{11}$/.test(form.business_phone)) errors.business_phone = 'Phone number must contain exactly 11 digits.';
        if (!form.website_url) errors.website_url = 'Website URL is required.'; else if (!isValidWebsiteUrl(form.website_url)) errors.website_url = 'Enter a valid URL including http:// or https://.';
        if (!form.business_category) errors.business_category = 'Select a business category.';
        if (!form.business_description) errors.business_description = 'Business description is required.'; else if (form.business_description.length < 50) errors.business_description = 'Description must contain at least 50 characters.';
    } else if (key === 'address') {
        if (!form.country) errors.country = 'Country is required.'; if (!form.state) errors.state = 'State is required.'; if (!form.city) errors.city = 'City or LGA is required.'; if (!form.street_address) errors.street_address = 'Street address is required.'; if (!files.proof_of_address) errors.proof_of_address = 'Upload a valid proof of address.';
    } else if (key === 'identity') {
        if (!/^\d{11}$/.test(form.nin)) errors.nin = 'NIN must contain exactly 11 digits.'; if (!/^\d{11}$/.test(form.bvn)) errors.bvn = 'BVN must contain exactly 11 digits.';
    } else if (key === 'settlement') {
        if (!form.bank_name) errors.bank_name = 'Select a bank.'; if (!/^\d{10}$/.test(form.account_number)) errors.account_number = 'Account number must contain exactly 10 digits.'; if (!form.account_name) errors.account_name = 'Account name is required.';
    } else if (key === 'cac') {
        if (!form.cac_number) errors.cac_number = 'CAC or RC number is required.'; if (!files.cac_certificate) errors.cac_certificate = 'Upload the CAC certificate.'; if (!files.cac_status_report) errors.cac_status_report = 'Upload the CAC status report.';
    } else if (key === 'agreement' && !form.accept_terms) errors.accept_terms = 'You must accept the merchant terms before submitting.';
    const valid = Object.keys(errors).length === 0;
    if (!valid) { notice.type = 'error'; notice.message = 'Complete the highlighted fields before continuing.'; window.scrollTo({ top: 0, behavior: 'smooth' }); }
    return valid;
};

const nextStep = () => { if (validateStep(activeStep.value.key) && currentStep.value < steps.value.length - 1) { currentStep.value++; clearNotice(); window.scrollTo({ top: 0, behavior: 'smooth' }); } };
const prevStep = () => { if (currentStep.value > 0) { currentStep.value--; clearNotice(); window.scrollTo({ top: 0, behavior: 'smooth' }); } };
const goToStep = index => { if (index <= currentStep.value || steps.value.slice(0, index).every(step => isStepComplete(step.key))) { currentStep.value = index; clearNotice(); } else { notice.type = 'error'; notice.message = 'Complete the previous steps before moving ahead.'; } };
const handlePrimaryAction = () => currentStep.value < steps.value.length - 1 ? nextStep() : submitActivation();

const submitActivation = async () => {
    if (!validateStep('agreement')) return;
    loading.value = true; clearNotice();
    try {
        const data = new FormData();
        Object.entries(form).forEach(([key, value]) => data.append(key, value));
        Object.entries(files).forEach(([key, value]) => { if (value) data.append(key, value); });
        await axios.post('/api/v1/merchant/activation', data, { headers: { 'Content-Type': 'multipart/form-data' } });
        notice.type = 'success'; notice.message = 'Your business activation application has been submitted successfully and is now awaiting review.';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
        const apiErrors = error.response?.data?.errors || {};
        Object.entries(apiErrors).forEach(([key, value]) => { errors[key] = Array.isArray(value) ? value[0] : value; });
        notice.type = 'error'; notice.message = error.response?.data?.message || 'We could not submit your application. Please review your information and try again.';
        const firstKey = Object.keys(apiErrors)[0];
        if (firstKey) { const stepMap = { business_name: 'business', first_name: 'business', last_name: 'business', middle_name: 'business', business_email: 'business', business_phone: 'business', website_url: 'business', business_category: 'business', business_description: 'business', country: 'address', state: 'address', city: 'address', street_address: 'address', proof_of_address: 'address', nin: 'identity', bvn: 'identity', account_number: 'settlement', bank_name: 'settlement', account_name: 'settlement', cac_number: 'cac', cac_certificate: 'cac', cac_status_report: 'cac' }; const target = steps.value.findIndex(s => s.key === stepMap[firstKey]); if (target >= 0) currentStep.value = target; }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } finally { loading.value = false; }
};
</script>
