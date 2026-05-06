<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Check, Droplets, Flame, Pencil, Scale, Target, TrendingDown } from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    weeklyData: { type: Array, default: () => [] },
    monthlyData: { type: Array, default: () => [] },
    mealBreakdown: { type: Object, default: () => ({}) },
    waterData: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({
            avgCalories: 0,
            avgProtein: 0,
            avgWater: 0,
            daysLogged: 0,
            calorieGap: 0,
            currentWeight: null,
            totalLoss: null,
            goalCalories: 2000,
            goalWater: 8,
            streak: 0,
        }),
    },
})

// ── Poids ──────────────────────────────────────────────────────────────────────
const editingWeight = ref(false)
const weightForm = useForm({ weight_current: props.stats.currentWeight ?? '' })
const submitWeight = () => {
    weightForm.put(route('progression.update-weight'), {
        preserveScroll: true,
        onSuccess: () => {
            editingWeight.value = false
        },
    })
}

// ── Modes ──────────────────────────────────────────────────────────────────────
const chartMode = ref('calories') // 'calories' | 'weight'
const viewMode = ref('week') // 'week' | 'month'

// ── Données du graphique ────────────────────────────────────────────────────────
const DAYS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
const W = 360,
    H = 120,
    PAD_X = 20,
    PAD_Y = 12

const chartData = computed(() => {
    if (viewMode.value === 'month') {
        return props.monthlyData.length
            ? props.monthlyData
            : [
                  { label: 'S1', avgCalories: 0, daysLogged: 0, isCurrent: false },
                  { label: 'S2', avgCalories: 0, daysLogged: 0, isCurrent: false },
                  { label: 'S3', avgCalories: 0, daysLogged: 0, isCurrent: false },
                  { label: 'S4', avgCalories: 0, daysLogged: 0, isCurrent: true },
              ]
    }
    return props.weeklyData.length
        ? props.weeklyData
        : DAYS.map((d) => ({ day: d, calories: 0, weight: null, isFuture: false, isToday: false }))
})

const values = computed(() => {
    if (viewMode.value === 'month') {
        return chartData.value.map((d) => (chartMode.value === 'calories' ? (d.avgCalories ?? 0) : (d.avgWeight ?? 0)))
    }
    return chartData.value.map((d) =>
        d.isFuture ? 0 : chartMode.value === 'calories' ? (d.calories ?? 0) : (d.weight ?? 0),
    )
})

const maxVal = computed(() => Math.max(...values.value, 1))
const minVal = computed(() => Math.min(...values.value.filter((v) => v > 0), 0))

const points = computed(() =>
    values.value.map((v, i) => {
        const d = chartData.value[i] ?? {}
        return {
            x: PAD_X + (i / Math.max(chartData.value.length - 1, 1)) * (W - PAD_X * 2),
            y: PAD_Y + (1 - (v - minVal.value) / Math.max(maxVal.value - minVal.value, 1)) * (H - PAD_Y * 2),
            val: v,
            label: viewMode.value === 'month' ? (d.label ?? '') : (d.day ?? DAYS[i]),
            isToday: viewMode.value === 'week' ? (d.isToday ?? false) : (d.isCurrent ?? false),
            isFuture: viewMode.value === 'week' ? (d.isFuture ?? false) : false,
        }
    }),
)

const pathD = computed(() => {
    const pts = points.value.filter((p) => p.val > 0)
    if (pts.length < 2) return ''
    return pts.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ')
})

const areaD = computed(() => {
    const pts = points.value.filter((p) => p.val > 0)
    if (pts.length < 2) return ''
    const last = pts[pts.length - 1],
        first = pts[0]
    return `${pathD.value} L ${last.x} ${H} L ${first.x} ${H} Z`
})

const goalLineY = computed(() => {
    if (chartMode.value !== 'calories') return null
    const goal = props.stats.goalCalories || 2000
    const y = PAD_Y + (1 - (goal - minVal.value) / Math.max(maxVal.value - minVal.value, 1)) * (H - PAD_Y * 2)
    return y < PAD_Y || y > H - PAD_Y ? null : y
})

