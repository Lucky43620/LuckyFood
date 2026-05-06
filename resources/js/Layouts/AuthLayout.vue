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
    <div class="flex min-h-screen bg-neutral-25">
        <Head :title="title" />

        <!-- ── Left branding panel (lg+) ─────────────────────────────────────── -->
        <div
            class="hidden shrink-0 flex-col justify-between bg-gradient-to-br from-green-600 to-green-800 p-12 text-white lg:flex lg:w-[480px] xl:w-[520px]"
        >
            <!-- Logo -->
            <Link :href="route('home')" class="flex w-fit items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/20">
                    <Leaf :size="18" class="text-white" />
                </div>
                <span class="font-display text-xl text-white">LuckyFood</span>
            </Link>

            <!-- Headline -->
            <div>
                <h1 class="mb-4 font-display text-[38px] leading-tight">Mangez mieux,<br />vivez mieux.</h1>
                <p class="mb-8 text-sm leading-relaxed text-green-100">
                    Suivez votre alimentation, atteignez vos objectifs nutritionnels et créez vos recettes
                    personnalisées.
                </p>

                <ul class="flex flex-col gap-3">
                    <li v-for="feat in FEATURES" :key="feat" class="flex items-start gap-2.5 text-sm text-green-50">
                        <CheckCircle2 :size="16" class="mt-0.5 shrink-0 text-green-300" />
                        {{ feat }}
                    </li>
                </ul>
            </div>

            <!-- Footer -->
            <p class="text-xs text-green-300">© {{ new Date().getFullYear() }} LuckyFood · Propulsé par FatSecret</p>
        </div>

        <!-- ── Right form panel ───────────────────────────────────────────────── -->
        <div class="flex flex-1 flex-col items-center justify-center p-6 sm:p-10">
            <!-- Mobile logo (hidden on lg) -->
            <Link :href="route('home')" class="mb-8 flex items-center gap-2 lg:hidden">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-500">
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
