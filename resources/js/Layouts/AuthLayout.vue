<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Leaf, CheckCircle2 } from 'lucide-vue-next'

defineProps({
  title: { type: String, default: 'LuckyFood' },
})

const FEATURES = [
  'Suivi des calories & macros en temps réel',
  'Base de données FatSecret (4M+ aliments)',
  'Recettes personnalisées avec calcul nutritionnel',
  'Graphiques de progression hebdomadaire',
]
</script>

<template>
  <div class="min-h-screen flex bg-neutral-25">
    <Head :title="title" />

    <!-- ── Left branding panel (lg+) ─────────────────────────────────────── -->
    <div class="hidden lg:flex lg:w-[480px] xl:w-[520px] shrink-0 flex-col justify-between bg-gradient-to-br from-green-600 to-green-800 p-12 text-white">
      <!-- Logo -->
      <Link :href="route('home')" class="flex items-center gap-2.5 w-fit">
        <div class="w-9 h-9 bg-white/20 rounded-lg flex items-center justify-center">
          <Leaf :size="18" class="text-white" />
        </div>
        <span class="font-display text-xl text-white">LuckyFood</span>
      </Link>

      <!-- Headline -->
      <div>
        <h1 class="font-display text-[38px] leading-tight mb-4">
          Mangez mieux,<br>vivez mieux.
        </h1>
        <p class="text-green-100 text-sm leading-relaxed mb-8">
          Suivez votre alimentation, atteignez vos objectifs nutritionnels et créez vos recettes personnalisées.
        </p>

        <ul class="flex flex-col gap-3">
          <li
            v-for="feat in FEATURES"
            :key="feat"
            class="flex items-start gap-2.5 text-sm text-green-50"
          >
            <CheckCircle2 :size="16" class="text-green-300 shrink-0 mt-0.5" />
            {{ feat }}
          </li>
        </ul>
      </div>

      <!-- Footer -->
      <p class="text-green-300 text-xs">© {{ new Date().getFullYear() }} LuckyFood · Propulsé par FatSecret</p>
    </div>

    <!-- ── Right form panel ───────────────────────────────────────────────── -->
    <div class="flex-1 flex flex-col items-center justify-center p-6 sm:p-10">

      <!-- Mobile logo (hidden on lg) -->
      <Link :href="route('home')" class="flex items-center gap-2 mb-8 lg:hidden">
        <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
          <Leaf :size="16" class="text-white" />
        </div>
        <span class="font-display text-lg text-neutral-900">LuckyFood</span>
      </Link>

      <!-- Card -->
      <div class="w-full max-w-sm">
        <slot />
      </div>
    </div>
  </div>
</template>