// ── Donut repas ─────────────────────────────────────────────────────────────────
const MEAL_CFG = [
    { key: 'breakfast', label: 'Petit-déj', color: 'oklch(73% 0.18 80)' },
    { key: 'lunch', label: 'Déjeuner', color: 'oklch(62% 0.18 145)' },
    { key: 'snack', label: 'Collation', color: 'oklch(67% 0.19 50)' },
    { key: 'dinner', label: 'Dîner', color: 'oklch(55% 0.02 270)' },
]
const DONUT_R = 38
const DONUT_CIRC = 2 * Math.PI * DONUT_R

const donutTotal = computed(() => MEAL_CFG.reduce((s, m) => s + (props.mealBreakdown[m.key] ?? 0), 0))

const donutSegments = computed(() => {
    if (!donutTotal.value) return []
    let cumulative = 0
    return MEAL_CFG.map((m) => {
        const cal = props.mealBreakdown[m.key] ?? 0
        if (!cal) return null
        const arc = (cal / donutTotal.value) * DONUT_CIRC
        const seg = {
            ...m,
            calories: cal,
            pct: Math.round((cal / donutTotal.value) * 100),
            dasharray: `${arc} ${DONUT_CIRC}`,
            dashoffset: -cumulative,
        }
        cumulative += arc
        return seg
    }).filter(Boolean)
})

// ── Cartes stats ────────────────────────────────────────────────────────────────
const STAT_CARDS = computed(() => [
    {
        icon: Flame,
        label: 'Moy. calorique',
        val: props.stats.avgCalories || '–',
        unit: 'kcal/j',
        color: 'text-orange-500',
        bg: 'bg-orange-50',
    },
    {
        icon: Target,
        label: 'Objectif',
        val: props.stats.goalCalories || '–',
        unit: 'kcal/j',
        color: 'text-amber-600',
        bg: 'bg-amber-100',
    },
    {
        icon: Check,
        label: 'Jours loggés',
        val: props.stats.daysLogged || '–',
        unit: 'cette semaine',
        color: 'text-green-600',
        bg: 'bg-green-50',
    },
    {
        icon: Droplets,
        label: 'Eau moyenne',
        val: props.stats.avgWater || '–',
        unit: 'verres/j',
        color: 'text-blue-500',
        bg: 'bg-blue-100',
    },
    {
        icon: Flame,
        label: 'Écart objectif',
        val: props.stats.calorieGap > 0 ? `+${props.stats.calorieGap}` : props.stats.calorieGap || '–',
        unit: 'kcal/j',
        color: props.stats.calorieGap > 0 ? 'text-orange-500' : 'text-green-600',
        bg: props.stats.calorieGap > 0 ? 'bg-orange-50' : 'bg-green-50',
    },
    {
        icon: TrendingDown,
        label: 'Écart poids',
        val: props.stats.totalLoss ?? '–',
        unit: 'kg',
        color: 'text-green-600',
        bg: 'bg-green-50',
    },
])

const detailWidth = (item) => {
    if (chartMode.value === 'weight') {
        const value = viewMode.value === 'month' ? (item.avgWeight ?? 0) : (item.weight ?? 0)

        return maxVal.value > 0 ? Math.min((value / maxVal.value) * 100, 100) : 0
    }

    const value = viewMode.value === 'month' ? (item.avgCalories ?? 0) : (item.calories ?? 0)

    return Math.min((value / (props.stats.goalCalories || 2000)) * 100, 100)
}

const detailLabel = (item) => {
    if (chartMode.value === 'weight') {
        const value = viewMode.value === 'month' ? item.avgWeight : item.weight

        return value ? `${value} kg` : '–'
    }

    const value = viewMode.value === 'month' ? item.avgCalories : item.calories

    return value > 0 ? `${value} ${viewMode.value === 'month' ? 'kcal/j' : 'kcal'}` : '–'
}
</script>

