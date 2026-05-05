<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight, Plus, Minus, X, Search, ChefHat } from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import AppInput   from '@/Components/ui/AppInput.vue'
import AppButton  from '@/Components/ui/AppButton.vue'
import AppBadge   from '@/Components/ui/AppBadge.vue'

// ── Steps ─────────────────────────────────────────────────────────────────────
const step     = ref(1)
const STEPS    = ['Infos', 'Ingrédients', 'Résumé']
const canBack  = computed(() => step.value > 1)
const canNext  = computed(() => {
  if (step.value === 1) return form.name.trim().length > 0
  if (step.value === 2) return ingredients.value.length > 0
  return true
})

// ── Form state ─────────────────────────────────────────────────────────────────
const AVAILABLE_TAGS = ['Haut en protéines', 'Végétarien', 'Végan', 'Rapide', 'Sans gluten', 'Faible en calories']

const form = ref({
  name:     '',
  servings: 2,
  prepTime: 20,
  tags:     [],
})

const toggleTag = (tag) => {
  const idx = form.value.tags.indexOf(tag)
  idx === -1 ? form.value.tags.push(tag) : form.value.tags.splice(idx, 1)
}

// ── Ingredients ────────────────────────────────────────────────────────────────
const searchQuery   = ref('')
const searchResults = ref([])
const searching     = ref(false)
const ingredients   = ref([])

// Mock search — sera remplacé par Saloon / FatSecret
const MOCK_FOODS = [
  { food_id: 'f1', food_name: 'Poulet (blanc)', calories: 165, protein: 31, carbs: 0, fat: 3.6, per100g: true },
  { food_id: 'f2', food_name: 'Riz basmati cuit', calories: 130, protein: 2.7, carbs: 28, fat: 0.3, per100g: true },
  { food_id: 'f3', food_name: 'Brocoli', calories: 34, protein: 2.8, carbs: 6.6, fat: 0.4, per100g: true },
  { food_id: 'f4', food_name: 'Huile d\'olive', calories: 884, protein: 0, carbs: 0, fat: 100, per100g: true },
  { food_id: 'f5', food_name: 'Oeufs entiers', calories: 143, protein: 12.6, carbs: 0.7, fat: 9.5, per100g: true },
]

