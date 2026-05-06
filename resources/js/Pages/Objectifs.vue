<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Target, Flame, Dumbbell, Wheat, Droplets, Scale, CircleGauge, Ruler, User } from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import AppInput from '@/Components/ui/AppInput.vue'
import AppButton from '@/Components/ui/AppButton.vue'

const props = defineProps({
    goal: {
        type: Object,
        default: () => ({
            calories_goal: 2000,
            protein_goal: 150,
            carbs_goal: 250,
            fat_goal: 65,
            fiber_goal: 30,
            water_goal: 8,
            weight_current: null,
            weight_goal: null,
            activity_level: 'moderate',
            goal_type: 'maintain',
            gender: null,
        }),
    },
})

const form = useForm({
    calories_goal: props.goal.calories_goal ?? 2000,
    protein_goal: props.goal.protein_goal ?? 150,
    carbs_goal: props.goal.carbs_goal ?? 250,
    fat_goal: props.goal.fat_goal ?? 65,
    fiber_goal: props.goal.fiber_goal ?? 30,
    water_goal: props.goal.water_goal ?? 8,
    weight_current: props.goal.weight_current ?? '',
    weight_goal: props.goal.weight_goal ?? '',
    activity_level: props.goal.activity_level ?? 'moderate',
    goal_type: props.goal.goal_type ?? 'maintain',
    gender: props.goal.gender ?? null,
    age: props.goal.age ?? '',
    height_cm: props.goal.height_cm ?? '',
})

const save = () => {
    form.put(route('goals.update'), { preserveScroll: true })
}

const ACTIVITY_LEVELS = [
    { key: 'sedentary', label: 'Sédentaire', desc: "Peu ou pas d'exercice" },
    { key: 'light', label: 'Légèrement actif', desc: '1-3 jours/semaine' },
    { key: 'moderate', label: 'Modérément actif', desc: '3-5 jours/semaine' },
    { key: 'active', label: 'Très actif', desc: '6-7 jours/semaine' },
    { key: 'very_active', label: 'Extrêmement actif', desc: 'Athlète/travail physique' },
]

const GOAL_TYPES = [
    { key: 'lose', label: 'Perdre du poids', color: 'blue' },
    { key: 'maintain', label: 'Maintenir', color: 'green' },
    { key: 'gain', label: 'Prendre du muscle', color: 'amber' },
]

const GENDERS = [
    { key: null, label: 'Non précisé' },
    { key: 'female', label: 'Femme' },
    { key: 'male', label: 'Homme' },
    { key: 'other', label: 'Autre' },
]

const activityMultiplier = computed(() => {
    return (
        {
            sedentary: 1.2,
            light: 1.375,
            moderate: 1.55,
            active: 1.725,
            very_active: 1.9,
        }[form.activity_level] ?? 1.55
    )
})

const suggestedCalories = computed(() => {
    const weight = Number(form.weight_current)
    const height = Number(form.height_cm)
    const age = Number(form.age)

    if (!weight || !height || !age) return null

    const offset = form.gender === 'male' ? 5 : form.gender === 'female' ? -161 : -78
    const bmr = 10 * weight + 6.25 * height - 5 * age + offset
    const goalDelta = form.goal_type === 'lose' ? -400 : form.goal_type === 'gain' ? 250 : 0

    return Math.max(500, Math.round(bmr * activityMultiplier.value + goalDelta))
})

const applySuggestion = () => {
    if (!suggestedCalories.value) return

    const weight = Number(form.weight_current) || 70
    const protein = Math.round(weight * (form.goal_type === 'gain' ? 2 : 1.8))
    const fat = Math.round(weight * 0.8)
    const carbCalories = Math.max(suggestedCalories.value - protein * 4 - fat * 9, 0)

    form.calories_goal = suggestedCalories.value
    form.protein_goal = protein
    form.fat_goal = fat
    form.carbs_goal = Math.round(carbCalories / 4)
}
</script>

