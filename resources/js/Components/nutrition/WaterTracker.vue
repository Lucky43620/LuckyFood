<script setup>
import { Droplets } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: Number, required: true },
  goal:       { type: Number, default: 8 },
})
const emit = defineEmits(['update:modelValue'])

const setGlasses = (n) => emit('update:modelValue', n === props.modelValue ? n - 1 : n)
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm p-4">
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center gap-2">
        <Droplets :size="16" class="text-blue-500" />
        <span class="text-[13px] font-bold text-neutral-800">Eau</span>
      </div>
      <span class="font-mono text-xs text-neutral-400">{{ modelValue }}/{{ goal }} verres</span>
    </div>

    <div class="flex gap-1.5">
      <button
        v-for="i in goal"
        :key="i"
        class="flex-1 h-7 rounded-[6px] transition-colors duration-200"
        :class="i <= modelValue ? 'bg-blue-500' : 'bg-blue-100'"
        :title="`${i} verre${i > 1 ? 's' : ''}`"
        @click="setGlasses(i)"
      />
    </div>
  </div>
</template>
