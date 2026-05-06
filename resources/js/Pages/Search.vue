<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight, Clock, LayoutGrid, Loader2, Pencil, Search, ScanLine, Star } from 'lucide-vue-next'
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
    favorites: { type: Array, default: () => [] },
    recentFoods: { type: Array, default: () => [] },
    favoriteFoodIds: { type: Array, default: () => [] },
})

const query = ref(props.query)
const searching = ref(false)
const selectedMeal = ref(props.meal ?? 'breakfast')
const selectedCategoryId = ref(props.categoryId)
const showScanner = ref(false)
const showManualForm = ref(false)
const favoriteIds = ref(new Set(props.favoriteFoodIds.map(String)))
const { addingFood, toast, addFoodToJournal } = useJournalActions()
const manualFood = ref({
    food_name: '',
    serving_description: 'Portion personnalisée',
    quantity: 1,
    calories: 0,
    protein: 0,
    carbs: 0,
    fat: 0,
    fiber: 0,
})

const page = computed(() => props.pagination?.page ?? 0)
const total = computed(() => props.pagination?.total ?? 0)
const from = computed(() => props.pagination?.from ?? 0)
const to = computed(() => props.pagination?.to ?? 0)
const normalizedQuery = computed(() => query.value.trim())
const canSearch = computed(() => normalizedQuery.value.length >= 2)
const displayedResults = computed(() => (canSearch.value ? props.results : []))
const effectiveSearchError = computed(() => (canSearch.value ? props.searchError : null))

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
    if (val.trim().length < 2) {
        searching.value = false
        return
    }

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
watch(
    () => props.favoriteFoodIds,
    (ids) => {
        favoriteIds.value = new Set(ids.map(String))
    },
)

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

const addManualFood = () => {
    const name = manualFood.value.food_name.trim()
    if (!name) return

    addFoodToJournal(
        {
            food_id: `manual:${Date.now()}`,
            food_name: name,
        },
        {
            meal_type: selectedMeal.value,
            calories: manualFood.value.calories || 0,
            protein: manualFood.value.protein || 0,
            carbs: manualFood.value.carbs || 0,
            fat: manualFood.value.fat || 0,
            fiber: manualFood.value.fiber || 0,
            serving_description: manualFood.value.serving_description || 'Portion personnalisée',
            quantity: manualFood.value.quantity || 1,
        },
        { loadingKey: 'manual' },
    )

    manualFood.value.food_name = ''
}

const isFavorite = (food) => favoriteIds.value.has(String(food.food_id))

