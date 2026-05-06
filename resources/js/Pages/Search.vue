<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight, Loader2, Search, ScanLine, LayoutGrid } from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import AppInput from '@/Components/ui/AppInput.vue'
import AppToast from '@/Components/ui/AppToast.vue'
import BarcodeScanner from '@/Components/food/BarcodeScanner.vue'
import FoodResultCard from '@/Components/food/FoodResultCard.vue'
import MealSelector from '@/Components/food/MealSelector.vue'
import { useJournalActions } from '@/composables/useJournalActions'

const props = defineProps({
    query: { type: String, default: '' },
    results: { type: Array, default: () => [] },
    meal: { type: String, default: 'breakfast' },
    pagination: { type: Object, default: () => ({}) },
    searchError: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    categoryId: { type: Number, default: null },
})

const query = ref(props.query)
const searching = ref(false)
const selectedMeal = ref(props.meal ?? 'breakfast')
const selectedCategoryId = ref(props.categoryId)
const showScanner = ref(false)
const { addingFood, toast, addFoodToJournal } = useJournalActions()

const page = computed(() => props.pagination?.page ?? 0)
const total = computed(() => props.pagination?.total ?? 0)
const from = computed(() => props.pagination?.from ?? 0)
const to = computed(() => props.pagination?.to ?? 0)

const selectedCategoryName = computed(() => {
    if (!selectedCategoryId.value) return null
    return props.categories.find((c) => c.food_category_id == selectedCategoryId.value)?.food_category_name ?? null
})

const searchParams = (overrides = {}) => ({
    q: query.value,
    meal: selectedMeal.value,
    page: 0,
    category_id: selectedCategoryId.value || undefined,
    ...overrides,
})

const pageParams = (targetPage) => searchParams({ page: targetPage })
const detailParams = (food) => ({ foodId: food.food_id, meal: selectedMeal.value, q: query.value })

let searchTimer = null
watch(query, (val) => {
    clearTimeout(searchTimer)
    if (!val.trim()) return
    searching.value = true
    searchTimer = setTimeout(() => {
        router.get(route('search.index'), searchParams({ q: val, page: 0 }), {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                searching.value = false
            },
        })
    }, 350)
})

function applyCategory(categoryId) {
    selectedCategoryId.value = categoryId
    router.get(route('search.index'), searchParams({ category_id: categoryId || undefined, page: 0 }), {
        preserveState: true,
        preserveScroll: true,
    })
}

function onBarcodeScanned(code) {
    showScanner.value = false
    router.get(route('search.barcode'), { barcode: code, meal: selectedMeal.value })
}

const addToJournal = (food) => {
    addFoodToJournal(food, {
        meal_type: selectedMeal.value,
        calories: food.calories ?? 0,
        protein: food.protein ?? 0,
        carbs: food.carbs ?? 0,
        fat: food.fat ?? 0,
        serving_description: food.serving_description ?? '',
        quantity: 1,
    })
}
</script>

