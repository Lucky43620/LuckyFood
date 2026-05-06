<script setup>
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { ChevronLeft, ChefHat, Clock, Users, Flame, Trash2 } from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import AppBadge from '@/Components/ui/AppBadge.vue'
import AppButton from '@/Components/ui/AppButton.vue'
import { MEALS } from '@/constants/nutrition'
import { RECIPE_CATEGORY_COLORS } from '@/constants/nutrition'

const props = defineProps({
    recipe: { type: Object, required: true },
    perServing: { type: Object, default: () => ({ calories: 0, protein: 0, carbs: 0, fat: 0 }) },
})

const page = usePage()
const canDelete = computed(() => props.recipe.user_id === page.props.auth.user?.id)
const journalForm = ref({
    date: new Date().toISOString().split('T')[0],
    meal_type: 'lunch',
    servings: 1,
})
const shoppingList = computed(() =>
    (props.recipe.ingredients ?? [])
        .map((ingredient) => `${ingredient.quantity}${ingredient.unit ?? 'g'} ${ingredient.food_name}`)
        .join('\n'),
)

const deleteRecipe = () => {
    if (confirm('Supprimer cette recette ?')) {
        router.delete(route('recipes.destroy', props.recipe.id), {
            onSuccess: () => router.visit(route('recipes.index')),
        })
    }
}

const addToJournal = () => {
    router.post(route('recipes.add-to-journal', props.recipe.id), journalForm.value, { preserveScroll: true })
}
</script>

