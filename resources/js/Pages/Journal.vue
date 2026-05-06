<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight, RotateCcw, X } from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import MacroBar from '@/Components/nutrition/MacroBar.vue'
import FoodItem from '@/Components/food/FoodItem.vue'
import AppButton from '@/Components/ui/AppButton.vue'
import { MEAL_CONFIGS, MEALS } from '@/constants/nutrition'

const props = defineProps({
    date: { type: String, required: true },
    entries: { type: Array, default: () => [] },
    totals: { type: Object, default: () => ({ calories: 0, protein: 0, carbs: 0, fat: 0, fiber: 0 }) },
    goal: { type: Object, default: () => ({ calories_goal: 2000, protein_goal: 150, carbs_goal: 250, fat_goal: 65 }) },
})

const formattedDate = computed(() =>
    new Date(props.date).toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }),
)

const isToday = computed(() => props.date === new Date().toISOString().split('T')[0])

const navigate = (offset) => {
    const d = new Date(props.date)
    d.setDate(d.getDate() + offset)
    router.visit(route('journal.index'), { data: { date: d.toISOString().split('T')[0] } })
}

const byMeal = (key) => props.entries.filter((e) => e.meal_type === key)
const entryHref = (entry, mealKey) => {
    const id = String(entry.food_id ?? '')

    if (id.startsWith('manual:') || id.startsWith('recipe:')) return null

    return route('search.show', { foodId: entry.food_id, from: 'journal', meal: mealKey })
}
const editingEntry = ref(null)
const editForm = ref({})

const openEdit = (entry) => {
    editingEntry.value = entry
    editForm.value = {
        date: entry.date,
        food_id: entry.food_id,
        food_name: entry.food_name,
        meal_type: entry.meal_type,
        serving_description: entry.serving_description ?? '',
        quantity: Number(entry.quantity ?? 1),
        calories: Number(entry.calories ?? 0),
        protein: Number(entry.protein ?? 0),
        carbs: Number(entry.carbs ?? 0),
        fat: Number(entry.fat ?? 0),
        fiber: Number(entry.fiber ?? 0),
    }
}

const closeEdit = () => {
    editingEntry.value = null
    editForm.value = {}
}

const submitEdit = () => {
    if (!editingEntry.value) return

    router.put(route('journal.update', editingEntry.value.id), editForm.value, {
        preserveScroll: true,
        onSuccess: closeEdit,
    })
}

const removeEntry = (id) => {
    router.delete(route('journal.destroy', id), { preserveScroll: true })
}

const repeatYesterday = () => {
    router.post(route('journal.repeat-yesterday'), { date: props.date }, { preserveScroll: true })
}
</script>

