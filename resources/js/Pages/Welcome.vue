<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Leaf, Flame, ChefHat, TrendingUp, Droplets, ArrowRight, CheckCircle2 } from 'lucide-vue-next'

defineProps({
    canLogin: Boolean,
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
        desc: "Tracker d'eau intégré au tableau de bord pour rester bien hydraté.",
    },
]

const BENEFITS = [
    "4 millions d'aliments via FatSecret",
    'Protéines, glucides, lipides, fibres',
    'Journal alimentaire par repas',
    'Interface 100% en français',
]
</script>

<template>
    <Head title="LuckyFood — Suivi nutritionnel" />

    <div class="min-h-screen bg-neutral-25 font-sans">
        <!-- ── Navbar ──────────────────────────────────────────────────────────── -->
        <nav class="sticky top-0 z-50 border-b border-neutral-100 bg-white/80 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-6">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-500">
                        <Leaf :size="16" class="text-white" />
                    </div>
                    <span class="font-display text-lg text-neutral-900">LuckyFood</span>
                </div>

                <div v-if="canLogin" class="flex items-center gap-3">
                    <Link
                        :href="route('login')"
                        class="text-sm font-semibold text-neutral-600 transition-colors hover:text-neutral-900"
                    >
                        Connexion
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="inline-flex h-9 items-center rounded-pill bg-green-500 px-4 text-sm font-semibold text-white transition-colors hover:bg-green-600"
                    >
                        S'inscrire gratuitement
                    </Link>
                </div>
            </div>
        </nav>

        <!-- ── Hero ────────────────────────────────────────────────────────────── -->
        <section class="mx-auto max-w-5xl px-6 pb-16 pt-20 text-center">
            <div
                class="mb-6 inline-flex items-center gap-2 rounded-pill border border-green-100 bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700"
            >
                <Leaf :size="12" /> Propulsé par FatSecret API
            </div>

            <h1 class="mb-6 font-display text-[48px] leading-[1.05] tracking-tight text-neutral-900 sm:text-[64px]">
                Mangez mieux,<br />
                <span class="text-green-500">vivez mieux.</span>
            </h1>

            <p class="mx-auto mb-10 max-w-xl text-lg leading-relaxed text-neutral-500">
                Suivez vos calories, vos macros et votre hydratation au quotidien. Créez vos recettes. Visualisez votre
                progression.
            </p>

            <div class="flex flex-col justify-center gap-3 sm:flex-row">
                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="inline-flex h-12 items-center justify-center gap-2 rounded-pill bg-green-500 px-8 text-sm font-semibold text-white shadow-sm transition-all hover:-translate-y-px hover:bg-green-600"
                >
                    Commencer gratuitement <ArrowRight :size="16" />
                </Link>
                <Link
                    v-if="canLogin"
                    :href="route('login')"
                    class="inline-flex h-12 items-center justify-center rounded-pill border border-neutral-200 bg-white px-8 text-sm font-semibold text-neutral-700 transition-colors hover:border-neutral-300"
                >
                    Se connecter
                </Link>
            </div>
        </section>

        <!-- ── Benefits strip ──────────────────────────────────────────────────── -->
        <section class="border-y border-neutral-100 bg-white py-5">
            <div class="mx-auto max-w-5xl px-6">
                <ul class="flex flex-wrap items-center justify-center gap-x-8 gap-y-2">
                    <li v-for="b in BENEFITS" :key="b" class="flex items-center gap-2 text-sm text-neutral-600">
                        <CheckCircle2 :size="14" class="shrink-0 text-green-500" />
                        {{ b }}
                    </li>
                </ul>
            </div>
        </section>

        <!-- ── Features grid ───────────────────────────────────────────────────── -->
        <section class="mx-auto max-w-5xl px-6 py-20">
            <div class="mb-12 text-center">
                <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-neutral-400">Fonctionnalités</p>
                <h2 class="font-display text-[36px] leading-tight text-neutral-900">Tout ce dont vous avez besoin</h2>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div
                    v-for="feat in FEATURES"
                    :key="feat.title"
                    class="rounded-xl bg-white p-6 shadow-sm transition-shadow hover:shadow-md"
                >
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg" :class="feat.color">
                        <component :is="feat.icon" :size="20" />
                    </div>
                    <h3 class="mb-2 font-semibold text-neutral-900">{{ feat.title }}</h3>
                    <p class="text-sm leading-relaxed text-neutral-500">{{ feat.desc }}</p>
                </div>
            </div>
        </section>

        <!-- ── CTA bottom ──────────────────────────────────────────────────────── -->
        <section class="bg-green-500 py-16 text-center text-white">
            <h2 class="mb-4 font-display text-[32px] leading-tight">Prêt à commencer ?</h2>
            <p class="mb-8 text-sm text-green-100">Créez votre compte gratuit en moins de 30 secondes.</p>
            <Link
                v-if="canRegister"
                :href="route('register')"
                class="inline-flex h-12 items-center gap-2 rounded-pill bg-white px-8 text-sm font-semibold text-green-700 transition-colors hover:bg-green-50"
            >
                Créer mon compte <ArrowRight :size="16" />
            </Link>
        </section>

        <!-- ── Footer ──────────────────────────────────────────────────────────── -->
        <footer class="border-t border-neutral-100 bg-white py-8 text-center text-xs text-neutral-400">
            © {{ new Date().getFullYear() }} LuckyFood — Tous droits réservés
        </footer>
    </div>
</template>
