<script setup>
import { Link } from '@inertiajs/vue3'
import { ArrowUpRight, Leaf, Loader2, Plus, Star } from 'lucide-vue-next'
import AppBadge from '@/Components/ui/AppBadge.vue'
import { useFormatting } from '@/composables/useFormatting'

defineProps({
    food: { type: Object, required: true },
    detailParams: { type: Object, required: true },
    adding: { type: Boolean, default: false },
    favorite: { type: Boolean, default: false },
})

defineEmits(['add', 'favorite'])

const { number } = useFormatting()
</script>

<template>
    <article
        class="flex flex-col rounded-xl bg-white shadow-md transition-all duration-200 hover:-translate-y-px hover:shadow-lg"
    >
        <div class="flex flex-1 flex-col p-5">
            <div class="flex items-start gap-3">
                <Link :href="route('search.show', detailParams)" class="shrink-0">
                    <div v-if="food.image_url" class="h-12 w-12 overflow-hidden rounded-xl">
                        <img :src="food.image_url" :alt="food.food_name" class="h-full w-full object-cover" />
                    </div>
                    <div v-else class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-50">
                        <Leaf :size="20" class="text-green-500" />
                    </div>
                </Link>

                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <Link
                            :href="route('search.show', detailParams)"
                            class="line-clamp-2 text-[15px] font-bold leading-snug text-neutral-900 transition-colors hover:text-green-600"
                        >
                            {{ food.food_name }}
                        </Link>
                        <AppBadge v-if="food.food_type === 'Generic'" color="green" class="mt-0.5 shrink-0"
                            >Générique</AppBadge
                        >
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                        <span class="text-xs text-neutral-400">{{
                            food.serving_description || 'Portion non précisée'
                        }}</span>
                        <AppBadge v-if="food.brand_name" color="neutral">{{ food.brand_name }}</AppBadge>
                    </div>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-4 gap-2 border-t border-neutral-100 pt-4 text-center">
                <div>
                    <p class="font-mono text-[22px] font-black leading-none text-neutral-900">
                        {{ number(food.calories, 0) }}
                    </p>
                    <p class="mt-1.5 text-[10px] font-bold uppercase tracking-wide text-neutral-400">kcal</p>
                </div>
                <div>
                    <p class="font-mono text-[22px] font-black leading-none text-blue-500">
                        {{ number(food.protein) }}
                    </p>
                    <p class="mt-1.5 text-[10px] font-bold uppercase tracking-wide text-blue-400">Prot. g</p>
                </div>
                <div>
                    <p class="font-mono text-[22px] font-black leading-none text-amber-500">{{ number(food.carbs) }}</p>
                    <p class="mt-1.5 text-[10px] font-bold uppercase tracking-wide text-amber-400">Gluc. g</p>
                </div>
                <div>
                    <p class="font-mono text-[22px] font-black leading-none text-coral-500">{{ number(food.fat) }}</p>
                    <p class="mt-1.5 text-[10px] font-bold uppercase tracking-wide text-coral-400">Lip. g</p>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <Link
                    :href="route('search.show', detailParams)"
                    class="inline-flex h-9 flex-1 items-center justify-center gap-1.5 rounded-xl bg-neutral-50 text-sm font-semibold text-neutral-600 transition-colors hover:bg-green-50 hover:text-green-600"
                >
                    Voir les détails
                    <ArrowUpRight :size="14" />
                </Link>
                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-green-500 text-white shadow-sm transition-all hover:-translate-y-px hover:bg-green-600 active:translate-y-0 disabled:opacity-50"
                    :disabled="adding"
                    aria-label="Ajouter au journal"
                    title="Ajouter au journal"
                    @click="$emit('add', food)"
                >
                    <Loader2 v-if="adding" :size="14" class="animate-spin" />
                    <Plus v-else :size="16" />
                </button>
                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-neutral-200 bg-white transition-colors hover:border-amber-300 hover:text-amber-500"
                    :class="favorite ? 'border-amber-300 text-amber-500' : 'text-neutral-400'"
                    :aria-label="favorite ? 'Retirer des favoris' : 'Ajouter aux favoris'"
                    :title="favorite ? 'Retirer des favoris' : 'Ajouter aux favoris'"
                    @click="$emit('favorite', food)"
                >
                    <Star :size="15" :fill="favorite ? 'currentColor' : 'none'" />
                </button>
            </div>
        </div>
    </article>
</template>