<template>
    <MainLayout title="Journal alimentaire">
        <div class="flex flex-col gap-5 px-6 py-6 md:px-7">
            <!-- Header with date navigation -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-neutral-400">Journal</p>
                    <h1 class="font-display text-[28px] capitalize leading-tight tracking-tight text-neutral-900">
                        {{ isToday ? "Aujourd'hui" : formattedDate }}
                    </h1>
                </div>

                <div class="flex items-center gap-2">
                    <AppButton size="sm" variant="secondary" @click="repeatYesterday">
                        <RotateCcw :size="14" />
                        Hier
                    </AppButton>
                    <button
                        @click="navigate(-1)"
                        class="flex h-9 w-9 items-center justify-center rounded-md bg-white text-neutral-500 shadow-sm transition-colors hover:text-neutral-800"
                    >
                        <ChevronLeft :size="18" />
                    </button>
                    <span class="hidden text-sm font-medium text-neutral-600 sm:block">
                        {{ new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) }}
                    </span>
                    <button
                        @click="navigate(1)"
                        :disabled="isToday"
                        class="flex h-9 w-9 items-center justify-center rounded-md bg-white text-neutral-500 shadow-sm transition-colors hover:text-neutral-800 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <ChevronRight :size="18" />
                    </button>
                </div>
            </div>

            <!-- Daily total card -->
            <div class="rounded-xl bg-white p-5 shadow-md">
                <p class="mb-4 text-[11px] font-semibold uppercase tracking-widest text-neutral-400">Total du jour</p>
                <div class="mb-5 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div
                        v-for="s in [
                            { label: 'Calories', val: totals.calories, unit: 'kcal', color: 'text-neutral-900' },
                            { label: 'Protéines', val: totals.protein, unit: 'g', color: 'text-green-600' },
                            { label: 'Glucides', val: totals.carbs, unit: 'g', color: 'text-amber-600' },
                            { label: 'Lipides', val: totals.fat, unit: 'g', color: 'text-coral-500' },
                        ]"
                        :key="s.label"
                    >
                        <p class="mb-1 text-[11px] font-medium text-neutral-400">{{ s.label }}</p>
                        <p class="font-mono text-xl font-semibold" :class="s.color">
                            {{ s.val }}<span class="ml-1 text-xs text-neutral-400">{{ s.unit }}</span>
                        </p>
                    </div>
                </div>
                <div class="flex flex-col gap-3">
                    <MacroBar label="Protéines" :value="totals.protein" :goal="goal.protein_goal" color="green" />
                    <MacroBar label="Glucides" :value="totals.carbs" :goal="goal.carbs_goal" color="amber" />
                    <MacroBar label="Lipides" :value="totals.fat" :goal="goal.fat_goal" color="coral" />
                </div>
            </div>

            <!-- Meals -->
            <div v-for="meal in MEAL_CONFIGS" :key="meal.key" class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-neutral-100 px-4 py-3">
                    <div class="flex items-center gap-2.5">
                        <component :is="meal.icon" :size="16" class="text-neutral-500" />
                        <span class="text-sm font-bold text-neutral-800">{{ meal.name }}</span>
                        <span class="font-mono text-[11px] text-neutral-400">
                            {{ byMeal(meal.key).reduce((s, e) => s + e.calories, 0) }} kcal
                        </span>
                    </div>
                    <AppButton
                        size="sm"
                        variant="secondary"
                        @click="router.visit(route('search.index'), { data: { meal: meal.key } })"
                    >
                        + Ajouter
                    </AppButton>
                </div>

                <div v-if="byMeal(meal.key).length">
                    <FoodItem
                        v-for="(entry, idx) in byMeal(meal.key)"
                        :key="entry.id"
                        :name="entry.food_name"
                        :serving="entry.serving_description"
                        :kcal="entry.calories"
                        :protein="entry.protein"
                        :carbs="entry.carbs"
                        :fat="entry.fat"
                        :href="entryHref(entry, meal.key)"
                        can-edit
                        :last="idx === byMeal(meal.key).length - 1"
                        @edit="openEdit(entry)"
                        @remove="removeEntry(entry.id)"
                    />
                </div>
                <p v-else class="px-4 py-3 text-xs italic text-neutral-400">Aucun aliment ajouté</p>
            </div>
        </div>

        <Transition name="fade">
            <div
                v-if="editingEntry"
                class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-950/50 p-4"
                @click.self="closeEdit"
            >
                <form class="w-full max-w-xl rounded-xl bg-white p-5 shadow-lg" @submit.prevent="submitEdit">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-neutral-400">
                                Modifier l'entrée
                            </p>
                            <h2 class="mt-1 text-lg font-bold text-neutral-900">{{ editForm.food_name }}</h2>
                        </div>
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-md text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700"
                            @click="closeEdit"
                        >
                            <X :size="16" />
                        </button>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex flex-col gap-1.5 text-sm font-semibold text-neutral-700">
                            Nom
                            <input
                                v-model="editForm.food_name"
                                class="h-10 rounded-md border border-neutral-200 px-3 text-sm font-normal text-neutral-800 focus:border-green-400 focus:outline-none"
                            />
                        </label>
                        <label class="flex flex-col gap-1.5 text-sm font-semibold text-neutral-700">
                            Date
                            <input
                                v-model="editForm.date"
                                type="date"
                                class="h-10 rounded-md border border-neutral-200 px-3 text-sm font-normal text-neutral-800 focus:border-green-400 focus:outline-none"
                            />
                        </label>
                        <label class="flex flex-col gap-1.5 text-sm font-semibold text-neutral-700">
                            Repas
                            <select
                                v-model="editForm.meal_type"
                                class="h-10 rounded-md border border-neutral-200 px-3 text-sm font-normal text-neutral-800 focus:border-green-400 focus:outline-none"
                            >
                                <option v-for="meal in MEALS" :key="meal.key" :value="meal.key">
                                    {{ meal.label }}
                                </option>
                            </select>
                        </label>
                        <label class="flex flex-col gap-1.5 text-sm font-semibold text-neutral-700">
                            Portion
                            <input
                                v-model="editForm.serving_description"
                                class="h-10 rounded-md border border-neutral-200 px-3 text-sm font-normal text-neutral-800 focus:border-green-400 focus:outline-none"
                            />
                        </label>
                        <label
                            v-for="field in [
                                { key: 'quantity', label: 'Quantité', step: '0.1' },
                                { key: 'calories', label: 'Calories', step: '1' },
                                { key: 'protein', label: 'Protéines', step: '0.1' },
                                { key: 'carbs', label: 'Glucides', step: '0.1' },
                                { key: 'fat', label: 'Lipides', step: '0.1' },
                                { key: 'fiber', label: 'Fibres', step: '0.1' },
                            ]"
                            :key="field.key"
                            class="flex flex-col gap-1.5 text-sm font-semibold text-neutral-700"
                        >
                            {{ field.label }}
                            <input
                                v-model.number="editForm[field.key]"
                                type="number"
                                min="0"
                                :step="field.step"
                                class="h-10 rounded-md border border-neutral-200 px-3 text-sm font-normal text-neutral-800 focus:border-green-400 focus:outline-none"
                            />
                        </label>
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <AppButton type="button" variant="ghost" @click="closeEdit">Annuler</AppButton>
                        <AppButton type="submit">Enregistrer</AppButton>
                    </div>
                </form>
            </div>
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
