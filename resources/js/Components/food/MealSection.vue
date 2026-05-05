<script setup>
import { computed } from 'vue'
import { Plus } from 'lucide-vue-next'
import FoodItem from './FoodItem.vue'

const props = defineProps({
  name:      { type: String, required: true },
  icon:      { type: [Object, Function], required: true },  // lucide component
  iconBg:    { type: String, required: true },   // tailwind bg class
  iconColor: { type: String, required: true },   // tailwind text class
  items:     { type: Array,  default: () => [] },
  mealType:  { type: String, required: true },
})

const emit = defineEmits(['add-food', 'remove-food'])

const total = computed(() => props.items.reduce((sum, item) => sum + (item.calories ?? 0), 0))
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm overflow-hidden">

    <!-- Meal header -->
    <div
      class="flex items-center justify-between px-4 py-3.5"
      :class="items.length > 0 && 'border-b border-neutral-100'"
    >
      <div class="flex items-center gap-2.5">
        <div class="w-[34px] h-[34px] rounded-md flex items-center justify-center shrink-0" :class="iconBg">
          <component :is="icon" :size="16" :class="iconColor" />
        </div>
        <div>
          <p class="text-sm font-bold text-neutral-800">{{ name }}</p>
          <p class="font-mono text-[11px] text-neutral-400">{{ total }} kcal</p>
        </div>
      </div>

      <button
        @click="emit('add-food', mealType)"
        class="flex items-center gap-1 h-[30px] px-3 bg-green-50 text-green-600 text-xs font-semibold rounded-pill hover:bg-green-100 transition-colors"
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
      :last="idx === items.length - 1"
      @remove="emit('remove-food', item)"
    />

  </div>
</template>
