<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { ChefHat, Plus, Clock, Users, Flame } from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import AppButton  from '@/Components/ui/AppButton.vue'
import AppBadge   from '@/Components/ui/AppBadge.vue'

const props = defineProps({
  recipes: { type: Array, default: () => [] },
})

const CATEGORY_COLORS = {
  'Haut en protéines': 'green',
  'Végétarien':        'amber',
  'Végan':             'blue',
  'Sans gluten':       'orange',
  'Rapide':            'neutral',
}

const activeFilter = ref('Tous')
const FILTERS = ['Tous', 'Haut en protéines', 'Végétarien', 'Végan', 'Rapide']

const filtered = computed(() => {
  if (activeFilter.value === 'Tous') return props.recipes
  return props.recipes.filter(r =>
    (r.tags ?? []).includes(activeFilter.value) || r.category === activeFilter.value
  )
})

const caloriesPerServing = (r) =>
  r.servings > 0 ? Math.round(r.total_calories / r.servings) : r.total_calories

const macroPercent = (kcal, total) => total > 0 ? Math.round((kcal / total) * 100) : 0

const deleteRecipe = (id) => {
  if (confirm('Supprimer cette recette ?')) {
    router.delete(route('recipes.destroy', id))
  }
}
</script>

<template>
  <MainLayout title="Recettes">
    <div class="px-6 md:px-7 py-6 flex flex-col gap-5">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs font-semibold tracking-widest uppercase text-neutral-400 mb-1">Bibliothèque</p>
          <h1 class="font-display text-[28px] leading-tight tracking-tight text-neutral-900">Recettes</h1>
        </div>
        <AppButton :href="route('recipes.create')" as="a">
          <Plus :size="15" /> Nouvelle recette
        </AppButton>
      </div>

      <!-- Filters -->
      <div class="flex flex-wrap gap-2">
        <button
          v-for="f in FILTERS"
          :key="f"
          @click="activeFilter = f"
          class="px-3 py-1.5 rounded-pill text-xs font-semibold transition-colors"
          :class="activeFilter === f
            ? 'bg-green-500 text-white'
            : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'"
        >
          {{ f }}
        </button>
      </div>

      <!-- Recipe grid -->
      <div v-if="filtered.length" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
          v-for="recipe in filtered"
          :key="recipe.id"
          class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow cursor-pointer group"
          @click="router.visit(route('recipes.show', recipe.id))"
        >
          <!-- Color placeholder (thumbnail) -->
          <div class="h-32 bg-gradient-to-br from-green-100 to-green-50 flex items-center justify-center">
            <ChefHat :size="32" class="text-green-300" />
          </div>

          <div class="p-4">
            <!-- Tags -->
            <div class="flex flex-wrap gap-1 mb-2">
              <AppBadge
                v-for="tag in (recipe.tags ?? []).slice(0, 2)"
                :key="tag"
                :color="CATEGORY_COLORS[tag] ?? 'neutral'"
              >
                {{ tag }}
              </AppBadge>
            </div>

            <h3 class="text-sm font-bold text-neutral-800 mb-2 group-hover:text-green-600 transition-colors">
              {{ recipe.name }}
            </h3>

            <!-- Meta: time + servings -->
            <div class="flex items-center gap-3 text-xs text-neutral-400 mb-3">
              <span class="flex items-center gap-1"><Clock :size="12" /> {{ recipe.prep_time }} min</span>
              <span class="flex items-center gap-1"><Users :size="12" /> {{ recipe.servings }} portion{{ recipe.servings > 1 ? 's' : '' }}</span>
            </div>

            <!-- Macros bar -->
            <div class="flex items-center gap-1.5 mb-2">
              <div
                v-for="m in [
                  { label: 'P', val: (recipe.total_protein ?? 0) * 4, color: 'bg-green-400'  },
                  { label: 'G', val: (recipe.total_carbs ?? 0) * 4,   color: 'bg-amber-400'  },
                  { label: 'L', val: (recipe.total_fat ?? 0) * 9,     color: 'bg-coral-400'  },
                ]"
                :key="m.label"
                class="h-1.5 rounded-full transition-all"
                :class="m.color"
                :style="{ width: `${macroPercent(m.val, recipe.total_calories * 4 || 1)}%`, minWidth: '4px' }"
              />
            </div>

            <!-- Calories + actions -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-1">
                <Flame :size="13" class="text-orange-400" />
                <span class="font-mono text-sm font-semibold text-neutral-800">
                  {{ caloriesPerServing(recipe) }}
                </span>
                <span class="text-xs text-neutral-400">kcal / portion</span>
              </div>
              <button
                @click.stop="deleteRecipe(recipe.id)"
                class="text-xs text-neutral-400 hover:text-red-500 transition-colors px-2 py-1 rounded"
              >
                Supprimer
              </button>
            </div>
          </div>
        </div>

        <!-- Create card -->
        <div
          class="border-2 border-dashed border-neutral-200 rounded-lg flex flex-col items-center justify-center py-10 gap-3 cursor-pointer hover:border-green-300 hover:bg-green-50/50 transition-all group"
          @click="router.visit(route('recipes.create'))"
        >
          <div class="w-10 h-10 rounded-full bg-neutral-100 group-hover:bg-green-100 flex items-center justify-center transition-colors">
            <Plus :size="20" class="text-neutral-400 group-hover:text-green-600" />
          </div>
          <p class="text-sm font-semibold text-neutral-400 group-hover:text-green-600 transition-colors">
            Créer une recette
          </p>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="text-center py-16 text-neutral-400">
        <ChefHat :size="40" class="mx-auto mb-4 opacity-20" />
        <p class="text-sm font-medium">Aucune recette pour l'instant</p>
        <AppButton class="mt-4" @click="router.visit(route('recipes.create'))">
          <Plus :size="15" /> Créer ma première recette
        </AppButton>
      </div>

    </div>
  </MainLayout>
</template>
