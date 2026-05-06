<script setup>
defineProps({
    color: { type: String, default: 'neutral' }, // green | orange | amber | blue | coral | neutral
})
</script>

<template>
    <span
        class="inline-flex items-center rounded-pill px-2.5 py-1 text-xs font-semibold"
        :class="{
            'bg-green-50 text-green-600': color === 'green',
            'bg-orange-50 text-orange-500': color === 'orange',
            'bg-amber-100 text-amber-700': color === 'amber',
            'bg-blue-100 text-blue-500': color === 'blue',
            'bg-coral-100 text-coral-500': color === 'coral',
            'bg-neutral-100 text-neutral-600': color === 'neutral',
        }"
    >
        <slot />
    </span>
</template>
