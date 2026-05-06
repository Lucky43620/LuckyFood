<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import MacroBar from '@/Components/nutrition/MacroBar.vue'
import FoodItem from '@/Components/food/FoodItem.vue'
import AppButton from '@/Components/ui/AppButton.vue'
import { MEAL_CONFIGS } from '@/constants/nutrition'

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

const removeEntry = (id) => {
    router.delete(route('journal.destroy', id), { preserveScroll: true })
}
</script>

<template>
    <MainLayout title="Journal alimentaire">
        <div class="flex flex-col gap-5 px-6 py-6 md:px-7">
            <!-- Header with date navigation -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-neutral-400">Journal</p>
                    <h1 class="font-display text-[28px] capitalize leading-tight tracking-tight text-neutral-900">
                        {{ isToday ? "Aujourd'hui" : formattedDate }}
                    </h1>
                </div>

                <div class="flex items-center gap-2">
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
                        :href="route('search.show', { foodId: entry.food_id, from: 'journal', meal: meal.key })"
                        :last="idx === byMeal(meal.key).length - 1"
                        @remove="removeEntry(entry.id)"
                    />
                </div>
                <p v-else class="px-4 py-3 text-xs italic text-neutral-400">Aucun aliment ajouté</p>
            </div>
        </div>
    </MainLayout>
</template>
