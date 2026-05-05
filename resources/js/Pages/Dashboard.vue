<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { Sun, Utensils, Apple, Moon, Plus } from 'lucide-vue-next'
import MainLayout    from '@/Layouts/MainLayout.vue'
import CalorieRing   from '@/Components/nutrition/CalorieRing.vue'
import MacroBar      from '@/Components/nutrition/MacroBar.vue'
import WaterTracker  from '@/Components/nutrition/WaterTracker.vue'
import MealSection   from '@/Components/food/MealSection.vue'
import AppButton     from '@/Components/ui/AppButton.vue'

const props = defineProps({
  goal:   { type: Object, default: () => ({ calories_goal: 2000, protein_goal: 150, carbs_goal: 250, fat_goal: 65, fiber_goal: 30, water_goal: 8 }) },
  totals: { type: Object, default: () => ({ calories: 0, protein: 0, carbs: 0, fat: 0, fiber: 0 }) },
  meals:  { type: Object, default: () => ({}) },
  water:  { type: Number, default: 0 },
  date:   { type: String, default: '' },
})

const page     = usePage()
const userName = computed(() => page.props.auth.user?.name?.split(' ')[0] ?? 'vous')

const waterGlasses = ref(props.water)

const formattedDate = computed(() => {
  if (!props.date) return ''
  return new Date(props.date).toLocaleDateString('fr-FR', {
    weekday: 'long', day: 'numeric', month: 'long',
  })
})

const remaining = computed(() =>
  Math.max(props.goal.calories_goal - props.totals.calories + 0, 0)
)

const MEAL_CONFIGS = [
  { key: 'breakfast', name: 'Petit-déjeuner', icon: Sun,      iconBg: 'bg-amber-100',  iconColor: 'text-amber-600' },
  { key: 'lunch',     name: 'Déjeuner',        icon: Utensils, iconBg: 'bg-green-50',   iconColor: 'text-green-600' },
  { key: 'snack',     name: 'Collation',        icon: Apple,    iconBg: 'bg-orange-50',  iconColor: 'text-orange-500' },
  { key: 'dinner',    name: 'Dîner',            icon: Moon,     iconBg: 'bg-neutral-100', iconColor: 'text-neutral-500' },
]

const mealItems = (key) => {
  const arr = props.meals[key]
  return Array.isArray(arr) ? arr : (arr ? Object.values(arr) : [])
}

const goToSearch = (mealType) => {
  router.visit(route('search.index'), { data: { meal: mealType } })
}

const updateWater = (glasses) => {
  waterGlasses.value = glasses
  router.patch(route('dashboard'), { water: glasses }, { preserveState: true, preserveScroll: true })
}

const removeFood = (item) => {
  router.delete(route('journal.destroy', item.id), { preserveScroll: true })
}
</script>

<template>
  <MainLayout title="Tableau de bord">
    <div class="flex flex-col min-h-full">

      <!-- ── Header ── -->
      <header class="flex items-start justify-between px-6 md:px-7 pt-6 pb-0">
        <div>
          <p class="text-xs font-semibold tracking-widest uppercase text-neutral-400 mb-1">
            {{ formattedDate }}
          </p>
          <h1 class="font-display text-[32px] leading-tight tracking-tight text-neutral-900">
            Bonjour, {{ userName }}
          </h1>
        </div>
        <AppButton @click="goToSearch(null)">
          <Plus :size="15" /> Ajouter un aliment
        </AppButton>
      </header>

      <!-- ── Main grid ── -->
      <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-5 px-6 md:px-7 py-5">

        <!-- Left column: calorie summary + macros + water -->
        <div class="flex flex-col gap-4">

          <!-- Calorie card -->
          <div class="bg-white rounded-xl shadow-md p-5">
            <p class="text-[11px] font-semibold tracking-widest uppercase text-neutral-400 mb-4">
              Calories du jour
            </p>

            <CalorieRing :eaten="totals.calories" :goal="goal.calories_goal" />

            <p class="text-center mt-3 text-xs text-neutral-400">
              <span class="font-mono font-semibold text-green-500">{{ remaining }}</span> kcal restants
            </p>

            <!-- Stats row -->
            <div class="grid grid-cols-3 gap-2 mt-4 pt-4 border-t border-neutral-100">
              <div
                v-for="s in [
                  { label: 'Objectif',  val: goal.calories_goal },
                  { label: 'Consommé', val: totals.calories },
                  { label: 'Dépensé',  val: 0 },
                ]"
                :key="s.label"
                class="text-center"
              >
                <p class="font-mono text-sm font-semibold text-neutral-800">{{ s.val.toLocaleString('fr-FR') }}</p>
                <p class="text-[10px] text-neutral-400 mt-0.5">{{ s.label }}</p>
              </div>
            </div>
          </div>

          <!-- Macros card -->
          <div class="bg-white rounded-lg shadow-sm p-4 flex flex-col gap-4">
            <p class="text-[11px] font-semibold tracking-widest uppercase text-neutral-400">Macronutriments</p>
            <MacroBar label="Protéines" :value="totals.protein" :goal="goal.protein_goal" color="green" />
            <MacroBar label="Glucides"  :value="totals.carbs"   :goal="goal.carbs_goal"   color="amber" />
            <MacroBar label="Lipides"   :value="totals.fat"     :goal="goal.fat_goal"     color="coral" />
            <MacroBar label="Fibres"    :value="totals.fiber"   :goal="goal.fiber_goal"   color="blue" />
          </div>

          <!-- Water tracker -->
          <WaterTracker :model-value="waterGlasses" :goal="goal.water_goal" @update:modelValue="updateWater" />
        </div>

        <!-- Right column: meals -->
        <div class="flex flex-col gap-4">
          <MealSection
            v-for="meal in MEAL_CONFIGS"
            :key="meal.key"
            :name="meal.name"
            :icon="meal.icon"
            :icon-bg="meal.iconBg"
            :icon-color="meal.iconColor"
            :items="mealItems(meal.key)"
            :meal-type="meal.key"
            @add-food="goToSearch"
            @remove-food="removeFood"
          />
        </div>
      </div>

    </div>
  </MainLayout>
</template>
