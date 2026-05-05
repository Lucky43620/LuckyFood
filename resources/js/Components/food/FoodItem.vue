<script setup>
import { X } from 'lucide-vue-next'

defineProps({
  name:    { type: String, required: true },
  serving: { type: String, default: '' },
  kcal:    { type: Number, required: true },
  last:    { type: Boolean, default: false },
})

defineEmits(['remove'])
</script>

<template>
  <div
    class="flex items-center gap-2.5 px-4 py-2.5 group"
    :class="!last && 'border-b border-neutral-50'"
  >
    <div class="flex-1 min-w-0">
      <p class="text-[13px] font-semibold text-neutral-800 truncate">{{ name }}</p>
      <p v-if="serving" class="text-[11px] text-neutral-400 mt-0.5">{{ serving }}</p>
    </div>

    <div class="flex items-center gap-1.5 shrink-0">
      <span class="font-mono text-[13px] font-semibold text-neutral-700">{{ kcal }}</span>
      <span class="text-[11px] text-neutral-400">kcal</span>
      <button
        @click="$emit('remove')"
        class="text-neutral-300 hover:text-red-500 p-0.5 transition-colors ml-1 opacity-0 group-hover:opacity-100"
        title="Supprimer"
      >
        <X :size="13" />
      </button>
    </div>
  </div>
</template>
