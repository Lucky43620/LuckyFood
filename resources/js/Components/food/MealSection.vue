<script setup>
import { computed } from 'vue'
import { Plus } from 'lucide-vue-next'
import FoodItem from './FoodItem.vue'

const props = defineProps({
    name: { type: String, required: true },
    icon: { type: [Object, Function], required: true }, // lucide component
    iconBg: { type: String, required: true }, // tailwind bg class
    iconColor: { type: String, required: true }, // tailwind text class
    items: { type: Array, default: () => [] },
    mealType: { type: String, required: true },
})

const emit = defineEmits(['add-food', 'remove-food'])

const total = computed(() => props.items.reduce((sum, item) => sum + (item.calories ?? 0), 0))

const itemHref = (item) => {
    const foodId = String(item.food_id ?? '')

    if (!foodId || foodId.startsWith('manual:')) return null

    if (foodId.startsWith('recipe:')) {
        const recipeId = foodId.replace('recipe:', '')

        return recipeId ? route('recipes.show', recipeId) : null
    }

    return route('search.show', { foodId, meal: props.mealType, from: 'dashboard' })
}
</script>

<template>
    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <!-- Meal header -->
        <div
            class="flex items-center justify-between px-4 py-3.5"
            :class="items.length > 0 && 'border-b border-neutral-100'"
        >
            <div class="flex items-center gap-2.5">
                <div class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-md" :class="iconBg">
                    <component :is="icon" :size="16" :class="iconColor" />
                </div>
                <div>
                    <p class="text-sm font-bold text-neutral-800">{{ name }}</p>
                    <p class="font-mono text-[11px] text-neutral-400">{{ total }} kcal</p>
                </div>
            </div>

            <button
                @click="emit('add-food', mealType)"
                class="flex h-[30px] items-center gap-1 rounded-pill bg-green-50 px-3 text-xs font-semibold text-green-600 transition-colors hover:bg-green-100"
            >
                <Plus :size="13" /> Ajouter
            </button>
        </div>

        <!-- Food items list -->
        <FoodItem
            v-for="(item, idx) in items"
            :key="item.id ?? idx"
            :name="item.food_name ?? item.name"
            :serving="item.serving_description ?? item.serving ?? ''"
            :kcal="item.calories ?? 0"
            :protein="item.protein"
            :carbs="item.carbs"
            :fat="item.fat"
            :href="itemHref(item)"
            :last="idx === items.length - 1"
            @remove="emit('remove-food', item)"
        />
    </div>
</template>
