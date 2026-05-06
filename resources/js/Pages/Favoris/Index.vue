<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Search, Star } from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import AppButton from '@/Components/ui/AppButton.vue'
import AppToast from '@/Components/ui/AppToast.vue'
import FoodResultCard from '@/Components/food/FoodResultCard.vue'
import MealSelector from '@/Components/food/MealSelector.vue'
import { useJournalActions } from '@/composables/useJournalActions'

defineProps({
    favorites: { type: Array, default: () => [] },
})

const selectedMeal = ref('breakfast')
const { addingFood, toast, addFoodToJournal } = useJournalActions()

const detailParams = (food) => ({
    foodId: food.food_id,
    meal: selectedMeal.value,
    q: food.food_name,
    from: 'favorites',
})

const addToJournal = (food) => {
    addFoodToJournal(food, {
        meal_type: selectedMeal.value,
        calories: food.calories ?? 0,
        protein: food.protein ?? 0,
        carbs: food.carbs ?? 0,
        fat: food.fat ?? 0,
        fiber: food.fiber ?? 0,
        serving_description: food.serving_description ?? '',
        quantity: 1,
    })
}

const removeFavorite = (food) => {
    router.delete(route('favorite-foods.destroy', food.food_id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <MainLayout title="Favoris">
        <div class="px-5 py-6 md:px-8">
            <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-neutral-400">Aliments</p>
                    <h1 class="font-display text-[30px] leading-tight tracking-tight text-neutral-900">Favoris</h1>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <MealSelector v-model="selectedMeal" />
                    <AppButton :href="route('search.index')" as="a" variant="secondary">
                        <Search :size="15" /> Rechercher
                    </AppButton>
                </div>
            </header>

            <div v-if="favorites.length" class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                <FoodResultCard
                    v-for="food in favorites"
                    :key="food.food_id"
                    :food="food"
                    :detail-params="detailParams(food)"
                    :adding="addingFood === food.food_id"
                    favorite
                    @add="addToJournal"
                    @favorite="removeFavorite"
                />
            </div>

            <div v-else class="py-20 text-center text-neutral-400">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-50">
                    <Star :size="28" class="text-amber-400" />
                </div>
                <p class="font-semibold text-neutral-600">Aucun favori</p>
                <p class="mt-1 text-sm">Ajoutez une etoile depuis la recherche pour retrouver vos aliments ici.</p>
                <AppButton class="mt-4" :href="route('search.index')" as="a">
                    <Search :size="15" /> Rechercher un aliment
                </AppButton>
            </div>
        </div>

        <AppToast :message="toast" />
    </MainLayout>
</template>
