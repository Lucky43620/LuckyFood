<script setup>
defineProps({
  variant:  { type: String, default: 'primary' }, // primary | secondary | ghost | danger
  size:     { type: String, default: 'md' },       // sm | md | lg
  as:       { type: String, default: 'button' },
  disabled: { type: Boolean, default: false },
  loading:  { type: Boolean, default: false },
})
</script>

<template>
  <component
    :is="as"
    class="inline-flex items-center justify-center gap-1.5 font-sans font-semibold transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed select-none"
    :class="[
      size === 'sm' && 'h-8 px-3 text-xs rounded-md',
      size === 'md' && 'h-10 px-5 text-sm rounded-pill',
      size === 'lg' && 'h-12 px-6 text-base rounded-pill',

      variant === 'primary'   && 'bg-green-500 text-white hover:bg-green-600 shadow-sm hover:-translate-y-px active:translate-y-0',
      variant === 'secondary' && 'bg-green-50 text-green-600 hover:bg-green-100',
      variant === 'ghost'     && 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800',
      variant === 'danger'    && 'bg-red-100 text-red-500 hover:bg-red-500 hover:text-white',
    ]"
    :disabled="disabled || loading"
    v-bind="$attrs"
  >
    <svg v-if="loading" class="animate-spin -ml-1 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
    </svg>
    <slot />
  </component>
</template>
