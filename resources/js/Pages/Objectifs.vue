<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Target, Flame, Dumbbell, Wheat, Droplets, Scale } from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import AppInput   from '@/Components/ui/AppInput.vue'
import AppButton  from '@/Components/ui/AppButton.vue'

const props = defineProps({
  goal: {
    type: Object,
    default: () => ({
      calories_goal: 2000, protein_goal: 150, carbs_goal: 250, fat_goal: 65,
      fiber_goal: 30, water_goal: 8, weight_current: null, weight_goal: null,
      activity_level: 'moderate', goal_type: 'maintain', gender: null,
    }),
  },
})

const form = ref({
  calories_goal:  props.goal.calories_goal  ?? 2000,
  protein_goal:   props.goal.protein_goal   ?? 150,
  carbs_goal:     props.goal.carbs_goal     ?? 250,
  fat_goal:       props.goal.fat_goal       ?? 65,
  fiber_goal:     props.goal.fiber_goal     ?? 30,
  water_goal:     props.goal.water_goal     ?? 8,
  weight_current: props.goal.weight_current ?? '',
  weight_goal:    props.goal.weight_goal    ?? '',
  activity_level: props.goal.activity_level ?? 'moderate',
  goal_type:      props.goal.goal_type      ?? 'maintain',
  gender:         props.goal.gender         ?? null,
})

const saving  = ref(false)
const success  = ref(false)

const save = () => {
  saving.value = true
  router.put(route('goals.update'), form.value, {
    onSuccess: () => { success.value = true; setTimeout(() => { success.value = false }, 3000) },
    onFinish:  () => { saving.value = false },
  })
}

const ACTIVITY_LEVELS = [
  { key: 'sedentary',  label: 'Sédentaire',           desc: 'Peu ou pas d\'exercice' },
  { key: 'light',      label: 'Légèrement actif',     desc: '1-3 jours/semaine' },
  { key: 'moderate',   label: 'Modérément actif',     desc: '3-5 jours/semaine' },
  { key: 'active',     label: 'Très actif',           desc: '6-7 jours/semaine' },
  { key: 'very_active',label: 'Extrêmement actif',    desc: 'Athlète/travail physique' },
]

const GOAL_TYPES = [
  { key: 'lose',     label: 'Perdre du poids',     color: 'blue'   },
  { key: 'maintain', label: 'Maintenir',            color: 'green'  },
  { key: 'gain',     label: 'Prendre du muscle',   color: 'amber'  },
]
</script>

<template>
  <MainLayout title="Objectifs">
    <div class="px-6 md:px-7 py-6 max-w-2xl flex flex-col gap-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs font-semibold tracking-widest uppercase text-neutral-400 mb-1">Personnalisation</p>
          <h1 class="font-display text-[28px] leading-tight tracking-tight text-neutral-900">Objectifs</h1>
        </div>
        <AppButton :loading="saving" @click="save">
          Enregistrer
        </AppButton>
      </div>

      <!-- Success toast -->
      <Transition name="fade">
        <div v-if="success" class="bg-green-50 border border-green-200 text-green-700 text-sm font-medium px-4 py-2.5 rounded-lg flex items-center gap-2">
          <Target :size="16" /> Objectifs enregistrés avec succès
        </div>
      </Transition>

      <!-- Goal type -->
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-[11px] font-semibold tracking-widest uppercase text-neutral-400 mb-4">Objectif principal</p>
        <div class="grid grid-cols-3 gap-3">
          <button
            v-for="g in GOAL_TYPES"
            :key="g.key"
            @click="form.goal_type = g.key"
            class="flex flex-col items-center py-4 px-3 rounded-lg border-2 transition-all text-sm font-semibold"
            :class="form.goal_type === g.key
              ? 'border-green-500 bg-green-50 text-green-700'
              : 'border-neutral-200 text-neutral-500 hover:border-neutral-300'"
          >
            {{ g.label }}
          </button>
        </div>
      </div>

      <!-- Calories & macros -->
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-[11px] font-semibold tracking-widest uppercase text-neutral-400 mb-4">Calories & Macros</p>
        <div class="flex flex-col gap-4">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-md bg-orange-50 flex items-center justify-center shrink-0">
              <Flame :size="16" class="text-orange-500" />
            </div>
            <AppInput v-model.number="form.calories_goal" label="Calories / jour" type="number" placeholder="2000" class="flex-1" />
          </div>
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-md bg-green-50 flex items-center justify-center shrink-0">
              <Dumbbell :size="16" class="text-green-600" />
            </div>
            <AppInput v-model.number="form.protein_goal" label="Protéines (g/j)" type="number" placeholder="150" class="flex-1" />
          </div>
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-md bg-amber-100 flex items-center justify-center shrink-0">
              <Wheat :size="16" class="text-amber-600" />
            </div>
            <AppInput v-model.number="form.carbs_goal" label="Glucides (g/j)" type="number" placeholder="250" class="flex-1" />
          </div>
        </div>
      </div>

      <!-- Poids & eau -->
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-[11px] font-semibold tracking-widest uppercase text-neutral-400 mb-4">Poids & Hydratation</p>
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model.number="form.weight_current" label="Poids actuel (kg)" type="number" placeholder="70" :icon="Scale" />
          <AppInput v-model.number="form.weight_goal"    label="Poids cible (kg)"  type="number" placeholder="65" :icon="Scale" />
          <AppInput v-model.number="form.water_goal"     label="Eau (verres/j)"    type="number" placeholder="8"  :icon="Droplets" />
        </div>
      </div>

      <!-- Activité -->
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-[11px] font-semibold tracking-widest uppercase text-neutral-400 mb-4">Niveau d'activité</p>
        <div class="flex flex-col gap-2">
          <button
            v-for="level in ACTIVITY_LEVELS"
            :key="level.key"
            @click="form.activity_level = level.key"
            class="flex items-center gap-3 p-3 rounded-lg border-2 transition-all text-left"
            :class="form.activity_level === level.key
              ? 'border-green-500 bg-green-50'
              : 'border-neutral-100 hover:border-neutral-200'"
          >
            <div
              class="w-3 h-3 rounded-full border-2 shrink-0 transition-colors"
              :class="form.activity_level === level.key
                ? 'border-green-500 bg-green-500'
                : 'border-neutral-300'"
            />
            <div>
              <p class="text-sm font-semibold" :class="form.activity_level === level.key ? 'text-green-700' : 'text-neutral-800'">
                {{ level.label }}
              </p>
              <p class="text-xs text-neutral-400">{{ level.desc }}</p>
            </div>
          </button>
        </div>
      </div>

    </div>
  </MainLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
