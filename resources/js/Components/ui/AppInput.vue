<script setup>
defineProps({
  modelValue:  { type: [String, Number], default: '' },
  label:       { type: String, default: null },
  placeholder: { type: String, default: '' },
  error:       { type: String, default: null },
  type:        { type: String, default: 'text' },
  icon:        { type: [Object, Function], default: null },    // lucide component
  iconRight:   { type: [Object, Function], default: null },
  disabled:    { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" class="text-sm font-semibold text-neutral-700">{{ label }}</label>

    <div class="relative">
      <!-- Left icon -->
      <span v-if="icon" class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none">
        <component :is="icon" :size="16" />
      </span>

      <input
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        @input="$emit('update:modelValue', $event.target.value)"
        class="w-full h-11 bg-white border rounded-md text-[14px] text-neutral-800 placeholder-neutral-400 transition-all duration-150 focus:outline-none focus:ring-2 disabled:opacity-50 disabled:cursor-not-allowed"
        :class="[
          icon      ? 'pl-9'  : 'pl-4',
          iconRight ? 'pr-9'  : 'pr-4',
          error
            ? 'border-red-400 focus:ring-red-400/20 focus:border-red-400'
            : 'border-neutral-200 focus:ring-green-500/20 focus:border-green-400',
        ]"
      />

      <!-- Right icon -->
      <span v-if="iconRight" class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400">
        <component :is="iconRight" :size="16" />
      </span>
    </div>

    <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
  </div>
</template>
