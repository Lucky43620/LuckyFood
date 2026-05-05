<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Leaf, Flame, ChefHat, TrendingUp, Droplets, ArrowRight, CheckCircle2 } from 'lucide-vue-next'

defineProps({
  canLogin:    Boolean,
  canRegister: Boolean,
})

const FEATURES = [
  {
    icon: Flame,
    color: 'bg-orange-50 text-orange-500',
    title: 'Suivi calorique',
    desc: 'Anneau visuel en temps réel, macros détaillés, objectifs personnalisés.',
  },
  {
    icon: ChefHat,
    color: 'bg-green-50 text-green-600',
    title: 'Recettes personnalisées',
    desc: 'Créez vos recettes, calculez les apports par portion automatiquement.',
  },
  {
    icon: TrendingUp,
    color: 'bg-blue-50 text-blue-500',
    title: 'Progression',
    desc: 'Graphiques hebdomadaires calories & poids, tableaux de suivi détaillés.',
  },
  {
    icon: Droplets,
    color: 'bg-sky-50 text-sky-500',
    title: 'Hydratation',
    desc: 'Tracker d\'eau intégré au tableau de bord pour rester bien hydraté.',
  },
]

const BENEFITS = [
  '4 millions d\'aliments via FatSecret',
  'Protéines, glucides, lipides, fibres',
  'Journal alimentaire par repas',
  'Interface 100% en français',
]
</script>

<template>
  <Head title="LuckyFood — Suivi nutritionnel" />

  <div class="min-h-screen bg-neutral-25 font-sans">

    <!-- ── Navbar ──────────────────────────────────────────────────────────── -->
    <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur border-b border-neutral-100">
      <div class="max-w-5xl mx-auto px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
            <Leaf :size="16" class="text-white" />
          </div>
          <span class="font-display text-lg text-neutral-900">LuckyFood</span>
        </div>

        <div v-if="canLogin" class="flex items-center gap-3">
          <Link
            :href="route('login')"
            class="text-sm font-semibold text-neutral-600 hover:text-neutral-900 transition-colors"
          >
            Connexion
          </Link>
          <Link
            v-if="canRegister"
            :href="route('register')"
            class="h-9 px-4 bg-green-500 hover:bg-green-600 text-white text-sm font-semibold rounded-pill transition-colors inline-flex items-center"
          >
            S'inscrire gratuitement
          </Link>
        </div>
      </div>
    </nav>

    <!-- ── Hero ────────────────────────────────────────────────────────────── -->
    <section class="max-w-5xl mx-auto px-6 pt-20 pb-16 text-center">
      <div class="inline-flex items-center gap-2 bg-green-50 text-green-700 text-xs font-semibold px-3 py-1.5 rounded-pill mb-6 border border-green-100">
        <Leaf :size="12" /> Propulsé par FatSecret API
      </div>

      <h1 class="font-display text-[48px] sm:text-[64px] leading-[1.05] tracking-tight text-neutral-900 mb-6">
        Mangez mieux,<br>
        <span class="text-green-500">vivez mieux.</span>
      </h1>

      <p class="text-lg text-neutral-500 max-w-xl mx-auto mb-10 leading-relaxed">
        Suivez vos calories, vos macros et votre hydratation au quotidien.
        Créez vos recettes. Visualisez votre progression.
      </p>

      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <Link
          v-if="canRegister"
          :href="route('register')"
          class="inline-flex items-center justify-center gap-2 h-12 px-8 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-pill transition-all hover:-translate-y-px shadow-sm text-sm"
        >
          Commencer gratuitement <ArrowRight :size="16" />
        </Link>
        <Link
          v-if="canLogin"
          :href="route('login')"
          class="inline-flex items-center justify-center h-12 px-8 bg-white border border-neutral-200 text-neutral-700 font-semibold rounded-pill hover:border-neutral-300 transition-colors text-sm"
        >
          Se connecter
        </Link>
      </div>
    </section>

    <!-- ── Benefits strip ──────────────────────────────────────────────────── -->
    <section class="border-y border-neutral-100 bg-white py-5">
      <div class="max-w-5xl mx-auto px-6">
        <ul class="flex flex-wrap items-center justify-center gap-x-8 gap-y-2">
          <li
            v-for="b in BENEFITS"
            :key="b"
            class="flex items-center gap-2 text-sm text-neutral-600"
          >
            <CheckCircle2 :size="14" class="text-green-500 shrink-0" />
            {{ b }}
          </li>
        </ul>
      </div>
    </section>

    <!-- ── Features grid ───────────────────────────────────────────────────── -->
    <section class="max-w-5xl mx-auto px-6 py-20">
      <div class="text-center mb-12">
        <p class="text-xs font-semibold tracking-widest uppercase text-neutral-400 mb-3">Fonctionnalités</p>
        <h2 class="font-display text-[36px] leading-tight text-neutral-900">Tout ce dont vous avez besoin</h2>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div
          v-for="feat in FEATURES"
          :key="feat.title"
          class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow"
        >
          <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-4" :class="feat.color">
            <component :is="feat.icon" :size="20" />
          </div>
          <h3 class="font-semibold text-neutral-900 mb-2">{{ feat.title }}</h3>
          <p class="text-sm text-neutral-500 leading-relaxed">{{ feat.desc }}</p>
        </div>
      </div>
    </section>

    <!-- ── CTA bottom ──────────────────────────────────────────────────────── -->
    <section class="bg-green-500 py-16 text-center text-white">
      <h2 class="font-display text-[32px] leading-tight mb-4">Prêt à commencer ?</h2>
      <p class="text-green-100 text-sm mb-8">Créez votre compte gratuit en moins de 30 secondes.</p>
      <Link
        v-if="canRegister"
        :href="route('register')"
        class="inline-flex items-center gap-2 h-12 px-8 bg-white text-green-700 font-semibold rounded-pill hover:bg-green-50 transition-colors text-sm"
      >
        Créer mon compte <ArrowRight :size="16" />
      </Link>
    </section>

    <!-- ── Footer ──────────────────────────────────────────────────────────── -->
    <footer class="bg-white border-t border-neutral-100 py-8 text-center text-xs text-neutral-400">
      © {{ new Date().getFullYear() }} LuckyFood — Tous droits réservés
    </footer>

  </div>
</template>
