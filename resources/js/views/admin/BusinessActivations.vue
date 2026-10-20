<template>
    <div class="space-y-6">
        <!-- Page heading -->
        <section
            class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
        >
            <div>
                <p
                    class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400"
                >
                    Merchant onboarding
                </p>

                <h2
                    class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl"
                >
                    Business Activations
                </h2>

                <p
                    class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400"
                >
                    Review business details, identity information,
                    settlement accounts and uploaded documents before
                    approving live access.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                :disabled="loading"
                @click="fetchActivations(1)"
            >
                <span>↻</span>

                {{ loading ? 'Refreshing…' : 'Refresh applications' }}
            </button>
        </section>

        <!-- Summary cards -->
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="summary in summaries"
                :key="summary.label"
                class="flex min-h-[120px] items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <span
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-xl"
                    :class="summary.iconClass"
                >
                    {{ summary.icon }}
                </span>

                <span>
                    <span
                        class="block text-2xl font-black text-slate-950 dark:text-white"
                    >
                        {{ summary.value }}
                    </span>

                    <span
                        class="mt-1 block text-sm font-semibold text-slate-500"
                    >
                        {{ summary.label }}
                    </span>
                </span>
            </article>
        </section>

        <!-- Filters -->
        <section
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto]">
                <!-- Search -->
                <form
                    class="relative"
                    @submit.prevent="applyFilters"
                >
                    <span
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"
                    >
                        ⌕
                    </span>

                    <input
                        v-model="filters.search"
                        type="search"
                        placeholder="Search business, owner, email, phone, website or CAC number..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-28 text-sm outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-950 dark:focus:bg-slate-950"
                    />

                    <button
                        type="submit"
                        class="absolute bottom-1.5 right-1.5 top-1.5 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white hover:bg-indigo-700"
                    >
                        Search
                    </button>
                </form>

                <button
                    type="button"
                    class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    @click="resetFilters"
                >
                    Clear filters
                </button>
            </div>

            <!-- Status filters -->
            <div class="mt-5">
                <p
                    class="mb-2 text-[11px] font-black uppercase tracking-wider text-slate-400"
                >
                    Application status
                </p>

                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="option in statusOptions"
                        :key="option.value"
                        type="button"
                        class="rounded-xl border px-4 py-2 text-sm font-bold transition"
                        :class="
                            filters.status === option.value
                                ? 'border-indigo-600 bg-indigo-600 text-white'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300'
                        "
                        @click="setStatus(option.value)"
                    >
                        {{ option.label }}
                    </button>
                </div>
            </div>

            <!-- Additional filters -->
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <label
                        class="mb-2 block text-[11px] font-black uppercase tracking-wider text-slate-400"
                    >
                        Business type
                    </label>

                    <select
                        v-model="filters.business_type"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-950"
                        @change="applyFilters"
                    >
                        <option value="">
                            All business types
                        </option>

                        <option value="starter">
                            Starter businesses
                        </option>

                        <option value="registered">
                            Registered businesses
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        class="mb-2 block text-[11px] font-black uppercase tracking-wider text-slate-400"
                    >
                        Submission period
                    </label>

                    <select
                        v-model="filters.period"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-950"
                        @change="applyFilters"
                    >
                        <option value="">
                            All dates
                        </option>

                        <option value="today">
                            Today
                        </option>

                        <option value="week">
                            This week
                        </option>

                        <option value="month">
                            This month
                        </option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Feedback -->
        <section
            v-if="successMessage"
            class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300"
        >
            {{ successMessage }}
        </section>

        <section
            v-if="errorMessage"
            class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
        >
            {{ errorMessage }}
        </section>

        <!-- Loading -->
        <section
            v-if="loading"
            class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"
        >
            <div
                v-for="index in 6"
                :key="index"
                class="h-80 animate-pulse rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="h-12 w-12 rounded-2xl bg-slate-200 dark:bg-slate-800"></div>
                <div class="mt-5 h-5 w-2/3 rounded bg-slate-200 dark:bg-slate-800"></div>
                <div class="mt-3 h-4 w-1/2 rounded bg-slate-200 dark:bg-slate-800"></div>
                <div class="mt-8 h-4 w-full rounded bg-slate-200 dark:bg-slate-800"></div>
                <div class="mt-3 h-4 w-4/5 rounded bg-slate-200 dark:bg-slate-800"></div>
            </div>
        </section>

        <!-- Empty -->
        <section
            v-else-if="activations.length === 0"
            class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-20 text-center dark:border-slate-700 dark:bg-slate-900"
        >
            <span
                class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-100 text-3xl dark:bg-indigo-950"
            >
                🏢
            </span>

            <h3 class="mt-5 text-xl font-black">
                No activation applications found
            </h3>

            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                No business application matches the selected filters.
            </p>

            <button
                type="button"
                class="mt-5 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white"
                @click="resetFilters"
            >
                Clear filters
            </button>
        </section>

        <!-- Application cards -->
        <section
            v-else
            class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="activation in activations"
                :key="activation.id"
                class="flex min-h-[365px] flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-900"
            >
                <div class="flex items-start justify-between gap-4">
                    <span
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-xl"
                        :class="
                            activation.business_type === 'registered'
                                ? 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300'
                                : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300'
                        "
                    >
                        {{
                            activation.business_type === 'registered'
                                ? '🏢'
                                : '🚀'
                        }}
                    </span>

                    <span
                        class="rounded-full px-3 py-1 text-xs font-black"
                        :class="statusClass(activation.status)"
                    >
                        {{ formatStatus(activation.status) }}
                    </span>
                </div>

                <div class="mt-5">
                    <p
                        class="text-xs font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400"
                    >
                        {{ formatBusinessType(activation.business_type) }}
                    </p>

                    <h3
                        class="mt-1 line-clamp-2 text-xl font-black text-slate-950 dark:text-white"
                    >
                        {{ activation.business_name }}
                    </h3>

                    <p class="mt-2 text-sm text-slate-500">
                        Owner: {{ ownerName(activation) }}
                    </p>
                </div>

                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            Email
                        </dt>

                        <dd
                            class="max-w-[65%] truncate text-right font-semibold"
                        >
                            {{ activation.business_email || 'N/A' }}
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            Phone
                        </dt>

                        <dd class="font-semibold">
                            {{ activation.business_phone || 'N/A' }}
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            Website
                        </dt>

                        <dd class="max-w-[65%] truncate text-right">
                            <a
                                v-if="activation.website_url"
                                :href="activation.website_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                            >
                                {{ activation.website_url }}
                            </a>

                            <span v-else>
                                N/A
                            </span>
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            Submitted
                        </dt>

                        <dd class="text-right font-semibold">
                            {{ formatDate(activation.created_at) }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-auto pt-6">
                    <button
                        type="button"
                        class="flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700"
                        @click="openReview(activation)"
                    >
                        Review application
                    </button>
                </div>
            </article>
        </section>

        <!-- Pagination -->
        <section
            v-if="pagination.last_page > 1"
            class="flex flex-col items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900 sm:flex-row"
        >
            <p class="text-sm text-slate-500">
                Showing {{ pagination.from || 0 }}–{{ pagination.to || 0 }}
                of {{ pagination.total || 0 }} applications
            </p>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700"
                    :disabled="pagination.current_page <= 1"
                    @click="fetchActivations(pagination.current_page - 1)"
                >
                    Previous
                </button>

                <span class="px-3 text-sm font-bold">
                    {{ pagination.current_page }}
                    /
                    {{ pagination.last_page }}
                </span>

                <button
                    type="button"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700"
                    :disabled="
                        pagination.current_page >=
                        pagination.last_page
                    "
                    @click="fetchActivations(pagination.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </section>

        <!-- Review modal -->
        <Teleport to="body">
            <div
                v-if="reviewOpen"
                class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-5"
                @click.self="closeReview"
            >
                <div
                    class="flex max-h-[95vh] w-full max-w-6xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 shadow-2xl dark:border-slate-700 dark:bg-slate-950"
                >
                    <!-- Modal header -->
                    <header
                        class="flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-5 dark:border-slate-800 dark:bg-slate-900 sm:px-7"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-3">
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black"
                                    :class="statusClass(selectedActivation.status)"
                                >
                                    {{ formatStatus(selectedActivation.status) }}
                                </span>

                                <span
                                    class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                                >
                                    {{
                                        formatBusinessType(
                                            selectedActivation.business_type
                                        )
                                    }}
                                </span>
                            </div>

                            <h3
                                class="mt-3 truncate text-2xl font-black text-slate-950 dark:text-white"
                            >
                                {{ selectedActivation.business_name }}
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Submitted
                                {{ formatDateTime(selectedActivation.created_at) }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-xl text-slate-500 transition hover:bg-slate-100 dark:hover:bg-slate-800"
                            @click="closeReview"
                        >
                            ✕
                        </button>
                    </header>

                    <!-- Modal body -->
                    <div class="flex-1 overflow-y-auto p-4 sm:p-7">
                        <div
                            v-if="reviewLoading"
                            class="py-24 text-center text-slate-500"
                        >
                            Loading complete application…
                        </div>

                        <div v-else class="space-y-6">
                            <!-- Business information -->
                            <ReviewSection
                                title="Business Information"
                                icon="🏢"
                            >
                                <InfoGrid>
                                    <InfoItem
                                        label="Business Name"
                                        :value="selectedActivation.business_name"
                                    />

                                    <InfoItem
                                        label="Business Type"
                                        :value="
                                            formatBusinessType(
                                                selectedActivation.business_type
                                            )
                                        "
                                    />

                                    <InfoItem
                                        label="Business Category"
                                        :value="selectedActivation.business_category"
                                    />

                                    <InfoItem
                                        label="Website URL"
                                        :value="selectedActivation.website_url"
                                        :link="selectedActivation.website_url"
                                    />

                                    <InfoItem
                                        class="md:col-span-2"
                                        label="Business Description"
                                        :value="selectedActivation.business_description"
                                    />
                                </InfoGrid>
                            </ReviewSection>

                            <!-- Starter personal information -->
                            <ReviewSection
                                v-if="
                                    selectedActivation.business_type ===
                                    'starter'
                                "
                                title="Personal Information"
                                icon="👤"
                            >
                                <InfoGrid>
                                    <InfoItem
                                        label="First Name"
                                        :value="selectedActivation.first_name"
                                    />

                                    <InfoItem
                                        label="Last Name / Surname"
                                        :value="selectedActivation.last_name"
                                    />

                                    <InfoItem
                                        label="Middle Name"
                                        :value="
                                            selectedActivation.middle_name ||
                                            'Not provided'
                                        "
                                    />

                                    <InfoItem
                                        label="Account Owner"
                                        :value="ownerName(selectedActivation)"
                                    />
                                </InfoGrid>
                            </ReviewSection>

                            <!-- Contact -->
                            <ReviewSection
                                title="Contact Information"
                                icon="✉️"
                            >
                                <InfoGrid>
                                    <InfoItem
                                        label="Business Email"
                                        :value="selectedActivation.business_email"
                                    />

                                    <InfoItem
                                        label="Business Phone"
                                        :value="selectedActivation.business_phone"
                                    />
                                </InfoGrid>
                            </ReviewSection>

                            <!-- Address -->
                            <ReviewSection
                                title="Address Information"
                                icon="📍"
                            >
                                <InfoGrid>
                                    <InfoItem
                                        label="Country"
                                        :value="selectedActivation.country"
                                    />

                                    <InfoItem
                                        label="State"
                                        :value="selectedActivation.state"
                                    />

                                    <InfoItem
                                        label="City"
                                        :value="selectedActivation.city"
                                    />

                                    <InfoItem
                                        label="Landmark"
                                        :value="
                                            selectedActivation.landmark ||
                                            'Not provided'
                                        "
                                    />

                                    <InfoItem
                                        class="md:col-span-2"
                                        label="Street Address"
                                        :value="selectedActivation.street_address"
                                    />
                                </InfoGrid>
                            </ReviewSection>

                            <!-- Identity -->
                            <ReviewSection
                                title="Identity Information"
                                icon="🪪"
                            >
                                <p class="mb-4 text-sm font-semibold">Identity verification: {{ selectedActivation.kyc_status || 'pending' }}</p>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <SensitiveItem
                                        label="National Identification Number"
                                        :value="selectedActivation.nin"
                                        :revealed="showNin"
                                        @toggle="showNin = !showNin"
                                    />

                                    <SensitiveItem
                                        label="Bank Verification Number"
                                        :value="selectedActivation.bvn"
                                        :revealed="showBvn"
                                        @toggle="showBvn = !showBvn"
                                    />
                                </div>
                            </ReviewSection>

                            <!-- Bank -->
                            <ReviewSection
                                title="Settlement Account"
                                icon="🏦"
                            >
                                <InfoGrid>
                                    <InfoItem
                                        label="Bank Name"
                                        :value="selectedActivation.bank_name"
                                    />

                                    <InfoItem
                                        label="Account Name"
                                        :value="selectedActivation.account_name"
                                    />

                                    <InfoItem
                                        label="Account Number"
                                        :value="selectedActivation.account_number"
                                    />
                                </InfoGrid>
                            </ReviewSection>

                            <!-- CAC -->
                            <ReviewSection
                                v-if="
                                    selectedActivation.business_type ===
                                    'registered'
                                "
                                title="Company Registration"
                                icon="📑"
                            >
                                <InfoGrid>
                                    <InfoItem
                                        label="CAC Number"
                                        :value="
                                            selectedActivation.cac_number ||
                                            'Not provided'
                                        "
                                    />
                                </InfoGrid>
                            </ReviewSection>

                            <!-- Documents -->
                            <ReviewSection
                                title="Uploaded Documents"
                                icon="📎"
                            >
                                <div class="grid gap-4 md:grid-cols-3">
                                    <DocumentCard
                                        label="Proof of Address"
                                        :activation-id="selectedActivation.id"
                                        field="proof_of_address"
                                        :path="
                                            selectedActivation.proof_of_address
                                        "
                                    />

                                    <DocumentCard
                                        v-if="
                                            selectedActivation.business_type ===
                                            'registered'
                                        "
                                        label="CAC Certificate"
                                        :activation-id="selectedActivation.id"
                                        field="cac_certificate"
                                        :path="
                                            selectedActivation.cac_certificate
                                        "
                                    />

                                    <DocumentCard
                                        v-if="
                                            selectedActivation.business_type ===
                                            'registered'
                                        "
                                        label="CAC Status Report"
                                        :activation-id="selectedActivation.id"
                                        field="cac_status_report"
                                        :path="
                                            selectedActivation.cac_status_report
                                        "
                                    />
                                </div>
                            </ReviewSection>

                            <!-- Rejection details -->
                            <ReviewSection
                                v-if="
                                    selectedActivation.status === 'rejected' &&
                                    selectedActivation.rejection_reason
                                "
                                title="Rejection Information"
                                icon="⚠️"
                            >
                                <div
                                    class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm leading-6 text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
                                >
                                    {{ selectedActivation.rejection_reason }}
                                </div>
                            </ReviewSection>

                            <!-- Reject form -->
                            <ReviewSection
                                v-if="rejectFormOpen"
                                title="Reject Application"
                                icon="✕"
                            >
                                <label
                                    class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-300"
                                >
                                    Reason for rejection
                                </label>

                                <textarea
                                    v-model="rejectionReason"
                                    rows="5"
                                    maxlength="2000"
                                    placeholder="Explain clearly what the applicant needs to correct..."
                                    class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-500 focus:ring-4 focus:ring-red-500/10 dark:border-slate-700 dark:bg-slate-950"
                                ></textarea>

                                <div
                                    class="mt-2 flex items-center justify-between"
                                >
                                    <p class="text-sm text-red-600">
                                        {{ rejectionError }}
                                    </p>

                                    <p class="text-xs text-slate-400">
                                        {{ rejectionReason.length }}/2000
                                    </p>
                                </div>
                            </ReviewSection>
                        </div>
                    </div>

                    <!-- Actions -->
                    <footer
                        class="border-t border-slate-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900 sm:px-7"
                    >
                        <div
                            class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <button
                                type="button"
                                class="rounded-xl px-5 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                                :disabled="processing"
                                @click="closeReview"
                            >
                                Close
                            </button>

                            <div
                                v-if="
                                    selectedActivation.status !== 'approved'
                                "
                                class="flex flex-col gap-3 sm:flex-row"
                            >
                                <template v-if="rejectFormOpen">
                                    <button
                                        type="button"
                                        class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300"
                                        :disabled="processing"
                                        @click="cancelReject"
                                    >
                                        Cancel rejection
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-xl bg-red-600 px-5 py-3 text-sm font-black text-white transition hover:bg-red-700 disabled:opacity-50"
                                        :disabled="processing"
                                        @click="submitRejection"
                                    >
                                        {{
                                            processing
                                                ? 'Rejecting…'
                                                : 'Confirm rejection'
                                        }}
                                    </button>
                                </template>

                                <template v-else>
                                    <button
                                        v-if="
                                            selectedActivation.status !==
                                            'rejected'
                                        "
                                        type="button"
                                        class="rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-black text-red-700 transition hover:bg-red-100 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
                                        :disabled="processing"
                                        @click="rejectFormOpen = true"
                                    >
                                        Reject business
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-xl bg-emerald-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 disabled:opacity-50"
                                        :disabled="processing"
                                        @click="approveApplication"
                                    >
                                        {{
                                            processing
                                                ? 'Approving…'
                                                : 'Approve business'
                                        }}
                                    </button>
                                </template>
                            </div>

                            <span
                                v-else
                                class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-4 py-2 text-sm font-black text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                            >
                                ✓ Business approved
                            </span>
                        </div>
                    </footer>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script>
import DocumentCard from '../../components/ActivationDocumentCard.vue';
import {
    computed,
    defineComponent,
    h,
    onBeforeUnmount,
    onMounted,
    ref,
} from 'vue';

import axios from 'axios';
import { verificationModal } from '../../services/verificationModal';

/*
|--------------------------------------------------------------------------
| Small local presentation components
|--------------------------------------------------------------------------
*/

const ReviewSection = defineComponent({
    name: 'ReviewSection',

    props: {
        title: {
            type: String,
            required: true,
        },

        icon: {
            type: String,
            default: '•',
        },
    },

    setup(props, { slots }) {
        return () =>
            h(
                'section',
                {
                    class:
                        'rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6',
                },
                [
                    h(
                        'div',
                        {
                            class:
                                'mb-5 flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-slate-800',
                        },
                        [
                            h(
                                'span',
                                {
                                    class:
                                        'flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-lg dark:bg-indigo-950',
                                },
                                props.icon
                            ),

                            h(
                                'h4',
                                {
                                    class:
                                        'text-base font-black text-slate-950 dark:text-white',
                                },
                                props.title
                            ),
                        ]
                    ),

                    slots.default?.(),
                ]
            );
    },
});

const InfoGrid = defineComponent({
    name: 'InfoGrid',

    setup(_, { slots }) {
        return () =>
            h(
                'dl',
                {
                    class: 'grid gap-4 md:grid-cols-2',
                },
                slots.default?.()
            );
    },
});

const InfoItem = defineComponent({
    name: 'InfoItem',

    inheritAttrs: false,

    props: {
        label: {
            type: String,
            required: true,
        },

        value: {
            type: [String, Number],
            default: '',
        },

        link: {
            type: String,
            default: '',
        },
    },

    setup(props, { attrs }) {
        return () =>
            h(
                'div',
                {
                    ...attrs,
                    class: [
                        'rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/60',
                        attrs.class,
                    ],
                },
                [
                    h(
                        'dt',
                        {
                            class:
                                'text-[11px] font-black uppercase tracking-wider text-slate-400',
                        },
                        props.label
                    ),

                    props.link
                        ? h(
                              'a',
                              {
                                  href: props.link,
                                  target: '_blank',
                                  rel: 'noopener noreferrer',
                                  class:
                                      'mt-2 block break-words text-sm font-bold text-indigo-600 hover:underline dark:text-indigo-400',
                              },
                              props.value || 'N/A'
                          )
                        : h(
                              'dd',
                              {
                                  class:
                                      'mt-2 whitespace-pre-wrap break-words text-sm font-bold leading-6 text-slate-800 dark:text-slate-200',
                              },
                              props.value || 'N/A'
                          ),
                ]
            );
    },
});

const SensitiveItem = defineComponent({
    name: 'SensitiveItem',

    props: {
        label: {
            type: String,
            required: true,
        },

        value: {
            type: String,
            default: '',
        },

        revealed: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['toggle'],

    setup(props, { emit }) {
        const masked = computed(() => {
            const value = String(props.value || '');

            if (!value) {
                return 'N/A';
            }

            if (value.length <= 4) {
                return '••••';
            }

            return `${value.slice(0, 3)}${'•'.repeat(
                Math.max(value.length - 5, 4)
            )}${value.slice(-2)}`;
        });

        return () =>
            h(
                'div',
                {
                    class:
                        'rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/60',
                },
                [
                    h(
                        'p',
                        {
                            class:
                                'text-[11px] font-black uppercase tracking-wider text-slate-400',
                        },
                        props.label
                    ),

                    h(
                        'div',
                        {
                            class:
                                'mt-3 flex items-center justify-between gap-4',
                        },
                        [
                            h(
                                'p',
                                {
                                    class:
                                        'font-mono text-base font-black tracking-wider',
                                },
                                props.revealed
                                    ? props.value || 'N/A'
                                    : masked.value
                            ),

                            h(
                                'button',
                                {
                                    type: 'button',
                                    class:
                                        'rounded-xl bg-white px-3 py-2 text-xs font-black text-indigo-600 shadow-sm dark:bg-slate-900 dark:text-indigo-400',
                                    onClick: () => emit('toggle'),
                                },
                                props.revealed ? 'Hide' : 'Show'
                            ),
                        ]
                    ),
                ]
            );
    },
});

export default {
    name: 'BusinessActivations',

    components: {
        ReviewSection,
        InfoGrid,
        InfoItem,
        SensitiveItem,
        DocumentCard,
    },

    setup() {
        const loading = ref(false);
        const reviewLoading = ref(false);
        const processing = ref(false);

        const activations = ref([]);
        const selectedActivation = ref({});

        const reviewOpen = ref(false);
        const rejectFormOpen = ref(false);

        const showNin = ref(false);
        const showBvn = ref(false);

        const rejectionReason = ref('');
        const rejectionError = ref('');

        const successMessage = ref('');
        const errorMessage = ref('');

        const filters = ref({
            search: '',
            status: '',
            business_type: '',
            period: '',
        });

        const pagination = ref({
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0,
        });

        const statusCounts = ref({
            all: 0,
            pending: 0,
            under_review: 0,
            approved: 0,
            rejected: 0,
        });

        const statusOptions = [
            {
                value: '',
                label: 'All',
            },
            {
                value: 'pending',
                label: 'Pending',
            },
            {
                value: 'under_review',
                label: 'Under Review',
            },
            {
                value: 'approved',
                label: 'Approved',
            },
            {
                value: 'rejected',
                label: 'Rejected',
            },
        ];

        const summaries = computed(() => [
            {
                label: 'All Applications',
                value: statusCounts.value.all,
                icon: '▤',
                iconClass:
                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
            },
            {
                label: 'Awaiting Review',
                value:
                    statusCounts.value.pending +
                    statusCounts.value.under_review,
                icon: '◷',
                iconClass:
                    'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
            },
            {
                label: 'Approved',
                value: statusCounts.value.approved,
                icon: '✓',
                iconClass:
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            },
            {
                label: 'Rejected',
                value: statusCounts.value.rejected,
                icon: '✕',
                iconClass:
                    'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
            },
        ]);

        const formatStatus = (status) => {
            const statuses = {
                pending: 'Pending',
                under_review: 'Under Review',
                approved: 'Approved',
                rejected: 'Rejected',
            };

            return statuses[status] || status || 'Pending';
        };

        const statusClass = (status) => {
            const classes = {
                pending:
                    'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',

                under_review:
                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',

                approved:
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',

                rejected:
                    'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
            };

            return (
                classes[status] ||
                'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
            );
        };

        const formatBusinessType = (type) => {
            return type === 'registered'
                ? 'Registered Business'
                : 'Starter Business';
        };

        const formatDate = (value) => {
            if (!value) {
                return 'N/A';
            }

            return new Date(value).toLocaleDateString('en-NG', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            });
        };

        const formatDateTime = (value) => {
            if (!value) {
                return 'N/A';
            }

            return new Date(value).toLocaleString('en-NG', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        };

        const ownerName = (activation) => {
            if (activation.business_type === 'starter') {
                const personalName = [
                    activation.first_name,
                    activation.middle_name,
                    activation.last_name,
                ]
                    .filter(Boolean)
                    .join(' ')
                    .trim();

                if (personalName) {
                    return personalName;
                }
            }

            return (
                activation.user?.name ||
                activation.merchant?.user?.name ||
                'Not provided'
            );
        };

        const buildParams = (page = 1) => {
            const params = {
                page,
                per_page: 12,
            };

            Object.entries(filters.value).forEach(
                ([key, value]) => {
                    if (
                        value !== null &&
                        value !== undefined &&
                        String(value).trim() !== ''
                    ) {
                        params[key] = String(value).trim();
                    }
                }
            );

            return params;
        };

        const fetchActivations = async (page = 1) => {
            loading.value = true;
            errorMessage.value = '';
            successMessage.value = '';

            try {
                const response = await axios.get(
                    '/api/v1/admin/business-activations',
                    {
                        params: buildParams(page),
                    }
                );

                const paginator = response.data?.data || {};

                activations.value = Array.isArray(paginator.data)
                    ? paginator.data
                    : [];

                pagination.value = {
                    current_page:
                        Number(paginator.current_page || 1),

                    last_page:
                        Number(paginator.last_page || 1),

                    from:
                        Number(paginator.from || 0),

                    to:
                        Number(paginator.to || 0),

                    total:
                        Number(paginator.total || 0),
                };
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The activation applications could not be loaded.';

                activations.value = [];
            } finally {
                loading.value = false;
            }
        };

        const fetchStatusCounts = async () => {
            const requests = await Promise.allSettled([
                axios.get(
                    '/api/v1/admin/business-activations',
                    {
                        params: {
                            per_page: 6,
                        },
                    }
                ),

                axios.get(
                    '/api/v1/admin/business-activations',
                    {
                        params: {
                            status: 'pending',
                            per_page: 6,
                        },
                    }
                ),

                axios.get(
                    '/api/v1/admin/business-activations',
                    {
                        params: {
                            status: 'under_review',
                            per_page: 6,
                        },
                    }
                ),

                axios.get(
                    '/api/v1/admin/business-activations',
                    {
                        params: {
                            status: 'approved',
                            per_page: 6,
                        },
                    }
                ),

                axios.get(
                    '/api/v1/admin/business-activations',
                    {
                        params: {
                            status: 'rejected',
                            per_page: 6,
                        },
                    }
                ),
            ]);

            const totalFrom = (result) => {
                if (result.status !== 'fulfilled') {
                    return 0;
                }

                return Number(
                    result.value.data?.data?.total || 0
                );
            };

            statusCounts.value = {
                all: totalFrom(requests[0]),
                pending: totalFrom(requests[1]),
                under_review: totalFrom(requests[2]),
                approved: totalFrom(requests[3]),
                rejected: totalFrom(requests[4]),
            };
        };

        const applyFilters = async () => {
            await fetchActivations(1);
        };

        const setStatus = async (status) => {
            filters.value.status = status;
            await fetchActivations(1);
        };

        const resetFilters = async () => {
            filters.value = {
                search: '',
                status: '',
                business_type: '',
                period: '',
            };

            await fetchActivations(1);
        };

        const openReview = async (activation) => {
            reviewOpen.value = true;
            reviewLoading.value = true;

            selectedActivation.value = activation;

            rejectFormOpen.value = false;
            rejectionReason.value = '';
            rejectionError.value = '';

            showNin.value = false;
            showBvn.value = false;

            document.body.style.overflow = 'hidden';

            try {
                const response = await axios.get(
                    `/api/v1/admin/business-activations/${activation.id}`
                );

                selectedActivation.value =
                    response.data?.data || activation;
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The complete application could not be loaded.';
            } finally {
                reviewLoading.value = false;
            }
        };

        const closeReview = () => {
            if (processing.value) {
                return;
            }

            reviewOpen.value = false;
            selectedActivation.value = {};

            rejectFormOpen.value = false;
            rejectionReason.value = '';
            rejectionError.value = '';

            showNin.value = false;
            showBvn.value = false;

            document.body.style.overflow = '';
        };

        const approveApplication = async () => {
            if (
                !selectedActivation.value?.id ||
                processing.value
            ) {
                return;
            }

            const confirmed = await verificationModal.confirm({
                type: 'info',
                title: 'Approve business activation?',
                message: `Approve ${selectedActivation.value.business_name}?`,
                confirmText: 'Approve business',
            });

            if (!confirmed) {
                return;
            }

            processing.value = true;
            errorMessage.value = '';
            successMessage.value = '';

            try {
                const response = await axios.post(
                    `/api/v1/admin/business-activations/${selectedActivation.value.id}/approve`
                );

                successMessage.value =
                    response.data?.message ||
                    'Business approved successfully.';

                selectedActivation.value =
                    response.data?.data ||
                    selectedActivation.value;

                processing.value = false;
                closeReview();

                await Promise.all([
                    fetchActivations(
                        pagination.value.current_page
                    ),
                    fetchStatusCounts(),
                ]);
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The business could not be approved.';
            } finally {
                processing.value = false;
            }
        };

        const cancelReject = () => {
            rejectFormOpen.value = false;
            rejectionReason.value = '';
            rejectionError.value = '';
        };

        const submitRejection = async () => {
            const reason = rejectionReason.value.trim();

            rejectionError.value = '';

            if (reason.length < 5) {
                rejectionError.value =
                    'Please enter a clear rejection reason containing at least 5 characters.';

                return;
            }

            processing.value = true;
            errorMessage.value = '';
            successMessage.value = '';

            try {
                const response = await axios.post(
                    `/api/v1/admin/business-activations/${selectedActivation.value.id}/reject`,
                    {
                        reason,
                    }
                );

                successMessage.value =
                    response.data?.message ||
                    'Business activation rejected successfully.';

                processing.value = false;
                closeReview();

                await Promise.all([
                    fetchActivations(
                        pagination.value.current_page
                    ),
                    fetchStatusCounts(),
                ]);
            } catch (error) {
                rejectionError.value =
                    error.response?.data?.errors?.reason?.[0] ||
                    error.response?.data?.message ||
                    'The application could not be rejected.';
            } finally {
                processing.value = false;
            }
        };

        const handleKeydown = (event) => {
            if (
                event.key === 'Escape' &&
                reviewOpen.value &&
                !processing.value
            ) {
                closeReview();
            }
        };

        onMounted(async () => {
            window.addEventListener(
                'keydown',
                handleKeydown
            );

            await Promise.all([
                fetchActivations(),
                fetchStatusCounts(),
            ]);
        });

        onBeforeUnmount(() => {
            window.removeEventListener(
                'keydown',
                handleKeydown
            );

            document.body.style.overflow = '';
        });

        return {
            loading,
            reviewLoading,
            processing,
            activations,
            selectedActivation,
            reviewOpen,
            rejectFormOpen,
            showNin,
            showBvn,
            rejectionReason,
            rejectionError,
            successMessage,
            errorMessage,
            filters,
            pagination,
            statusOptions,
            summaries,
            formatStatus,
            statusClass,
            formatBusinessType,
            formatDate,
            formatDateTime,
            ownerName,
            fetchActivations,
            applyFilters,
            setStatus,
            resetFilters,
            openReview,
            closeReview,
            approveApplication,
            cancelReject,
            submitRejection,
        };
    },
};
</script>
