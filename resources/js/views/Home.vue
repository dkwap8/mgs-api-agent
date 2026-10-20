<template>
    <div class="min-h-screen bg-gradient-to-r from-indigo-50 to-indigo-50">
        <nav class="bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)]">
            <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-bold text-indigo-600">MGS API</h1>
                <div class="space-x-4">
                    <router-link :to="{ name: 'login' }" class="text-slate-600 hover:text-indigo-600">Login</router-link>
                    <router-link :to="{ name: 'register' }" class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700">
                        Get Started
                    </router-link>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 py-20">
            <div class="text-center">
                <h1 class="text-5xl font-bold text-slate-950 mb-6">
                    Welcome to <span class="text-indigo-600">MGS API</span> Platform
                </h1>
                <p class="text-xl text-slate-600 mb-8 max-w-2xl mx-auto">
                    Virtual Account API, Collections, Wallets, Webhooks and Developer Platform
                </p>
                <div class="space-x-4">
                    <router-link :to="{ name: 'register' }" class="px-8 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 font-medium">
                        Get Started →
                    </router-link>
                    <router-link :to="{ name: 'login' }" class="px-8 py-3 bg-slate-200 text-slate-700 rounded-xl hover:bg-gray-300 font-medium">
                        Sign In
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Home'
};
</script>