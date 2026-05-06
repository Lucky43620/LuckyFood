<script setup>
import { computed } from 'vue'

const props = defineProps({
    label: { type: String, required: true },
    value: { type: Number, required: true },
    goal: { type: Number, required: true },
    color: { type: String, default: 'green' }, // green | amber | coral | blue
    unit: { type: String, default: 'g' },
})

const pct = computed(() => Math.min((props.value / Math.max(props.goal, 1)) * 100, 100))

const COLORS = {
    green: { label: 'text-green-600', bar: 'bg-green-500' },
    amber: { label: 'text-amber-600', bar: 'bg-amber-500' },
    coral: { label: 'text-coral-500', bar: 'bg-coral-500' },
    blue: { label: 'text-blue-500', bar: 'bg-blue-500' },
}
const scheme = computed(() => COLORS[props.color] ?? COLORS.green)
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <div class="flex items-center justify-between">
            <span class="text-[13px] font-semibold" :class="scheme.label">{{ label }}</span>
            <span class="font-mono text-xs text-neutral-500">
                {{ value }}<span class="text-neutral-300"> / {{ goal }}{{ unit }}</span>
            </span>
        </div>
        <div class="h-[7px] overflow-hidden rounded-full bg-neutral-100">
            <div
                class="h-full rounded-full transition-all duration-500"
                style="transition-timing-function: cubic-bezier(0.22, 1, 0.36, 1)"
                :class="scheme.bar"
                :style="{ width: `${pct}%` }"
            />
        </div>
    </div>
</template>
