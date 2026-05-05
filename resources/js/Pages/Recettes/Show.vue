<script setup>
import { router } from '@inertiajs/vue3'
import { ChevronLeft, ChefHat, Clock, Users, Flame, Trash2 } from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import AppBadge   from '@/Components/ui/AppBadge.vue'
import AppButton  from '@/Components/ui/AppButton.vue'

const props = defineProps({
  recipe:     { type: Object, required: true },
  perServing: { type: Object, default: () => ({ calories: 0, protein: 0, carbs: 0, fat: 0 }) },
})

const CATEGORY_COLORS = {
  'Haut en protéines': 'green',
  'Végétarien':        'amber',
  'Végan':             'blue',
  'Sans gluten':       'orange',
  'Rapide':            'neutral',
}

const deleteRecipe = () => {
  if (confirm('Supprimer cette recette ?')) {
    router.delete(route('recipes.destroy', props.recipe.id), {
      onSuccess: () => router.visit(route('recipes.index')),
    })
  }
}
</script>

<template>
  <MainLayout :title="recipe.name">
    <div class="px-6 md:px-7 py-6 max-w-2xl flex flex-col gap-5">

      <!-- Header -->
      <div class="flex items-center gap-3">
        <button
          @click="router.visit(route('recipes.index'))"
          class="w-9 h-9 flex items-center justify-center rounded-md bg-white shadow-sm text-neutral-500 hover:text-neutral-800 transition-colors"
        >
          <ChevronLeft :size="18" />
        </button>
        <div class="flex-1 min-w-0">
          <p class="text-xs font-semibold tracking-widest uppercase text-neutral-400 mb-0.5">Recette</p>
          <h1 class="font-display text-[24px] leading-tight tracking-tight text-neutral-900 truncate">
            {{ recipe.name }}
          </h1>
        </div>
        <button
          @click="deleteRecipe"
          class="w-9 h-9 flex items-center justify-center rounded-md text-neutral-400 hover:text-red-500 hover:bg-red-50 transition-colors"
        >
          <Trash2 :size="16" />
        </button>
      </div>

      <!-- Hero card -->
      <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="h-36 bg-gradient-to-br from-green-100 to-green-50 flex items-center justify-center">
          <ChefHat :size="40" class="text-green-300" />
        </div>
        <div class="p-5">
          <!-- Tags -->
          <div class="flex flex-wrap gap-1.5 mb-4">
            <AppBadge
              v-for="tag in (recipe.tags ?? [])"
              :key="tag"
              :color="CATEGORY_COLORS[tag] ?? 'neutral'"
            >
              {{ tag }}
            </AppBadge>
          </div>

          <!-- Meta -->
          <div class="flex items-center gap-4 text-xs text-neutral-400 mb-5">
            <span class="flex items-center gap-1.5"><Clock :size="13" /> {{ recipe.prep_time }} min</span>
            <span class="flex items-center gap-1.5"><Users :size="13" /> {{ recipe.servings }} portion{{ recipe.servings > 1 ? 's' : '' }}</span>
          </div>

          <!-- Per-serving macros -->
          <p class="text-[10px] font-semibold tracking-widest uppercase text-neutral-400 mb-3">Par portion</p>
          <div class="grid grid-cols-4 gap-3 text-center">
            <div v-for="m in [
              { label: 'Calories',  val: perServing.calories, unit: 'kcal', color: 'text-orange-500' },
              { label: 'Protéines', val: perServing.protein,  unit: 'g',    color: 'text-green-600' },
              { label: 'Glucides',  val: perServing.carbs,    unit: 'g',    color: 'text-amber-600' },
              { label: 'Lipides',   val: perServing.fat,      unit: 'g',    color: 'text-neutral-500' },
            ]" :key="m.label">
              <p class="font-mono text-xl font-semibold" :class="m.color">{{ m.val }}</p>
              <p class="text-[10px] text-neutral-400 mt-0.5">{{ m.unit }}</p>
              <p class="text-[10px] text-neutral-500 font-medium">{{ m.label }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Ingredients -->
      <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-neutral-100">
          <p class="text-[11px] font-semibold tracking-widest uppercase text-neutral-400">
            Ingrédients ({{ recipe.ingredients?.length ?? 0 }})
          </p>
        </div>
        <div class="divide-y divide-neutral-50">
          <div
            v-for="ing in recipe.ingredients"
            :key="ing.id"
            class="flex items-center justify-between px-4 py-3"
          >
            <div class="flex-1 min-w-0">
              <p class="text-[13px] font-semibold text-neutral-800 truncate">{{ ing.food_name }}</p>
              <p class="font-mono text-[11px] text-neutral-400">{{ ing.calories ?? 0 }} kcal</p>
            </div>
            <span class="font-mono text-xs font-semibold text-neutral-500 shrink-0 ml-3">
              {{ ing.quantity }}{{ ing.unit ?? 'g' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Instructions -->
      <div v-if="(recipe.instructions ?? []).length" class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-neutral-100">
          <p class="text-[11px] font-semibold tracking-widest uppercase text-neutral-400">
            Préparation
          </p>
        </div>
        <div class="divide-y divide-neutral-50">
          <div
            v-for="(instruction, index) in recipe.instructions"
            :key="index"
            class="flex gap-3 px-4 py-3"
          >
            <span class="w-6 h-6 rounded-full bg-green-50 text-green-600 text-xs font-bold flex items-center justify-center shrink-0">
              {{ index + 1 }}
            </span>
            <p class="text-[13px] leading-6 text-neutral-700">{{ instruction }}</p>
          </div>
        </div>
      </div>

      <!-- Total nutritional info -->
      <div class="bg-green-50 rounded-lg px-4 py-3">
        <p class="text-[10px] font-semibold tracking-widest uppercase text-green-700 mb-2">Total recette</p>
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-1">
            <Flame :size="14" class="text-orange-400" />
            <span class="font-mono text-sm font-semibold text-neutral-800">{{ recipe.total_calories }}</span>
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
