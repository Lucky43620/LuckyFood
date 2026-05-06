<script setup>
import { MEALS } from '@/constants/nutrition'

defineProps({
    modelValue: { type: String, default: 'breakfast' },
    label: { type: String, default: 'Ajouter au repas' },
})

defineEmits(['update:modelValue'])
</script>

<template>
    <div>
        <p class="mb-2 text-xs font-semibold text-neutral-500">{{ label }}</p>
        <div class="grid grid-cols-4 gap-2" role="group" :aria-label="label">
            <button
                v-for="meal in MEALS"
                :key="meal.key"
                type="button"
                class="h-9 truncate rounded-pill px-1 text-[11px] font-semibold transition-all duration-150"
                :class="
                    modelValue === meal.key
                        ? 'scale-[0.98] bg-green-500 text-white shadow-sm'
                        : 'border border-neutral-200 bg-white text-neutral-600 hover:border-green-300 hover:text-green-600'
                "
                :aria-pressed="modelValue === meal.key"
                @click="$emit('update:modelValue', meal.key)"
            >
                {{ meal.label }}
            </button>
        </div>
    </div>
</template>