<template>
    <MainLayout :title="recipe.name">
        <div class="flex max-w-2xl flex-col gap-5 px-6 py-6 md:px-7">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <button
                    @click="router.visit(route('recipes.index'))"
                    class="flex h-9 w-9 items-center justify-center rounded-md bg-white text-neutral-500 shadow-sm transition-colors hover:text-neutral-800"
                    aria-label="Retour aux recettes"
                >
                    <ChevronLeft :size="18" />
                </button>
                <div class="min-w-0 flex-1">
                    <p class="mb-0.5 text-xs font-semibold uppercase tracking-widest text-neutral-400">Recette</p>
                    <h1 class="truncate font-display text-[24px] leading-tight tracking-tight text-neutral-900">
                        {{ recipe.name }}
                    </h1>
                </div>
                <button
                    v-if="canDelete"
                    @click="deleteRecipe"
                    class="flex h-9 w-9 items-center justify-center rounded-md text-neutral-400 transition-colors hover:bg-red-50 hover:text-red-500"
                    aria-label="Supprimer la recette"
                >
                    <Trash2 :size="16" />
                </button>
            </div>

            <!-- Hero card -->
            <div class="overflow-hidden rounded-xl bg-white shadow-md">
                <div class="flex h-36 items-center justify-center bg-gradient-to-br from-green-100 to-green-50">
                    <ChefHat :size="40" class="text-green-300" />
                </div>
                <div class="p-5">
                    <!-- Tags -->
                    <div class="mb-4 flex flex-wrap gap-1.5">
                        <AppBadge
                            v-for="tag in recipe.tags ?? []"
                            :key="tag"
                            :color="RECIPE_CATEGORY_COLORS[tag] ?? 'neutral'"
                        >
                            {{ tag }}
                        </AppBadge>
                        <AppBadge v-if="recipe.is_public" color="blue">Public</AppBadge>
                    </div>

                    <!-- Meta -->
                    <div class="mb-5 flex items-center gap-4 text-xs text-neutral-400">
                        <span class="flex items-center gap-1.5"><Clock :size="13" /> {{ recipe.prep_time }} min</span>
                        <span class="flex items-center gap-1.5"
                            ><Users :size="13" /> {{ recipe.servings }} portion{{
                                recipe.servings > 1 ? 's' : ''
                            }}</span
                        >
                    </div>

                    <!-- Per-serving macros -->
                    <p class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-neutral-400">Par portion</p>
                    <div class="grid grid-cols-4 gap-3 text-center">
                        <div
                            v-for="m in [
                                { label: 'Calories', val: perServing.calories, unit: 'kcal', color: 'text-orange-500' },
                                { label: 'Protéines', val: perServing.protein, unit: 'g', color: 'text-green-600' },
                                { label: 'Glucides', val: perServing.carbs, unit: 'g', color: 'text-amber-600' },
                                { label: 'Lipides', val: perServing.fat, unit: 'g', color: 'text-neutral-500' },
                            ]"
                            :key="m.label"
                        >
                            <p class="font-mono text-xl font-semibold" :class="m.color">{{ m.val }}</p>
                            <p class="mt-0.5 text-[10px] text-neutral-400">{{ m.unit }}</p>
                            <p class="text-[10px] font-medium text-neutral-500">{{ m.label }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add to journal -->
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <p class="mb-4 text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                    Ajouter au journal
                </p>
                <div class="grid gap-3 sm:grid-cols-[1fr_1fr_96px_auto] sm:items-end">
                    <label class="flex flex-col gap-1.5 text-sm font-semibold text-neutral-700">
                        Date
                        <input
                            v-model="journalForm.date"
                            type="date"
                            class="h-10 rounded-md border border-neutral-200 px-3 text-sm font-normal focus:border-green-400 focus:outline-none"
                        />
                    </label>
                    <label class="flex flex-col gap-1.5 text-sm font-semibold text-neutral-700">
                        Repas
                        <select
                            v-model="journalForm.meal_type"
                            class="h-10 rounded-md border border-neutral-200 px-3 text-sm font-normal focus:border-green-400 focus:outline-none"
                        >
                            <option v-for="meal in MEALS" :key="meal.key" :value="meal.key">{{ meal.label }}</option>
                        </select>
                    </label>
                    <label class="flex flex-col gap-1.5 text-sm font-semibold text-neutral-700">
                        Portions
                        <input
                            v-model.number="journalForm.servings"
                            type="number"
                            min="0.1"
                            step="0.1"
                            class="h-10 rounded-md border border-neutral-200 px-3 text-sm font-normal focus:border-green-400 focus:outline-none"
                        />
                    </label>
                    <AppButton @click="addToJournal">Ajouter</AppButton>
                </div>
            </div>

            <!-- Ingredients -->
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-neutral-100 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                        Ingrédients ({{ recipe.ingredients?.length ?? 0 }})
                    </p>
                </div>
                <div class="divide-y divide-neutral-50">
                    <div
                        v-for="ing in recipe.ingredients"
                        :key="ing.id"
                        class="flex items-center justify-between px-4 py-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-semibold text-neutral-800">{{ ing.food_name }}</p>
                            <p class="font-mono text-[11px] text-neutral-400">{{ ing.calories ?? 0 }} kcal</p>
                        </div>
                        <span class="ml-3 shrink-0 font-mono text-xs font-semibold text-neutral-500">
                            {{ ing.quantity }}{{ ing.unit ?? 'g' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Shopping list -->
            <div v-if="shoppingList" class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-neutral-100 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-neutral-400">Liste de courses</p>
                </div>
                <textarea
                    :value="shoppingList"
                    readonly
                    rows="5"
                    class="w-full resize-none border-0 bg-white px-4 py-3 font-mono text-xs text-neutral-600 focus:ring-0"
                />
            </div>

            <!-- Instructions -->
            <div v-if="(recipe.instructions ?? []).length" class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-neutral-100 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-neutral-400">Préparation</p>
                </div>
                <div class="divide-y divide-neutral-50">
                    <div v-for="(instruction, index) in recipe.instructions" :key="index" class="flex gap-3 px-4 py-3">
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-50 text-xs font-bold text-green-600"
                        >
                            {{ index + 1 }}
                        </span>
                        <p class="text-[13px] leading-6 text-neutral-700">{{ instruction }}</p>
                    </div>
                </div>
            </div>

            <!-- Total nutritional info -->
            <div class="rounded-lg bg-green-50 px-4 py-3">
                <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-green-700">Total recette</p>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-1">
                        <Flame :size="14" class="text-orange-400" />
                        <span class="font-mono text-sm font-semibold text-neutral-800">{{
                            recipe.total_calories
                        }}</span>
                        <span class="text-xs text-neutral-400">kcal</span>
                    </div>
                    <span class="text-xs text-neutral-400">·</span>
                    <span class="text-xs text-neutral-500">P {{ recipe.total_protein }}g</span>
                    <span class="text-xs text-neutral-500">G {{ recipe.total_carbs }}g</span>
                    <span class="text-xs text-neutral-500">L {{ recipe.total_fat }}g</span>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
