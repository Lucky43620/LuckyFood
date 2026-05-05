<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { ChevronLeft, ChevronRight, Plus, Minus, X, Search, ChefHat, Loader2 } from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import AppInput from '@/Components/ui/AppInput.vue'
import AppButton from '@/Components/ui/AppButton.vue'
import AppBadge from '@/Components/ui/AppBadge.vue'

const step = ref(1)
const STEPS = ['Infos', 'Ingrédients', 'Préparation', 'Résumé']
const canBack = computed(() => step.value > 1)

const AVAILABLE_TAGS = ['Haut en protéines', 'Végétarien', 'Végan', 'Rapide', 'Sans gluten', 'Faible en calories']

const form = ref({
  name: '',
  servings: 2,
  prepTime: 20,
  tags: [],
})

const searchQuery = ref('')
const searchResults = ref([])
const searchError = ref(null)
const searching = ref(false)
const ingredients = ref([])
const instructions = ref([{ id: 1, text: '' }])
let nextInstructionId = 2

const normalizedInstructions = computed(() => instructions.value
  .map(instruction => String(instruction.text ?? '').trim())
  .filter(Boolean))

const canNext = computed(() => {
  if (step.value === 1) return String(form.value.name ?? '').trim().length > 0
  if (step.value === 2) return ingredients.value.length > 0
  if (step.value === 3) return normalizedInstructions.value.length > 0
  return true
})

const toggleTag = (tag) => {
  const index = form.value.tags.indexOf(tag)
  index === -1 ? form.value.tags.push(tag) : form.value.tags.splice(index, 1)
}

const toNumber = (value) => {
  const number = Number(value)
  return Number.isFinite(number) ? number : 0
}

let searchTimer = null
let searchRequestId = 0
watch(searchQuery, (value) => {
  clearTimeout(searchTimer)
  searchError.value = null

  const query = value.trim()
  if (query.length < 2) {
    searchResults.value = []
    searching.value = false
    return
  }

  searching.value = true
  searchTimer = setTimeout(async () => {
    const requestId = ++searchRequestId

    try {
      const { data } = await axios.get(route('recipes.ingredients.search'), {
        params: { q: query },
      })

      if (requestId !== searchRequestId) return

      searchResults.value = data.results ?? []
      searchError.value = data.error?.message ?? null
    } catch {
      if (requestId !== searchRequestId) return
      searchResults.value = []
      searchError.value = 'La recherche est indisponible.'
    } finally {
      if (requestId === searchRequestId) {
        searching.value = false
      }
    }
  }, 350)
})

const addIngredient = (food) => {
  if (ingredients.value.find(ingredient => ingredient.food_id === food.food_id)) return

  ingredients.value.push({
    food_id: food.food_id,
    food_name: food.food_name,
    serving_description: food.serving_description,
    quantity: 100,
    unit: 'g',
    calories: toNumber(food.calories),
    protein: toNumber(food.protein),
    carbs: toNumber(food.carbs),
    fat: toNumber(food.fat),
  })

  searchQuery.value = ''
  searchResults.value = []
}

const removeIngredient = (id) => {
  ingredients.value = ingredients.value.filter(ingredient => ingredient.food_id !== id)
}

const adjustQty = (ingredient, delta) => {
  ingredient.quantity = Math.max(1, toNumber(ingredient.quantity) + delta)
}

const setQty = (ingredient, value) => {
  ingredient.quantity = Math.max(1, toNumber(value))
}

const addInstruction = () => {
  instructions.value.push({ id: nextInstructionId++, text: '' })
}

const removeInstruction = (id) => {
  if (instructions.value.length === 1) {
    instructions.value[0].text = ''
    return
  }

  instructions.value = instructions.value.filter(instruction => instruction.id !== id)
}

const ingredientNutrition = (ingredient, key) => {
  const value = toNumber(ingredient[key]) * toNumber(ingredient.quantity) / 100
  return key === 'calories' ? Math.round(value) : +value.toFixed(1)
}

const nutrition = computed(() => {
  return ingredients.value.reduce((acc, ingredient) => {
    const ratio = toNumber(ingredient.quantity) / 100
    acc.calories += toNumber(ingredient.calories) * ratio
    acc.protein += toNumber(ingredient.protein) * ratio
    acc.carbs += toNumber(ingredient.carbs) * ratio
    acc.fat += toNumber(ingredient.fat) * ratio
    return acc
  }, { calories: 0, protein: 0, carbs: 0, fat: 0 })
})

const perServing = computed(() => {
  const servings = Math.max(toNumber(form.value.servings), 1)

  return {
    calories: Math.round(nutrition.value.calories / servings),
    protein: +(nutrition.value.protein / servings).toFixed(1),
    carbs: +(nutrition.value.carbs / servings).toFixed(1),
    fat: +(nutrition.value.fat / servings).toFixed(1),
  }
})