<template>
    <MainLayout title="Progression">
        <div class="flex flex-col gap-5 px-6 py-6 md:px-7">
            <!-- Header + streak -->
            <div class="flex items-start justify-between">
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-neutral-400">Suivi</p>
                    <h1 class="font-display text-[28px] leading-tight tracking-tight text-neutral-900">Progression</h1>
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <a
                        :href="route('nutrition.export.csv')"
                        class="rounded-pill bg-white px-3 py-1.5 text-xs font-semibold text-neutral-500 shadow-sm transition-colors hover:text-green-600"
                    >
                        CSV
                    </a>
                    <a
                        :href="route('nutrition.export.json')"
                        class="rounded-pill bg-white px-3 py-1.5 text-xs font-semibold text-neutral-500 shadow-sm transition-colors hover:text-green-600"
                    >
                        JSON
                    </a>
                    <div
                        v-if="stats.streak > 0"
                        class="flex items-center gap-1.5 rounded-full bg-orange-50 px-3 py-1.5 shadow-sm"
                    >
                        <Flame :size="14" class="text-orange-500" />
                        <span class="text-sm font-bold text-orange-600">
                            {{ stats.streak }} jour{{ stats.streak > 1 ? 's' : '' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div v-for="s in STAT_CARDS" :key="s.label" class="rounded-lg bg-white p-4 shadow-sm">
                    <div class="mb-2 flex h-8 w-8 items-center justify-center rounded-md" :class="s.bg">
                        <component :is="s.icon" :size="16" :class="s.color" />
                    </div>
                    <p class="font-mono text-xl font-semibold text-neutral-900">{{ s.val }}</p>
                    <p class="mt-0.5 text-[10px] text-neutral-400">{{ s.unit }}</p>
                    <p class="mt-1 text-xs text-neutral-500">{{ s.label }}</p>
                </div>

                <!-- Carte poids éditable -->
                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <div class="mb-2 flex items-center justify-between">
                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-blue-100">
                            <Scale :size="16" class="text-blue-500" />
                        </div>
                        <button
                            @click="editingWeight = !editingWeight"
                            class="rounded p-1 text-neutral-300 transition-colors hover:text-neutral-600"
                            :title="editingWeight ? 'Annuler' : 'Modifier'"
                        >
                            <Pencil :size="12" />
                        </button>
                    </div>

                    <template v-if="!editingWeight">
                        <p class="font-mono text-xl font-semibold text-neutral-900">
                            {{ stats.currentWeight ?? '–' }}
                        </p>
                        <p class="mt-0.5 text-[10px] text-neutral-400">kg</p>
                        <p class="mt-1 text-xs text-neutral-500">Poids actuel</p>
                    </template>

                    <template v-else>
                        <form @submit.prevent="submitWeight" class="mt-1 flex flex-col gap-2">
                            <input
                                v-model="weightForm.weight_current"
                                type="number"
                                step="0.1"
                                min="20"
                                max="500"
                                placeholder="75.0"
                                class="w-full rounded border border-neutral-200 px-2 py-1.5 font-mono text-sm text-neutral-800 focus:border-green-400 focus:outline-none"
                                autofocus
                            />
                            <button
                                type="submit"
                                :disabled="weightForm.processing"
                                class="flex items-center justify-center gap-1 rounded bg-green-500 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-green-600 disabled:opacity-50"
                            >
                                <Check :size="11" />
                                Enregistrer
                            </button>
                        </form>
                    </template>
                </div>
            </div>

            <!-- Graphique -->
            <div class="rounded-xl bg-white p-5 shadow-md">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                        {{ chartMode === 'calories' ? 'Calories' : 'Poids' }}
                        —
                        {{ viewMode === 'week' ? 'cette semaine' : '4 dernières semaines' }}
                    </p>

                    <div class="flex items-center gap-2">
                        <!-- Sem. / Mois -->
                        <div class="flex rounded-md border border-neutral-100 p-0.5">
                            <button
                                v-for="v in [
                                    { key: 'week', label: 'Sem.' },
                                    { key: 'month', label: 'Mois' },
                                ]"
                                :key="v.key"
                                @click="viewMode = v.key"
                                class="rounded px-2.5 py-1 text-xs font-semibold transition-colors"
                                :class="
                                    viewMode === v.key
                                        ? 'bg-neutral-800 text-white'
                                        : 'text-neutral-400 hover:text-neutral-700'
                                "
                            >
                                {{ v.label }}
                            </button>
                        </div>
                        <!-- Calories / Poids -->
                        <div class="flex gap-1">
                            <button
                                v-for="m in [
                                    { key: 'calories', label: 'Calories' },
                                    { key: 'weight', label: 'Poids' },
                                ]"
                                :key="m.key"
                                @click="chartMode = m.key"
                                class="rounded-pill px-3 py-1 text-xs font-semibold transition-colors"
                                :class="
                                    chartMode === m.key
                                        ? 'bg-green-500 text-white'
                                        : 'text-neutral-400 hover:text-neutral-700'
                                "
                            >
                                {{ m.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- SVG -->
                <div class="relative">
                    <svg :width="W" :height="H" :viewBox="`0 0 ${W} ${H}`" class="w-full overflow-visible">
                        <defs>
                            <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="oklch(62% 0.18 145)" stop-opacity="0.15" />
                                <stop offset="100%" stop-color="oklch(62% 0.18 145)" stop-opacity="0" />
                            </linearGradient>
                        </defs>

                        <path v-if="areaD" :d="areaD" fill="url(#chartGradient)" />
                        <path
                            v-if="pathD"
                            :d="pathD"
                            fill="none"
                            stroke="oklch(62% 0.18 145)"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <circle
                            v-for="p in points.filter((p) => p.val > 0)"
                            :key="p.label"
                            :cx="p.x"
                            :cy="p.y"
                            r="4"
                            fill="white"
                            stroke="oklch(62% 0.18 145)"
                            stroke-width="2"
                        />

                        <!-- Ligne objectif -->
                        <line
                            v-if="goalLineY !== null"
                            :x1="PAD_X"
                            :y1="goalLineY"
                            :x2="W - PAD_X"
                            :y2="goalLineY"
                            stroke="oklch(73% 0.18 80)"
                            stroke-width="1"
                            stroke-dasharray="4 3"
                            opacity="0.6"
                        />

                        <!-- État vide -->
                        <line
                            v-if="!pathD"
                            :x1="PAD_X"
                            :y1="H / 2"
                            :x2="W - PAD_X"
                            :y2="H / 2"
                            stroke="#E0DDD9"
                            stroke-width="1"
                            stroke-dasharray="4 4"
                        />
                    </svg>

                    <!-- Labels X -->
                    <div class="mt-2 flex justify-between px-5">
                        <span
                            v-for="p in points"
                            :key="p.label"
                            class="text-[10px] font-medium"
                            :class="
                                p.isToday
                                    ? 'font-bold text-green-600'
                                    : p.isFuture
                                      ? 'text-neutral-200'
                                      : 'text-neutral-400'
                            "
                        >
                            {{ p.label }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tableau détail -->
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-neutral-100 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                        {{ viewMode === 'week' ? 'Détail semaine' : 'Détail 4 semaines' }}
                    </p>
                </div>

                <!-- Vue semaine -->
                <div v-if="viewMode === 'week' && weeklyData.length" class="divide-y divide-neutral-50">
                    <div
                        v-for="day in weeklyData"
                        :key="day.date"
                        class="flex items-center gap-4 px-4 py-3"
                        :class="day.isToday && 'bg-green-50/50'"
                    >
                        <div class="flex w-10 shrink-0 items-center gap-1.5">
                            <span
                                class="text-xs font-semibold"
                                :class="
                                    day.isToday
                                        ? 'text-green-600'
                                        : day.isFuture
                                          ? 'text-neutral-300'
                                          : 'text-neutral-500'
                                "
                            >
                                {{ day.day }}
                            </span>
                            <span v-if="day.isToday" class="h-1.5 w-1.5 rounded-full bg-green-500" />
                        </div>
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-neutral-100">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="day.isFuture ? '' : 'bg-green-500'"
                                :style="{
                                    width: day.isFuture ? '0%' : `${detailWidth(day)}%`,
                                }"
                            />
                        </div>
                        <span
                            class="w-16 shrink-0 text-right font-mono text-xs"
                            :class="day.isFuture ? 'text-neutral-300' : 'text-neutral-600'"
                        >
                            {{ day.isFuture ? '–' : detailLabel(day) }}
                        </span>
                    </div>
                </div>

                <!-- Vue mensuelle -->
                <div v-else-if="viewMode === 'month' && monthlyData.length" class="divide-y divide-neutral-50">
                    <div
                        v-for="week in monthlyData"
                        :key="week.weekStart"
                        class="flex items-center gap-4 px-4 py-3"
                        :class="week.isCurrent && 'bg-green-50/50'"
                    >
                        <div class="w-16 shrink-0">
                            <p
                                class="text-xs font-semibold"
                                :class="week.isCurrent ? 'text-green-600' : 'text-neutral-500'"
                            >
                                {{ week.label }}
                            </p>
                            <p class="text-[10px] text-neutral-400">{{ week.daysLogged }} j loggés</p>
                        </div>
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-neutral-100">
                            <div
                                class="h-full rounded-full bg-green-500 transition-all duration-500"
                                :style="{
                                    width: `${detailWidth(week)}%`,
                                }"
                            />
                        </div>
                        <span class="w-20 shrink-0 text-right font-mono text-xs text-neutral-600">
                            {{ detailLabel(week) }}
                        </span>
                    </div>
                </div>

                <p v-else class="px-4 py-6 text-center text-sm text-neutral-400">
                    Commencez à logger vos repas pour voir votre progression ici.
                </p>
            </div>

            <!-- Hydratation -->
            <div class="rounded-xl bg-white p-5 shadow-md">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                        Hydratation — cette semaine
                    </p>
                    <span class="font-mono text-xs font-semibold text-blue-500">
                        {{ stats.avgWater || '–' }}/{{ stats.goalWater || 8 }} verres/j
                    </span>
                </div>
                <div class="divide-y divide-neutral-50">
                    <div
                        v-for="day in waterData"
                        :key="day.date"
                        class="flex items-center gap-4 py-2.5"
                        :class="day.isToday && 'bg-blue-50/40 px-2'"
                    >
                        <span
                            class="w-10 shrink-0 text-xs font-semibold"
                            :class="
                                day.isToday ? 'text-blue-500' : day.isFuture ? 'text-neutral-300' : 'text-neutral-500'
                            "
                        >
                            {{ day.day }}
                        </span>
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-blue-100">
                            <div
                                class="h-full rounded-full bg-blue-500"
                                :style="{
                                    width: day.isFuture
                                        ? '0%'
                                        : `${Math.min((day.glasses / (stats.goalWater || 8)) * 100, 100)}%`,
                                }"
                            />
                        </div>
                        <span class="w-16 shrink-0 text-right font-mono text-xs text-neutral-500">
                            {{ day.isFuture ? '–' : `${day.glasses} v.` }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Répartition par repas -->
            <div class="rounded-xl bg-white p-5 shadow-md">
                <p class="mb-4 text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                    Répartition par repas — cette semaine
                </p>

                <div v-if="donutTotal > 0" class="flex items-center gap-6">
                    <!-- Donut SVG -->
                    <div class="shrink-0">
                        <svg viewBox="0 0 100 100" width="120" height="120">
                            <!-- Anneau de fond -->
                            <circle cx="50" cy="50" :r="DONUT_R" fill="none" stroke="#f3f4f6" stroke-width="16" />
                            <!-- Segments -->
                            <circle
                                v-for="seg in donutSegments"
                                :key="seg.key"
                                cx="50"
                                cy="50"
                                :r="DONUT_R"
                                fill="none"
                                :stroke="seg.color"
                                stroke-width="16"
                                :stroke-dasharray="seg.dasharray"
                                :stroke-dashoffset="seg.dashoffset"
                                transform="rotate(-90 50 50)"
                            />
                            <!-- Total au centre -->
                            <text
                                x="50"
                                y="46"
                                text-anchor="middle"
                                font-size="11"
                                font-weight="700"
                                fill="#1c1c1c"
                                font-family="monospace"
                            >
                                {{ donutTotal }}
                            </text>
                            <text x="50" y="57" text-anchor="middle" font-size="7.5" fill="#a1a1aa">kcal</text>
                        </svg>
                    </div>

                    <!-- Légende -->
                    <div class="flex flex-1 flex-col gap-3">
                        <div v-for="seg in donutSegments" :key="seg.key" class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ background: seg.color }" />
                            <span class="min-w-0 flex-1 truncate text-xs text-neutral-600">{{ seg.label }}</span>
                            <span class="shrink-0 font-mono text-xs font-semibold text-neutral-700">
                                {{ seg.pct }}%
                            </span>
                            <span class="w-16 shrink-0 text-right text-[11px] text-neutral-400">
                                {{ seg.calories }} kcal
                            </span>
                        </div>
                    </div>
                </div>

                <p v-else class="py-4 text-center text-sm text-neutral-400">Aucune donnée pour cette semaine.</p>
            </div>
        </div>
    </MainLayout>
</template>
