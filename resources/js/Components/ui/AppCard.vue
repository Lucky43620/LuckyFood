<script setup>
defineProps({
  padding: { type: String, default: 'md' }, // sm | md | lg | none
  shadow:  { type: String, default: 'sm' }, // sm | md | none
  rounded: { type: String, default: 'lg' }, // md | lg | xl
})
</script>

<template>
  <div
    class="bg-white"
    :class="[
      padding === 'sm'   && 'p-3',
      padding === 'md'   && 'p-4',
      padding === 'lg'   && 'p-6',
      padding === 'none' && '',

      shadow === 'sm'   && 'shadow-sm',
      shadow === 'md'   && 'shadow-md',
      shadow === 'none' && '',

      rounded === 'md' && 'rounded-md',
      rounded === 'lg' && 'rounded-lg',
      rounded === 'xl' && 'rounded-xl',
    ]"
  >
    <slot />
  </div>
</template>
