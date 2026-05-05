<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { ArrowUpRight, ChevronLeft, ChevronRight, Leaf, Loader2, Plus, Search } from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import AppInput from '@/Components/ui/AppInput.vue'
import AppBadge from '@/Components/ui/AppBadge.vue'

const props = defineProps({
  query: { type: String, default: '' },
  results: { type: Array, default: () => [] },
  meal: { type: String, default: 'breakfast' },
  pagination: { type: Object, default: () => ({}) },
  searchError: { type: Object, default: null },
})

const query = ref(props.query)
const searching = ref(false)
const selectedMeal = ref(props.meal ?? 'breakfast')
const addingFood = ref(null)
const toast = ref(null)

const MEALS = [
  { key: 'breakfast', label: 'Petit-déjeuner' },
  { key: 'lunch', label: 'Déjeuner' },
  { key: 'snack', label: 'Collation' },
  { key: 'dinner', label: 'Dîner' },
]

const page = computed(() => props.pagination?.page ?? 0)
const total = computed(() => props.pagination?.total ?? 0)
const from = computed(() => props.pagination?.from ?? 0)
const to = computed(() => props.pagination?.to ?? 0)

const pageParams = (targetPage) => ({ q: query.value, meal: selectedMeal.value, page: targetPage })
const detailParams = (food) => ({ foodId: food.food_id, meal: selectedMeal.value, q: query.value })

const fmt = (value, digits = 1) => {
  if (value === null || value === undefined || value === '') return '-'
  const n = Number(value)
  return Number.isFinite(n) ? n.toLocaleString('fr-FR', { maximumFractionDigits: digits }) : '-'
}

let searchTimer = null
watch(query, (val) => {
  clearTimeout(searchTimer)
  if (!val.trim()) return
  searching.value = true
  searchTimer = setTimeout(() => {
    router.get(route('search.index'), { q: val, meal: selectedMeal.value, page: 0 }, {
      preserveState: true,
      preserveScroll: true,
      onFinish: () => { searching.value = false },
    })
  }, 350)
})

const addToJournal = (food) => {
  addingFood.value = food.food_id
  router.post(route('journal.store'), {
    food_id: food.food_id,
    food_name: food.food_name,
    meal_type: selectedMeal.value,
    calories: food.calories ?? 0,
    protein: food.protein ?? 0,
    carbs: food.carbs ?? 0,
    fat: food.fat ?? 0,
    serving_description: food.serving_description ?? '',
    quantity: 1,
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      toast.value = `${food.food_name} ajouté au journal`
      setTimeout(() => { toast.value = null }, 3000)
    },
    onFinish: () => { addingFood.value = null },
  })
}
</script>