let searchTimer = null
const doSearch = () => {
  clearTimeout(searchTimer)
  if (!searchQuery.value.trim()) { searchResults.value = []; return }
  searching.value = true
  searchTimer = setTimeout(() => {
    searchResults.value = MOCK_FOODS.filter(f =>
      f.food_name.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
    searching.value = false
  }, 300)
}

const addIngredient = (food) => {
  if (ingredients.value.find(i => i.food_id === food.food_id)) return
  ingredients.value.push({ ...food, quantity: 100 })
  searchQuery.value = ''
  searchResults.value = []
}

const removeIngredient = (id) => {
  ingredients.value = ingredients.value.filter(i => i.food_id !== id)
}

const adjustQty = (ing, delta) => {
  ing.quantity = Math.max(10, ing.quantity + delta)
}

// ── Nutrition computed ──────────────────────────────────────────────────────────
const nutrition = computed(() => {
  return ingredients.value.reduce((acc, ing) => {
    const ratio = ing.quantity / 100
    acc.calories += (ing.calories ?? 0) * ratio
    acc.protein  += (ing.protein  ?? 0) * ratio
    acc.carbs    += (ing.carbs    ?? 0) * ratio
    acc.fat      += (ing.fat      ?? 0) * ratio
    return acc
  }, { calories: 0, protein: 0, carbs: 0, fat: 0 })
})

const perServing = computed(() => {
  const s = Math.max(form.value.servings, 1)
  return {
    calories: Math.round(nutrition.value.calories / s),
    protein:  +(nutrition.value.protein / s).toFixed(1),
    carbs:    +(nutrition.value.carbs   / s).toFixed(1),
    fat:      +(nutrition.value.fat     / s).toFixed(1),
  }
})

// ── Submit ─────────────────────────────────────────────────────────────────────
const saving = ref(false)
const save = () => {
  saving.value = true
  router.post(route('recipes.store'), {
    name:             form.value.name,
    servings:         form.value.servings,
    prep_time:        form.value.prepTime,
    tags:             form.value.tags,
    total_calories:   Math.round(nutrition.value.calories),
    total_protein:    +nutrition.value.protein.toFixed(2),
    total_carbs:      +nutrition.value.carbs.toFixed(2),
    total_fat:        +nutrition.value.fat.toFixed(2),
    ingredients:      ingredients.value.map(i => ({
      food_id:   i.food_id,
      food_name: i.food_name,
      quantity:  i.quantity,
      unit:      'g',
      calories:  Math.round(i.calories * i.quantity / 100),
      protein:   +(i.protein * i.quantity / 100).toFixed(2),
      carbs:     +(i.carbs   * i.quantity / 100).toFixed(2),
      fat:       +(i.fat     * i.quantity / 100).toFixed(2),
    })),
  }, {
    onFinish: () => { saving.value = false },
  })
}
</script>

<template>
  <MainLayout title="Créer une recette">
    <div class="px-6 md:px-7 py-6 max-w-2xl flex flex-col gap-5">

      <!-- Header + back -->
      <div class="flex items-center gap-3">
        <button
          @click="step > 1 ? step-- : router.visit(route('recipes.index'))"
          class="w-9 h-9 flex items-center justify-center rounded-md bg-white shadow-sm text-neutral-500 hover:text-neutral-800 transition-colors"
        >
          <ChevronLeft :size="18" />
        </button>
        <div>
          <h1 class="font-display text-[24px] leading-tight tracking-tight text-neutral-900">
            Nouvelle recette
          </h1>
        </div>
      </div>

      <!-- Step indicator -->
      <div class="flex items-center gap-2">
        <div
          v-for="(s, i) in STEPS"
          :key="s"
          class="flex items-center gap-2"
        >
          <div
            class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-colors"
            :class="step > i + 1
              ? 'bg-green-500 text-white'
              : step === i + 1
                ? 'bg-green-500 text-white'
                : 'bg-neutral-100 text-neutral-400'"
          >
            {{ i + 1 }}
          </div>
          <span class="text-xs font-medium hidden sm:block"
            :class="step === i + 1 ? 'text-neutral-800' : 'text-neutral-400'">{{ s }}</span>
          <div v-if="i < STEPS.length - 1" class="w-8 h-px bg-neutral-200" />
        </div>
      </div>

      <!-- ── Step 1 : Infos ── -->
      <div v-if="step === 1" class="flex flex-col gap-5">
        <AppInput v-model="form.name" label="Nom de la recette" placeholder="Ex : Poulet rôti aux herbes" />

        <div class="grid grid-cols-2 gap-4">
          <!-- Portions -->
          <div>
            <label class="text-sm font-semibold text-neutral-700 block mb-1.5">Portions</label>
            <div class="flex items-center gap-3 h-11 bg-white border border-neutral-200 rounded-md px-3">
              <button @click="form.servings = Math.max(1, form.servings - 1)" class="text-neutral-400 hover:text-neutral-700">
                <Minus :size="16" />
              </button>
              <span class="flex-1 text-center font-mono text-sm font-semibold">{{ form.servings }}</span>
              <button @click="form.servings++" class="text-neutral-400 hover:text-neutral-700">
                <Plus :size="16" />
              </button>
            </div>
          </div>

          <!-- Temps -->
          <div>
            <label class="text-sm font-semibold text-neutral-700 block mb-1.5">Temps (min)</label>
            <div class="flex items-center gap-3 h-11 bg-white border border-neutral-200 rounded-md px-3">
              <button @click="form.prepTime = Math.max(5, form.prepTime - 5)" class="text-neutral-400 hover:text-neutral-700">
                <Minus :size="16" />
              </button>
              <span class="flex-1 text-center font-mono text-sm font-semibold">{{ form.prepTime }}</span>
              <button @click="form.prepTime += 5" class="text-neutral-400 hover:text-neutral-700">
                <Plus :size="16" />
              </button>
            </div>
          </div>
        </div>

        <!-- Tags -->
        <div>
          <label class="text-sm font-semibold text-neutral-700 block mb-2">Tags</label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="tag in AVAILABLE_TAGS"
              :key="tag"
              @click="toggleTag(tag)"
              class="px-3 py-1.5 rounded-pill text-xs font-semibold border transition-colors"
              :class="form.tags.includes(tag)
                ? 'bg-green-500 text-white border-green-500'
                : 'bg-white text-neutral-600 border-neutral-200 hover:border-green-300'"
            >
              {{ tag }}
            </button>
          </div>
        </div>
      </div>

      <!-- ── Step 2 : Ingrédients ── -->
      <div v-else-if="step === 2" class="flex flex-col gap-4">
        <!-- Search -->
        <div class="relative">
          <AppInput
            v-model="searchQuery"
            placeholder="Rechercher un aliment…"
            :icon="Search"
            @input="doSearch"
          />
          <!-- Dropdown results -->
          <div
            v-if="searchResults.length"
            class="absolute top-full mt-1 left-0 right-0 bg-white rounded-md shadow-lg z-10 divide-y divide-neutral-50 overflow-hidden border border-neutral-100"
          >
            <button
              v-for="food in searchResults"
              :key="food.food_id"
              class="w-full flex items-center justify-between px-4 py-2.5 hover:bg-neutral-50 transition-colors text-left"
              @click="addIngredient(food)"
            >
              <span class="text-[13px] font-semibold text-neutral-800">{{ food.food_name }}</span>
              <span class="font-mono text-xs text-neutral-400">{{ food.calories }} kcal/100g</span>
            </button>
          </div>
        </div>

        <!-- Ingredients list -->
        <div v-if="ingredients.length" class="bg-white rounded-lg shadow-sm divide-y divide-neutral-50 overflow-hidden">
          <div
            v-for="ing in ingredients"
            :key="ing.food_id"
            class="flex items-center gap-3 px-4 py-3 group"
          >
            <div class="flex-1 min-w-0">
              <p class="text-[13px] font-semibold text-neutral-800 truncate">{{ ing.food_name }}</p>
              <p class="font-mono text-[11px] text-neutral-400">
                {{ Math.round(ing.calories * ing.quantity / 100) }} kcal
              </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <button @click="adjustQty(ing, -10)" class="text-neutral-300 hover:text-neutral-600"><Minus :size="13" /></button>
              <span class="font-mono text-xs w-12 text-center font-semibold">{{ ing.quantity }}g</span>
              <button @click="adjustQty(ing, +10)"  class="text-neutral-300 hover:text-neutral-600"><Plus :size="13" /></button>
              <button @click="removeIngredient(ing.food_id)" class="text-neutral-300 hover:text-red-500 ml-1"><X :size="13" /></button>
            </div>
          </div>
        </div>

        <!-- Running total -->
        <div v-if="ingredients.length" class="bg-green-50 rounded-lg px-4 py-3 flex items-center justify-between">
          <span class="text-sm font-semibold text-green-700">Total recette</span>
          <span class="font-mono text-sm font-semibold text-green-700">
            {{ Math.round(nutrition.calories) }} kcal
          </span>
        </div>

        <p v-else class="text-center py-8 text-sm text-neutral-400">
          Recherchez des aliments pour les ajouter
        </p>
      </div>

      <!-- ── Step 3 : Résumé ── -->
      <div v-else class="flex flex-col gap-4">
        <!-- Recipe card preview -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
          <div class="h-24 bg-gradient-to-br from-green-100 to-green-50 flex items-center justify-center">
            <ChefHat :size="28" class="text-green-300" />
          </div>
          <div class="p-5">
            <h2 class="font-display text-xl text-neutral-900 mb-2">{{ form.name }}</h2>
            <div class="flex flex-wrap gap-1.5 mb-4">
              <AppBadge v-for="tag in form.tags" :key="tag" color="green">{{ tag }}</AppBadge>
            </div>
            <div class="grid grid-cols-4 gap-3 text-center mb-4">
              <div v-for="m in [
                { label: 'Calories',  val: perServing.calories, unit: 'kcal' },
                { label: 'Protéines', val: perServing.protein,  unit: 'g' },
                { label: 'Glucides',  val: perServing.carbs,    unit: 'g' },
                { label: 'Lipides',   val: perServing.fat,      unit: 'g' },
              ]" :key="m.label">
                <p class="font-mono text-lg font-semibold text-neutral-900">{{ m.val }}</p>
                <p class="text-[10px] text-neutral-400">{{ m.label }}</p>
                <p class="text-[10px] text-neutral-400">par portion</p>
              </div>
            </div>
            <p class="text-xs text-neutral-400">
              {{ ingredients.length }} ingrédient{{ ingredients.length > 1 ? 's' : '' }} ·
              {{ form.prepTime }} min · {{ form.servings }} portion{{ form.servings > 1 ? 's' : '' }}
            </p>
          </div>
        </div>

        <!-- Ingredients recap -->
        <div class="bg-white rounded-lg shadow-sm divide-y divide-neutral-50 overflow-hidden">
          <div v-for="ing in ingredients" :key="ing.food_id" class="flex items-center justify-between px-4 py-2.5">
            <span class="text-[13px] text-neutral-700">{{ ing.food_name }}</span>
            <span class="font-mono text-xs text-neutral-400">{{ ing.quantity }}g</span>
          </div>
        </div>
      </div>

      <!-- Navigation buttons -->
      <div class="flex gap-3 justify-end pt-2">
        <AppButton v-if="canBack" variant="ghost" @click="step--">
          <ChevronLeft :size="16" /> Retour
        </AppButton>
        <AppButton
          v-if="step < 3"
          :disabled="!canNext"
          @click="step++"
        >
          Continuer <ChevronRight :size="16" />
        </AppButton>
        <AppButton
          v-else
          :loading="saving"
          @click="save"
        >
          Enregistrer la recette
        </AppButton>
      </div>

    </div>
  </MainLayout>
</template>