const toggleFavorite = (food) => {
    const payload = {
        food_id: food.food_id,
        food_name: food.food_name,
        serving_description: food.serving_description ?? '',
        calories: food.calories ?? 0,
        protein: food.protein ?? 0,
        carbs: food.carbs ?? 0,
        fat: food.fat ?? 0,
        fiber: food.fiber ?? 0,
    }

    if (isFavorite(food)) {
        router.delete(route('favorite-foods.destroy', food.food_id), {
            preserveScroll: true,
            preserveState: true,
        })
        return
    }

    router.post(route('favorite-foods.store'), payload, {
        preserveScroll: true,
        preserveState: true,
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
                v-if="effectiveSearchError"
                class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800"
            >
                {{ effectiveSearchError.message }}
            </div>

            <!-- Manual food -->
            <section class="mb-6 rounded-xl bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <Pencil :size="15" class="text-neutral-400" />
                        <p class="text-sm font-bold text-neutral-800">Aliment manuel</p>
                    </div>
                    <button
                        class="rounded-pill bg-neutral-100 px-3 py-1.5 text-xs font-semibold text-neutral-600 transition-colors hover:bg-green-50 hover:text-green-600"
                        @click="showManualForm = !showManualForm"
                    >
                        {{ showManualForm ? 'Fermer' : 'Ajouter' }}
                    </button>
                </div>
                <div v-if="showManualForm" class="mt-4 grid gap-3 md:grid-cols-6">
                    <AppInput
                        v-model="manualFood.food_name"
                        class="md:col-span-2"
                        label="Nom"
                        placeholder="Ex : Omelette maison"
                    />
                    <AppInput
                        v-model="manualFood.serving_description"
                        class="md:col-span-2"
                        label="Portion"
                        placeholder="1 assiette"
                    />
                    <AppInput
                        v-model.number="manualFood.quantity"
                        label="Quantité"
                        type="number"
                        min="0.1"
                        step="0.1"
                    />
                    <AppInput v-model.number="manualFood.calories" label="kcal" type="number" min="0" step="1" />
                    <AppInput v-model.number="manualFood.protein" label="Prot." type="number" min="0" step="0.1" />
                    <AppInput v-model.number="manualFood.carbs" label="Gluc." type="number" min="0" step="0.1" />
                    <AppInput v-model.number="manualFood.fat" label="Lip." type="number" min="0" step="0.1" />
                    <AppInput v-model.number="manualFood.fiber" label="Fibres" type="number" min="0" step="0.1" />
                    <button
                        class="flex h-11 items-center justify-center rounded-pill bg-green-500 px-4 text-sm font-semibold text-white transition-colors hover:bg-green-600 md:col-span-2 md:self-end"
                        @click="addManualFood"
                    >
                        Ajouter au journal
                    </button>
                </div>
            </section>

            <!-- Favorites and recents -->
            <section v-if="!normalizedQuery && (favorites.length || recentFoods.length)" class="mb-8 space-y-6">
                <div v-if="favorites.length">
                    <div class="mb-3 flex items-center gap-2">
                        <Star :size="14" class="text-amber-500" fill="currentColor" />
                        <p class="text-sm font-bold text-neutral-800">Favoris</p>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                        <FoodResultCard
                            v-for="food in favorites"
                            :key="`fav-${food.food_id}`"
                            :food="food"
                            :detail-params="detailParams(food)"
                            :adding="addingFood === food.food_id"
                            :favorite="isFavorite(food)"
                            @add="addToJournal"
                            @favorite="toggleFavorite"
                        />
                    </div>
                </div>

                <div v-if="recentFoods.length">
                    <div class="mb-3 flex items-center gap-2">
                        <Clock :size="14" class="text-neutral-400" />
                        <p class="text-sm font-bold text-neutral-800">Récents</p>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                        <FoodResultCard
                            v-for="food in recentFoods"
                            :key="`recent-${food.food_id}`"
                            :food="food"
                            :detail-params="detailParams(food)"
                            :adding="addingFood === food.food_id"
                            :favorite="isFavorite(food)"
                            @add="addToJournal"
                            @favorite="toggleFavorite"
                        />
                    </div>
                </div>
            </section>

            <!-- Results -->
            <section v-if="displayedResults.length">
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
                        v-for="food in displayedResults"
                        :key="food.food_id"
                        :food="food"
                        :detail-params="detailParams(food)"
                        :adding="addingFood === food.food_id"
                        :favorite="isFavorite(food)"
                        @add="addToJournal"
                        @favorite="toggleFavorite"
                    />
                </div>
            </section>

            <!-- Empty states -->
            <div v-else-if="normalizedQuery && !canSearch" class="py-16 text-center text-neutral-400">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-neutral-100">
                    <Search :size="28" class="opacity-40" />
                </div>
                <p class="font-semibold text-neutral-600">Tapez au moins 2 caractères</p>
            </div>

            <div
                v-else-if="normalizedQuery && !searching && !effectiveSearchError"
                class="py-20 text-center text-neutral-400"
            >
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-neutral-100">
                    <Search :size="28" class="opacity-40" />
                </div>
                <p class="font-semibold text-neutral-600">Aucun résultat</p>
                <p class="mt-1 text-sm">Aucun aliment trouvé pour "{{ query }}"</p>
            </div>

            <div
                v-else-if="!normalizedQuery && !favorites.length && !recentFoods.length"
                class="py-20 text-center text-neutral-400"
            >
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
