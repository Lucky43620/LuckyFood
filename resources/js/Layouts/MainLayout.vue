<script setup>
import { Head } from '@inertiajs/vue3'
import SideNav   from '@/Components/nav/SideNav.vue'
import BottomNav from '@/Components/nav/BottomNav.vue'

defineProps({
  title: { type: String, default: 'LuckyFood' },
})
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-neutral-25">
    <Head :title="title" />

    <!-- Sidebar desktop -->
    <SideNav />

    <!-- Main scrollable content -->
    <main class="flex-1 overflow-y-auto pb-16 md:pb-0">
      <slot />
    </main>

    <!-- Bottom nav mobile -->
    <BottomNav />
  </div>
</template>
