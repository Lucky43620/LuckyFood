<script setup>
import { Link } from '@inertiajs/vue3'
import { Pencil, X } from 'lucide-vue-next'

defineProps({
    name: { type: String, required: true },
    serving: { type: String, default: '' },
    kcal: { type: Number, required: true },
    protein: { type: Number, default: null },
    carbs: { type: Number, default: null },
    fat: { type: Number, default: null },
    href: { type: String, default: null },
    canEdit: { type: Boolean, default: false },
    last: { type: Boolean, default: false },
})

defineEmits(['edit', 'remove'])
</script>

<template>
    <div class="group flex items-center gap-2.5 px-4 py-2.5" :class="!last && 'border-b border-neutral-50'">
        <div class="min-w-0 flex-1">
            <component
                :is="href ? Link : 'span'"
                :href="href ?? undefined"
                class="truncate text-[13px] font-semibold text-neutral-800"
                :class="href && 'transition-colors hover:text-green-600'"
            >
                {{ name }}
            </component>
            <p class="mt-0.5 flex flex-wrap items-center gap-x-2 text-[11px] text-neutral-400">
                <span v-if="serving">{{ serving }}</span>
                <span v-if="protein !== null || carbs !== null || fat !== null" class="flex items-center gap-1.5">
                    <span v-if="serving" class="text-neutral-200">·</span>
                    <span v-if="protein !== null" class="text-green-500">P {{ protein }}g</span>
                    <span v-if="carbs !== null" class="text-amber-500">G {{ carbs }}g</span>
                    <span v-if="fat !== null" class="text-orange-400">L {{ fat }}g</span>
                </span>
            </p>
        </div>

        <div class="flex shrink-0 items-center gap-1.5">
            <span class="font-mono text-[13px] font-semibold text-neutral-700">{{ kcal }}</span>
            <span class="text-[11px] text-neutral-400">kcal</span>
            <button
                v-if="canEdit"
                @click="$emit('edit')"
                class="ml-1 p-0.5 text-neutral-300 opacity-0 transition-colors hover:text-green-600 group-hover:opacity-100"
                title="Modifier"
                :aria-label="`Modifier ${name}`"
            >
                <Pencil :size="13" />
            </button>
            <button
                @click="$emit('remove')"
                class="p-0.5 text-neutral-300 opacity-0 transition-colors hover:text-red-500 group-hover:opacity-100"
                title="Supprimer"
                :aria-label="`Supprimer ${name}`"
            >
                <X :size="13" />
            </button>
        </div>
    </div>
</template>
