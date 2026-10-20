<template>
    <div id="app">
        <router-view />
        <VerificationModal />
        <notifications position="top right" />
    </div>
</template>

<script>
import VerificationModal from './components/VerificationModal.vue';

export default {
    name: 'App',
    components: { VerificationModal },
};
</script>

<style>
#app {
    min-height: 100vh;
}
</style>