<template>
  <MainLayout title="Rechercher">
    <div class="px-5 md:px-8 py-6">

      <!-- Header -->
      <header class="flex flex-col gap-5 mb-10">
        <div>
          <p class="text-xs font-semibold tracking-widest uppercase text-neutral-400 mb-1">Base FatSecret</p>
          <h1 class="font-display text-[30px] leading-tight tracking-tight text-neutral-900">Rechercher</h1>
        </div>

        <div class="grid gap-3 lg:grid-cols-[minmax(320px,640px)_auto] lg:items-end">
          <AppInput
            v-model="query"
            placeholder="Ex : poulet, yaourt grec, banane..."
            :icon="searching ? Loader2 : Search"
          />
          <div>
            <p class="text-xs font-semibold text-neutral-500 mb-2">Ajouter au repas</p>
            <div class="grid grid-cols-4 gap-2">
              <button
                v-for="m in MEALS"
                :key="m.key"
                @click="selectedMeal = m.key"
                class="h-9 rounded-pill text-[11px] font-semibold transition-all duration-150 truncate px-1"
                :class="selectedMeal === m.key
                  ? 'bg-green-500 text-white shadow-sm scale-[0.98]'
                  : 'bg-white border border-neutral-200 text-neutral-600 hover:border-green-300 hover:text-green-600'"
              >
                {{ m.label }}
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Error -->
      <div v-if="searchError" class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
        {{ searchError.message }}
      </div>

      <!-- Results -->
      <section v-if="results.length">
        <!-- Pagination bar -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
          <p class="text-sm text-neutral-500">
            <span class="font-bold text-neutral-800">{{ from }}–{{ to }}</span>
            sur <span class="font-semibold text-neutral-700">{{ total }}</span> résultats
          </p>
          <div class="flex items-center gap-2">
            <Link
              :href="route('search.index', pageParams(Math.max(0, page - 1)))"
              preserve-scroll
              class="h-9 w-9 rounded-lg border border-neutral-200 bg-white flex items-center justify-center text-neutral-500 hover:border-green-300 hover:text-green-600 transition-colors"
              :class="!pagination.has_previous && 'pointer-events-none opacity-40'"
              aria-label="Page précédente"
            >
              <ChevronLeft :size="16" />
            </Link>
            <span class="min-w-[96px] text-center text-xs font-semibold text-neutral-400">
              Page {{ page + 1 }} / {{ pagination.total_pages || 1 }}
            </span>
            <Link
              :href="route('search.index', pageParams(page + 1))"
              preserve-scroll
              class="h-9 w-9 rounded-lg border border-neutral-200 bg-white flex items-center justify-center text-neutral-500 hover:border-green-300 hover:text-green-600 transition-colors"
              :class="!pagination.has_next && 'pointer-events-none opacity-40'"
              aria-label="Page suivante"
            >
              <ChevronRight :size="16" />
            </Link>
          </div>
        </div>

        <!-- Cards grid -->
        <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
          <article
            v-for="food in results"
            :key="food.food_id"
            class="bg-white flex flex-col transition-all duration-200 hover:-translate-y-px"
            style="border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.07)"
            onmouseover="this.style.boxShadow='0 4px 6px rgba(0,0,0,0.07), 0 10px 30px rgba(0,0,0,0.12)'"
            onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.07)'"
          >
            <div class="p-5 flex flex-col flex-1">
              <!-- Food identity -->
              <div class="flex items-start gap-3">
                <!-- Icon -->
                <Link :href="route('search.show', detailParams(food))" class="shrink-0">
                  <div v-if="food.image_url" class="h-12 w-12 rounded-xl overflow-hidden">
                    <img :src="food.image_url" :alt="food.food_name" class="h-full w-full object-cover">
                  </div>
                  <div v-else class="h-12 w-12 rounded-xl bg-green-50 flex items-center justify-center">
                    <Leaf :size="20" class="text-green-500" />
                  </div>
                </Link>

                <!-- Name + info -->
                <div class="min-w-0 flex-1">
                  <div class="flex items-start justify-between gap-2">
                    <Link
                      :href="route('search.show', detailParams(food))"
                      class="font-bold text-[15px] leading-snug text-neutral-900 hover:text-green-600 line-clamp-2 transition-colors"
                    >
                      {{ food.food_name }}
                    </Link>
                    <AppBadge v-if="food.food_type === 'Generic'" color="green" class="shrink-0 mt-0.5">Générique</AppBadge>
                  </div>
                  <div class="mt-1 flex flex-wrap items-center gap-1.5">
                    <span class="text-xs text-neutral-400">{{ food.serving_description || 'Portion non précisée' }}</span>
                    <AppBadge v-if="food.brand_name" color="neutral">{{ food.brand_name }}</AppBadge>
                  </div>
                </div>
              </div>

              <!-- Macro row -->
              <div class="mt-5 pt-4 border-t border-neutral-100 grid grid-cols-4 gap-2 text-center">
                <!-- Calories -->
                <div>
                  <p class="font-mono text-[22px] font-black text-neutral-900 leading-none">{{ fmt(food.calories, 0) }}</p>
                  <p class="text-[10px] uppercase font-bold text-neutral-400 mt-1.5 tracking-wide">kcal</p>
                </div>
                <!-- Protein -->
                <div>
                  <p class="font-mono text-[22px] font-black leading-none" style="color: oklch(60% 0.16 240)">{{ fmt(food.protein) }}</p>
                  <p class="text-[10px] uppercase font-bold mt-1.5 tracking-wide" style="color: oklch(70% 0.15 240)">Prot. g</p>
                </div>
                <!-- Carbs -->
                <div>
                  <p class="font-mono text-[22px] font-black text-amber-500 leading-none">{{ fmt(food.carbs) }}</p>
                  <p class="text-[10px] uppercase font-bold text-amber-400 mt-1.5 tracking-wide">Gluc. g</p>
                </div>
                <!-- Fat -->
                <div>
                  <p class="font-mono text-[22px] font-black leading-none" style="color: oklch(64% 0.18 25)">{{ fmt(food.fat) }}</p>
                  <p class="text-[10px] uppercase font-bold mt-1.5 tracking-wide" style="color: oklch(72% 0.17 25)">Lip. g</p>
                </div>
              </div>

              <!-- Actions -->
              <div class="mt-4 flex gap-2">
                <Link
                  :href="route('search.show', detailParams(food))"
                  class="h-9 flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl bg-neutral-50 text-sm font-semibold text-neutral-600 hover:bg-green-50 hover:text-green-600 transition-colors"
                >
                  Voir les détails
                  <ArrowUpRight :size="14" />
                </Link>
                <button
                  @click="addToJournal(food)"
                  :disabled="addingFood === food.food_id"
                  class="h-9 w-9 inline-flex items-center justify-center rounded-xl bg-green-500 text-white hover:bg-green-600 shadow-sm transition-all hover:-translate-y-px active:translate-y-0 disabled:opacity-50"
                  aria-label="Ajouter au journal"
                  title="Ajouter au journal"
                >
                  <Loader2 v-if="addingFood === food.food_id" :size="14" class="animate-spin" />
                  <Plus v-else :size="16" />
                </button>
              </div>
            </div>
          </article>
        </div>
      </section>

      <!-- Empty states -->
      <div v-else-if="query && !searching && !searchError" class="text-center py-20 text-neutral-400">
        <div class="h-16 w-16 rounded-2xl bg-neutral-100 flex items-center justify-center mx-auto mb-4">
          <Search :size="28" class="opacity-40" />
        </div>
        <p class="font-semibold text-neutral-600">Aucun résultat</p>
        <p class="text-sm mt-1">Aucun aliment trouvé pour "{{ query }}"</p>
      </div>

      <div v-else-if="!query" class="text-center py-20 text-neutral-400">
        <div class="h-16 w-16 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-4">
          <Search :size="28" class="text-green-400" />
        </div>
        <p class="font-semibold text-neutral-600">Rechercher un aliment</p>
        <p class="text-sm mt-1">Tapez un aliment dans la barre de recherche</p>
      </div>
    </div>

    <!-- Toast -->
    <Transition name="toast">
      <div
        v-if="toast"
        class="fixed bottom-20 md:bottom-6 left-1/2 -translate-x-1/2 bg-neutral-900 text-white text-sm font-medium px-5 py-2.5 rounded-pill shadow-xl z-50 whitespace-nowrap"
      >
        {{ toast }}
      </div>
    </Transition>
  </MainLayout>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(-50%) translateY(10px); }
</style>