const saving = ref(false)
const save = () => {
  saving.value = true

  router.post(route('recipes.store'), {
    name: form.value.name,
    servings: form.value.servings,
    prep_time: form.value.prepTime,
    tags: form.value.tags,
    instructions: normalizedInstructions.value,
    total_calories: Math.round(nutrition.value.calories),
    total_protein: +nutrition.value.protein.toFixed(2),
    total_carbs: +nutrition.value.carbs.toFixed(2),
    total_fat: +nutrition.value.fat.toFixed(2),
    ingredients: ingredients.value.map(ingredient => ({
      food_id: ingredient.food_id,
      food_name: ingredient.food_name,
      quantity: toNumber(ingredient.quantity),
      unit: ingredient.unit ?? 'g',
      calories: ingredientNutrition(ingredient, 'calories'),
      protein: ingredientNutrition(ingredient, 'protein'),
      carbs: ingredientNutrition(ingredient, 'carbs'),
      fat: ingredientNutrition(ingredient, 'fat'),
    })),
  }, {
    onFinish: () => { saving.value = false },
  })
}
</script>

<template>
  <MainLayout title="Créer une recette">
    <div class="px-6 md:px-7 py-6 max-w-3xl flex flex-col gap-5">
      <div class="flex items-center gap-3">
        <button
          @click="step > 1 ? step-- : router.visit(route('recipes.index'))"
          class="w-9 h-9 flex items-center justify-center rounded-md bg-white shadow-sm text-neutral-500 hover:text-neutral-800 transition-colors"
          aria-label="Retour"
        >
          <ChevronLeft :size="18" />
        </button>
        <div>
          <p class="text-xs font-semibold tracking-widest uppercase text-neutral-400 mb-0.5">Recette</p>
          <h1 class="font-display text-[24px] leading-tight tracking-tight text-neutral-900">
            Nouvelle recette
          </h1>
        </div>
      </div>

      <div class="flex items-center gap-2 overflow-x-auto pb-1">
        <div
          v-for="(label, index) in STEPS"
          :key="label"
          class="flex items-center gap-2 shrink-0"
        >
          <div
            class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-colors"
            :class="step >= index + 1 ? 'bg-green-500 text-white' : 'bg-neutral-100 text-neutral-400'"
          >
            {{ index + 1 }}
          </div>
          <span
            class="text-xs font-medium hidden sm:block"
            :class="step === index + 1 ? 'text-neutral-800' : 'text-neutral-400'"
          >
            {{ label }}
          </span>
          <div v-if="index < STEPS.length - 1" class="w-8 h-px bg-neutral-200" />
        </div>
      </div>

      <div v-if="step === 1" class="flex flex-col gap-5">
        <AppInput v-model="form.name" label="Nom de la recette" placeholder="Ex : Poulet rôti aux herbes" />

        <div class="grid grid-cols-2 gap-4">
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

      <div v-else-if="step === 2" class="flex flex-col gap-4">
        <div class="relative">
          <AppInput
            v-model="searchQuery"
            placeholder="Rechercher un aliment FatSecret"
            :icon="searching ? Loader2 : Search"
          />

          <div
            v-if="searching || searchResults.length || searchError || searchQuery.trim().length >= 2"
            class="absolute top-full mt-1 left-0 right-0 bg-white rounded-md shadow-lg z-10 overflow-hidden border border-neutral-100"
          >
            <div v-if="searching" class="px-4 py-3 flex items-center gap-2 text-sm text-neutral-500">
              <Loader2 :size="15" class="animate-spin" />
              Recherche en cours
            </div>

            <template v-else-if="searchResults.length">
              <button
                v-for="food in searchResults"
                :key="food.food_id"
                class="w-full flex items-start justify-between gap-3 px-4 py-3 hover:bg-neutral-50 transition-colors text-left border-b border-neutral-50 last:border-b-0"
                @click="addIngredient(food)"
              >
                <span class="min-w-0">
                  <span class="block text-[13px] font-semibold text-neutral-800 truncate">{{ food.food_name }}</span>
                  <span class="block text-[11px] text-neutral-400 truncate">{{ food.serving_description || 'Portion FatSecret' }}</span>
                </span>
                <span class="font-mono text-xs text-neutral-500 shrink-0">{{ Math.round(food.calories ?? 0) }} kcal</span>
              </button>
            </template>

            <div v-else-if="searchError" class="px-4 py-3 text-sm font-medium text-amber-700 bg-amber-50">
              {{ searchError }}
            </div>

            <div v-else class="px-4 py-3 text-sm text-neutral-400">
              Aucun aliment trouvé
            </div>
          </div>
        </div>

        <div v-if="ingredients.length" class="bg-white rounded-lg shadow-sm divide-y divide-neutral-50 overflow-hidden">
          <div
            v-for="ingredient in ingredients"
            :key="ingredient.food_id"
            class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center"
          >
            <div class="flex-1 min-w-0">
              <p class="text-[13px] font-semibold text-neutral-800 truncate">{{ ingredient.food_name }}</p>
              <p class="font-mono text-[11px] text-neutral-400">
                {{ ingredientNutrition(ingredient, 'calories') }} kcal ·
                P {{ ingredientNutrition(ingredient, 'protein') }}g ·
                G {{ ingredientNutrition(ingredient, 'carbs') }}g ·
                L {{ ingredientNutrition(ingredient, 'fat') }}g
              </p>
              <p class="text-[11px] text-neutral-400 truncate">{{ ingredient.serving_description || 'Base FatSecret' }}</p>
            </div>

            <div class="flex items-center justify-between gap-2 shrink-0">
              <button @click="adjustQty(ingredient, -10)" class="h-8 w-8 flex items-center justify-center text-neutral-300 hover:text-neutral-600">
                <Minus :size="13" />
              </button>
              <input
                type="number"
                min="1"
                :value="ingredient.quantity"
                @input="setQty(ingredient, $event.target.value)"
                class="h-8 w-20 rounded-md border border-neutral-200 bg-white text-center font-mono text-xs font-semibold text-neutral-700 focus:border-green-400 focus:ring-green-500/20"
              >
              <span class="font-mono text-xs font-semibold text-neutral-500">g</span>
              <button @click="adjustQty(ingredient, 10)" class="h-8 w-8 flex items-center justify-center text-neutral-300 hover:text-neutral-600">
                <Plus :size="13" />
              </button>
              <button @click="removeIngredient(ingredient.food_id)" class="h-8 w-8 flex items-center justify-center text-neutral-300 hover:text-red-500">
                <X :size="13" />
              </button>
            </div>
          </div>
        </div>

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

      <div v-else-if="step === 3" class="flex flex-col gap-4">
        <div
          v-for="(instruction, index) in instructions"
          :key="instruction.id"
          class="bg-white rounded-lg shadow-sm p-4 flex gap-3"
        >
          <span class="w-7 h-7 rounded-full bg-green-50 text-green-600 text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">
            {{ index + 1 }}
          </span>
          <div class="flex-1">
            <label class="text-sm font-semibold text-neutral-700 block mb-1.5">
              Étape {{ index + 1 }}
            </label>
            <textarea
              v-model="instruction.text"
              rows="3"
              class="w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-800 placeholder-neutral-400 focus:border-green-400 focus:ring-2 focus:ring-green-500/20"
              placeholder="Ex : Saisir le poulet 4 minutes de chaque côté, puis laisser reposer."
            />
          </div>
          <button
            @click="removeInstruction(instruction.id)"
            class="h-8 w-8 flex items-center justify-center text-neutral-300 hover:text-red-500"
            aria-label="Supprimer l'étape"
          >
            <X :size="14" />
          </button>
        </div>

        <AppButton variant="secondary" class="self-start" @click="addInstruction">
          <Plus :size="15" /> Ajouter une étape
        </AppButton>
      </div>

      <div v-else class="flex flex-col gap-4">
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
              <div v-for="macro in [
                { label: 'Calories', val: perServing.calories, unit: 'kcal' },
                { label: 'Protéines', val: perServing.protein, unit: 'g' },
                { label: 'Glucides', val: perServing.carbs, unit: 'g' },
                { label: 'Lipides', val: perServing.fat, unit: 'g' },
              ]" :key="macro.label">
                <p class="font-mono text-lg font-semibold text-neutral-900">{{ macro.val }}</p>
                <p class="text-[10px] text-neutral-400">{{ macro.label }}</p>
                <p class="text-[10px] text-neutral-400">par portion</p>
              </div>
            </div>
            <p class="text-xs text-neutral-400">
              {{ ingredients.length }} ingrédient{{ ingredients.length > 1 ? 's' : '' }} ·
              {{ normalizedInstructions.length }} étape{{ normalizedInstructions.length > 1 ? 's' : '' }} ·
              {{ form.prepTime }} min · {{ form.servings }} portion{{ form.servings > 1 ? 's' : '' }}
            </p>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm divide-y divide-neutral-50 overflow-hidden">
          <div v-for="ingredient in ingredients" :key="ingredient.food_id" class="flex items-center justify-between px-4 py-2.5">
            <span class="text-[13px] text-neutral-700">{{ ingredient.food_name }}</span>
            <span class="font-mono text-xs text-neutral-400">{{ ingredient.quantity }}g</span>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm divide-y divide-neutral-50 overflow-hidden">
          <div v-for="(instruction, index) in normalizedInstructions" :key="index" class="flex gap-3 px-4 py-3">
            <span class="w-6 h-6 rounded-full bg-green-50 text-green-600 text-xs font-bold flex items-center justify-center shrink-0">
              {{ index + 1 }}
            </span>
            <p class="text-[13px] leading-6 text-neutral-700">{{ instruction }}</p>
          </div>
        </div>
      </div>

      <div class="flex gap-3 justify-end pt-2">
        <AppButton v-if="canBack" variant="ghost" @click="step--">
          <ChevronLeft :size="16" /> Retour
        </AppButton>
        <AppButton
          v-if="step < STEPS.length"
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
