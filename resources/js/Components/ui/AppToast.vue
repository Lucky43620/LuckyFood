<script setup>
defineProps({
    message: { type: String, default: null },
})
</script>

<template>
    <Transition name="toast">
        <div
            v-if="message"
            class="fixed bottom-20 left-1/2 z-50 -translate-x-1/2 whitespace-nowrap rounded-pill bg-neutral-900 px-5 py-2.5 text-sm font-medium text-white shadow-xl md:bottom-6"
            role="status"
            aria-live="polite"
        >
            {{ message }}
        </div>
    </Transition>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.25s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateX(-50%) translateY(10px);
}
</style>
