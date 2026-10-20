<template>
    <div class="bg-white p-6 rounded-xl shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">{{ title }}</p>
                <p class="text-2xl font-bold">{{ value }}</p>
            </div>
            <div :class="`text-${color}-500 text-3xl`">
                {{ icon }}
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        title: String,
        value: [String, Number],
        icon: String,
        color: {
            type: String,
            default: 'blue'
        }
    }
};
</script>