<template>
    <MainLayout title="Rechercher">
        <div class="px-5 py-6 md:px-8">
            <!-- Header -->
            <header class="mb-8 flex flex-col gap-5">
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-neutral-400">Base FatSecret</p>
                    <h1 class="font-display text-[30px] leading-tight tracking-tight text-neutral-900">Rechercher</h1>
                </div>

                <div class="grid gap-3 lg:grid-cols-[minmax(320px,640px)_auto] lg:items-end">
                    <!-- Search input + scanner button -->
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <AppInput
                                v-model="query"
                                placeholder="Ex : poulet, yaourt grec, banane..."
                                :icon="searching ? Loader2 : Search"
                            />
                        </div>
                        <button
                            @click="showScanner = true"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-neutral-200 bg-white text-neutral-500 transition-colors hover:border-green-300 hover:text-green-600"
                            title="Scanner un code-barre"
                            aria-label="Scanner un code-barre"
                        >
                            <ScanLine :size="18" />
                        </button>
                    </div>

                    <MealSelector v-model="selectedMeal" />
                </div>

                <!-- Category filter -->
                <div v-if="categories.length" class="flex flex-wrap items-center gap-2">
                    <span class="flex shrink-0 items-center gap-1.5 text-xs font-semibold text-neutral-400">
                        <LayoutGrid :size="12" />
                        Catégorie
                    </span>
                    <button
                        @click="applyCategory(null)"
                        :aria-pressed="!selectedCategoryId"
                        class="h-7 rounded-full px-3 text-[11px] font-semibold transition-colors"
                        :class="
                            !selectedCategoryId
                                ? 'bg-green-500 text-white'
                                : 'bg-neutral-100 text-neutral-500 hover:bg-green-50 hover:text-green-600'
                        "
                    >
                        Toutes
                    </button>
                    <button
                        v-for="cat in categories"
                        :key="cat.food_category_id"
                        @click="applyCategory(cat.food_category_id)"
                        :aria-pressed="selectedCategoryId == cat.food_category_id"
                        class="h-7 rounded-full px-3 text-[11px] font-semibold transition-colors"
                        :class="
                            selectedCategoryId == cat.food_category_id
                                ? 'bg-green-500 text-white'
                                : 'bg-neutral-100 text-neutral-500 hover:bg-green-50 hover:text-green-600'
                        "
                    >
                        {{ cat.food_category_name }}
                    </button>
                </div>
            </header>

            <!-- Error -->
            <div
                v-if="searchError"
                class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800"
            >
                {{ searchError.message }}
            </div>

            <!-- Results -->
            <section v-if="results.length">
                <!-- Pagination bar -->
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-neutral-500">
                        <template v-if="selectedCategoryName">
                            <span class="font-semibold text-green-600">{{ selectedCategoryName }}</span>
                            &thinsp;·&thinsp;
                        </template>
                        <span class="font-bold text-neutral-800">{{ from }}–{{ to }}</span>
                        sur <span class="font-semibold text-neutral-700">{{ total }}</span> résultats
                    </p>
                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('search.index', pageParams(Math.max(0, page - 1)))"
                            preserve-scroll
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-neutral-200 bg-white text-neutral-500 transition-colors hover:border-green-300 hover:text-green-600"
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
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-neutral-200 bg-white text-neutral-500 transition-colors hover:border-green-300 hover:text-green-600"
                            :class="!pagination.has_next && 'pointer-events-none opacity-40'"
                            aria-label="Page suivante"
                        >
                            <ChevronRight :size="16" />
                        </Link>
                    </div>
                </div>

                <!-- Cards grid -->
                <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                    <FoodResultCard
                        v-for="food in results"
                        :key="food.food_id"
                        :food="food"
                        :detail-params="detailParams(food)"
                        :adding="addingFood === food.food_id"
                        @add="addToJournal"
                    />
                </div>
            </section>

            <!-- Empty states -->
            <div v-else-if="query && !searching && !searchError" class="py-20 text-center text-neutral-400">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-neutral-100">
                    <Search :size="28" class="opacity-40" />
                </div>
                <p class="font-semibold text-neutral-600">Aucun résultat</p>
                <p class="mt-1 text-sm">Aucun aliment trouvé pour "{{ query }}"</p>
            </div>

            <div v-else-if="!query" class="py-20 text-center text-neutral-400">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-50">
                    <Search :size="28" class="text-green-400" />
                </div>
                <p class="font-semibold text-neutral-600">Rechercher un aliment</p>
                <p class="mt-1 text-sm">Tapez un aliment dans la barre de recherche ou scannez un code-barre</p>
            </div>
        </div>

        <AppToast :message="toast" />

        <!-- Barcode scanner modal -->
        <Transition name="fade">
            <BarcodeScanner v-if="showScanner" @scanned="onBarcodeScanned" @close="showScanner = false" />
        </Transition>
    </MainLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
