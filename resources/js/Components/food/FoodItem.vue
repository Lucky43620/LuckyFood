<script setup>
import { X } from 'lucide-vue-next'

defineProps({
    name: { type: String, required: true },
    serving: { type: String, default: '' },
    kcal: { type: Number, required: true },
    last: { type: Boolean, default: false },
})

defineEmits(['remove'])
</script>

<template>
    <div class="group flex items-center gap-2.5 px-4 py-2.5" :class="!last && 'border-b border-neutral-50'">
        <div class="min-w-0 flex-1">
            <p class="truncate text-[13px] font-semibold text-neutral-800">{{ name }}</p>
            <p v-if="serving" class="mt-0.5 text-[11px] text-neutral-400">{{ serving }}</p>
        </div>

        <div class="flex shrink-0 items-center gap-1.5">
            <span class="font-mono text-[13px] font-semibold text-neutral-700">{{ kcal }}</span>
            <span class="text-[11px] text-neutral-400">kcal</span>
            <button
                @click="$emit('remove')"
                class="ml-1 p-0.5 text-neutral-300 opacity-0 transition-colors hover:text-red-500 group-hover:opacity-100"
                title="Supprimer"
                :aria-label="`Supprimer ${name}`"
            >
                <X :size="13" />
            </button>
        </div>
    </div>
</template>
