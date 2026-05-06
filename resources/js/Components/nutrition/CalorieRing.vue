<script setup>
import { computed } from 'vue'

const props = defineProps({
    eaten: { type: Number, required: true },
    goal: { type: Number, required: true },
    burned: { type: Number, default: 0 },
})

const R = 64
const CIRC = 2 * Math.PI * R // ≈ 402.12

const pct = computed(() => Math.min(props.eaten / Math.max(props.goal, 1), 1))
const offset = computed(() => CIRC * (1 - pct.value))

// Amber warning overlay when > 85% consumed
const overPct = computed(() => Math.max(pct.value - 0.85, 0) / 0.15)
const overDash = computed(() => CIRC * overPct.value)
const overOffset = computed(() => CIRC * (1 - overPct.value))
</script>

<template>
    <div class="relative mx-auto h-40 w-40">
        <svg width="160" height="160" viewBox="0 0 160 160" class="-rotate-90" aria-hidden="true">
            <!-- Track -->
            <circle cx="80" cy="80" :r="R" fill="none" stroke-width="14" class="stroke-neutral-100" />
            <!-- Progress -->
            <circle
                cx="80"
                cy="80"
                :r="R"
                fill="none"
                stroke-width="14"
                stroke-linecap="round"
                class="stroke-green-500 transition-all duration-700"
                style="transition-timing-function: cubic-bezier(0.22, 1, 0.36, 1)"
                :stroke-dasharray="CIRC"
                :stroke-dashoffset="offset"
            />
            <!-- Overflow warning (> 85%) -->
            <circle
                v-if="overPct > 0"
                cx="80"
                cy="80"
                :r="R"
                fill="none"
                stroke-width="14"
                stroke-linecap="round"
                opacity="0.75"
                class="stroke-amber-500"
                :stroke-dasharray="overDash"
                :stroke-dashoffset="overOffset"
            />
        </svg>

        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
            <span class="font-display text-4xl leading-none text-neutral-900">
                {{ eaten.toLocaleString('fr-FR') }}
            </span>
            <span class="mt-1.5 text-[11px] font-medium text-neutral-400">kcal consommés</span>
        </div>
    </div>
</template>