<template>
    <MainLayout title="Objectifs">
        <div class="flex max-w-2xl flex-col gap-6 px-6 py-6 md:px-7">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-neutral-400">
                        Personnalisation
                    </p>
                    <h1 class="font-display text-[28px] leading-tight tracking-tight text-neutral-900">Objectifs</h1>
                </div>
                <AppButton :loading="form.processing" @click="save"> Enregistrer </AppButton>
            </div>

            <!-- Success toast -->
            <Transition name="fade">
                <div
                    v-if="form.recentlySuccessful"
                    class="flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-medium text-green-700"
                >
                    <Target :size="16" /> Objectifs enregistrés avec succès
                </div>
            </Transition>

            <!-- Goal type -->
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <p class="mb-4 text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                    Objectif principal
                </p>
                <div class="grid grid-cols-3 gap-3">
                    <button
                        v-for="g in GOAL_TYPES"
                        :key="g.key"
                        @click="form.goal_type = g.key"
                        :aria-pressed="form.goal_type === g.key"
                        class="flex flex-col items-center rounded-lg border-2 px-3 py-4 text-sm font-semibold transition-all"
                        :class="
                            form.goal_type === g.key
                                ? 'border-green-500 bg-green-50 text-green-700'
                                : 'border-neutral-200 text-neutral-500 hover:border-neutral-300'
                        "
                    >
                        {{ g.label }}
                    </button>
                </div>
            </div>

            <!-- Calories & macros -->
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <p class="mb-4 text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                    Calories & Macros
                </p>
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-orange-50">
                            <Flame :size="16" class="text-orange-500" />
                        </div>
                        <AppInput
                            v-model.number="form.calories_goal"
                            label="Calories / jour"
                            type="number"
                            min="500"
                            max="10000"
                            placeholder="2000"
                            :error="form.errors.calories_goal"
                            class="flex-1"
                        />
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-green-50">
                            <Dumbbell :size="16" class="text-green-600" />
                        </div>
                        <AppInput
                            v-model.number="form.protein_goal"
                            label="Protéines (g/j)"
                            type="number"
                            min="0"
                            max="500"
                            placeholder="150"
                            :error="form.errors.protein_goal"
                            class="flex-1"
                        />
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-amber-100">
                            <Wheat :size="16" class="text-amber-600" />
                        </div>
                        <AppInput
                            v-model.number="form.carbs_goal"
                            label="Glucides (g/j)"
                            type="number"
                            min="0"
                            max="1000"
                            placeholder="250"
                            :error="form.errors.carbs_goal"
                            class="flex-1"
                        />
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-orange-50">
                            <Flame :size="16" class="text-orange-500" />
                        </div>
                        <AppInput
                            v-model.number="form.fat_goal"
                            label="Lipides (g/j)"
                            type="number"
                            min="0"
                            max="300"
                            placeholder="65"
                            :error="form.errors.fat_goal"
                            class="flex-1"
                        />
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-blue-100">
                            <Wheat :size="16" class="text-blue-500" />
                        </div>
                        <AppInput
                            v-model.number="form.fiber_goal"
                            label="Fibres (g/j)"
                            type="number"
                            min="0"
                            max="200"
                            placeholder="30"
                            :error="form.errors.fiber_goal"
                            class="flex-1"
                        />
                    </div>
                </div>
            </div>

            <!-- Poids & eau -->
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <p class="mb-4 text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                    Poids & Hydratation
                </p>
                <div class="grid grid-cols-2 gap-4">
                    <AppInput
                        v-model.number="form.weight_current"
                        label="Poids actuel (kg)"
                        type="number"
                        min="20"
                        max="500"
                        step="0.1"
                        placeholder="70"
                        :icon="Scale"
                        :error="form.errors.weight_current"
                    />
                    <AppInput
                        v-model.number="form.weight_goal"
                        label="Poids cible (kg)"
                        type="number"
                        min="20"
                        max="500"
                        step="0.1"
                        placeholder="65"
                        :icon="Scale"
                        :error="form.errors.weight_goal"
                    />
                    <AppInput
                        v-model.number="form.age"
                        label="Âge"
                        type="number"
                        min="10"
                        max="120"
                        placeholder="30"
                        :icon="User"
                        :error="form.errors.age"
                    />
                    <AppInput
                        v-model.number="form.height_cm"
                        label="Taille (cm)"
                        type="number"
                        min="80"
                        max="250"
                        placeholder="175"
                        :icon="Ruler"
                        :error="form.errors.height_cm"
                    />
                    <AppInput
                        v-model.number="form.water_goal"
                        label="Eau (verres/j)"
                        type="number"
                        min="0"
                        max="30"
                        placeholder="8"
                        :icon="Droplets"
                        :error="form.errors.water_goal"
                    />
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold text-neutral-700">Genre</label>
                        <select
                            v-model="form.gender"
                            class="focus:ring-green-500/20 h-11 rounded-md border border-neutral-200 bg-white px-3 text-[14px] text-neutral-800 transition-all focus:border-green-400 focus:outline-none focus:ring-2"
                        >
                            <option v-for="gender in GENDERS" :key="String(gender.key)" :value="gender.key">
                                {{ gender.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.gender" class="text-xs text-red-500">{{ form.errors.gender }}</p>
                    </div>
                </div>
            </div>

            <!-- Suggestion -->
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <CircleGauge :size="16" class="text-green-600" />
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                            Calcul conseillé
                        </p>
                    </div>
                    <AppButton size="sm" variant="secondary" :disabled="!suggestedCalories" @click="applySuggestion">
                        Appliquer
                    </AppButton>
                </div>
                <p class="font-mono text-2xl font-semibold text-neutral-900">
                    {{ suggestedCalories ?? '–' }}<span class="ml-1 text-xs text-neutral-400">kcal/j</span>
                </p>
                <p class="mt-1 text-xs text-neutral-400">
                    Basé sur poids, taille, âge, activité et objectif principal.
                </p>
            </div>

            <!-- Activité -->
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <p class="mb-4 text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                    Niveau d'activité
                </p>
                <div class="flex flex-col gap-2">
                    <button
                        v-for="level in ACTIVITY_LEVELS"
                        :key="level.key"
                        @click="form.activity_level = level.key"
                        :aria-pressed="form.activity_level === level.key"
                        class="flex items-center gap-3 rounded-lg border-2 p-3 text-left transition-all"
                        :class="
                            form.activity_level === level.key
                                ? 'border-green-500 bg-green-50'
                                : 'border-neutral-100 hover:border-neutral-200'
                        "
                    >
                        <div
                            class="h-3 w-3 shrink-0 rounded-full border-2 transition-colors"
                            :class="
                                form.activity_level === level.key
                                    ? 'border-green-500 bg-green-500'
                                    : 'border-neutral-300'
                            "
                        />
                        <div>
                            <p
                                class="text-sm font-semibold"
                                :class="form.activity_level === level.key ? 'text-green-700' : 'text-neutral-800'"
                            >
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
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
