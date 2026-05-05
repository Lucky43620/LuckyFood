<script setup>
import { ref, computed } from 'vue'
import { TrendingUp, TrendingDown, Flame, Scale, Target, Droplets } from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import AppBadge   from '@/Components/ui/AppBadge.vue'

const props = defineProps({
  weeklyData: { type: Array,  default: () => [] },
  stats:      { type: Object, default: () => ({ avgCalories: 0, currentWeight: null, totalLoss: null, goalCalories: 2000 }) },
})

const chartMode = ref('calories') // 'calories' | 'weight'

// Generate 7-day placeholder if no data
const DAYS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
const chartData = computed(() => {
  if (props.weeklyData.length) return props.weeklyData
  return DAYS.map((d, i) => ({ day: d, calories: 0, weight: null }))
})

// SVG chart dimensions
const W = 360, H = 120, PAD_X = 20, PAD_Y = 12

const values = computed(() =>
  chartData.value.map(d => chartMode.value === 'calories' ? (d.calories ?? 0) : (d.weight ?? 0))
)

const maxVal = computed(() => Math.max(...values.value, 1))
const minVal = computed(() => Math.min(...values.value.filter(v => v > 0), 0))

const points = computed(() =>
  values.value.map((v, i) => ({
    x: PAD_X + (i / Math.max(chartData.value.length - 1, 1)) * (W - PAD_X * 2),
    y: PAD_Y + (1 - (v - minVal.value) / Math.max(maxVal.value - minVal.value, 1)) * (H - PAD_Y * 2),
    val: v,
    day: chartData.value[i]?.day ?? DAYS[i],
  }))
)

const pathD = computed(() => {
  const pts = points.value.filter(p => p.val > 0)
  if (pts.length < 2) return ''
  return pts.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ')
})

const areaD = computed(() => {
  const pts = points.value.filter(p => p.val > 0)
  if (pts.length < 2) return ''
  const last = pts[pts.length - 1], first = pts[0]
  return `${pathD.value} L ${last.x} ${H} L ${first.x} ${H} Z`
})

const STAT_CARDS = computed(() => [
  { icon: Flame,      label: 'Moy. calorique',  val: props.stats.avgCalories || '–', unit: 'kcal/j',  color: 'text-orange-500', bg: 'bg-orange-50' },
  { icon: Scale,      label: 'Poids actuel',    val: props.stats.currentWeight ?? '–', unit: 'kg',    color: 'text-blue-500',   bg: 'bg-blue-100' },
  { icon: TrendingDown,label:'Perte totale',   val: props.stats.totalLoss ?? '–',   unit: 'kg',       color: 'text-green-600',  bg: 'bg-green-50' },
  { icon: Target,     label: 'Objectif',         val: props.stats.goalCalories || '–', unit: 'kcal/j', color: 'text-amber-600',  bg: 'bg-amber-100' },
])
</script>

<template>
  <MainLayout title="Progression">
    <div class="px-6 md:px-7 py-6 flex flex-col gap-5">

      <!-- Header -->
      <div>
        <p class="text-xs font-semibold tracking-widest uppercase text-neutral-400 mb-1">Suivi</p>
        <h1 class="font-display text-[28px] leading-tight tracking-tight text-neutral-900">Progression</h1>
      </div>

      <!-- Stat cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div
          v-for="s in STAT_CARDS"
          :key="s.label"
          class="bg-white rounded-lg shadow-sm p-4"
        >
          <div class="w-8 h-8 rounded-md flex items-center justify-center mb-2" :class="s.bg">
            <component :is="s.icon" :size="16" :class="s.color" />
          </div>
          <p class="font-mono text-xl font-semibold text-neutral-900">{{ s.val }}</p>
          <p class="text-[10px] text-neutral-400 mt-0.5">{{ s.unit }}</p>
          <p class="text-xs text-neutral-500 mt-1">{{ s.label }}</p>
        </div>
      </div>

      <!-- Chart card -->
      <div class="bg-white rounded-xl shadow-md p-5">
        <div class="flex items-center justify-between mb-4">
          <p class="text-[11px] font-semibold tracking-widest uppercase text-neutral-400">
            {{ chartMode === 'calories' ? 'Calories cette semaine' : 'Poids cette semaine' }}
          </p>
          <div class="flex gap-1">
            <button
              v-for="m in [{ key: 'calories', label: 'Calories' }, { key: 'weight', label: 'Poids' }]"
              :key="m.key"
              @click="chartMode = m.key"
              class="px-3 py-1 rounded-pill text-xs font-semibold transition-colors"
              :class="chartMode === m.key
                ? 'bg-green-500 text-white'
                : 'text-neutral-400 hover:text-neutral-700'"
            >
              {{ m.label }}
            </button>
          </div>
        </div>

        <!-- SVG Chart -->
        <div class="relative">
          <svg :width="W" :height="H" :viewBox="`0 0 ${W} ${H}`" class="w-full overflow-visible">
            <defs>
              <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%"   stop-color="oklch(62% 0.18 145)" stop-opacity="0.15" />
                <stop offset="100%" stop-color="oklch(62% 0.18 145)" stop-opacity="0" />
              </linearGradient>
            </defs>

            <!-- Area fill -->
            <path v-if="areaD" :d="areaD" fill="url(#chartGradient)" />

            <!-- Line -->
            <path
              v-if="pathD"
              :d="pathD"
              fill="none"
              stroke="oklch(62% 0.18 145)"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />

            <!-- Dots -->
            <circle
              v-for="p in points.filter(p => p.val > 0)"
              :key="p.day"
              :cx="p.x" :cy="p.y" r="4"
              fill="white"
              stroke="oklch(62% 0.18 145)"
              stroke-width="2"
            />

            <!-- Empty state line -->
            <line
              v-if="!pathD"
              :x1="PAD_X" :y1="H / 2"
              :x2="W - PAD_X" :y2="H / 2"
              stroke="#E0DDD9" stroke-width="1" stroke-dasharray="4 4"
            />
          </svg>

          <!-- X labels -->
          <div class="flex justify-between mt-2 px-5">
            <span
              v-for="p in points"
              :key="p.day"
              class="text-[10px] text-neutral-400 font-medium"
            >
              {{ p.day }}
            </span>
          </div>
        </div>
      </div>

      <!-- Weekly table -->
      <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-neutral-100">
          <p class="text-[11px] font-semibold tracking-widest uppercase text-neutral-400">Détail semaine</p>
        </div>

        <div v-if="weeklyData.length" class="divide-y divide-neutral-50">
          <div
            v-for="day in weeklyData"
            :key="day.date"
            class="flex items-center gap-4 px-4 py-3"
          >
            <span class="text-xs font-semibold text-neutral-500 w-10 shrink-0">{{ day.day }}</span>
            <div class="flex-1 h-1.5 bg-neutral-100 rounded-full overflow-hidden">
              <div
                class="h-full bg-green-500 rounded-full"
                :style="{ width: `${Math.min((day.calories / (stats.goalCalories || 2000)) * 100, 100)}%` }"
              />
            </div>
            <span class="font-mono text-xs text-neutral-600 w-16 text-right shrink-0">
              {{ day.calories }} kcal
            </span>
          </div>
        </div>

        <p v-else class="px-4 py-6 text-center text-sm text-neutral-400">
          Commencez à logger vos repas pour voir votre progression ici.
        </p>
      </div>

    </div>
  </MainLayout>
</template>
