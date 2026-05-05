<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { ArrowLeft, ExternalLink, Leaf, Loader2 } from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import AppBadge from '@/Components/ui/AppBadge.vue'

const props = defineProps({
  food: { type: Object, default: null },
  meal: { type: String, default: 'breakfast' },
  query: { type: String, default: '' },
  searchError: { type: Object, default: null },
})

const MEALS = [
  { key: 'breakfast', label: 'Petit-déjeuner' },
  { key: 'lunch',     label: 'Déjeuner' },
  { key: 'snack',     label: 'Collation' },
  { key: 'dinner',    label: 'Dîner' },
]

const _raw   = props.food?.servings?.serving
const _first = Array.isArray(_raw) ? _raw[0] : _raw
const selectedServingId = ref(_first?.serving_id ?? null)
const selectedMeal = ref(props.meal)
const quantity     = ref(1)
const addingFood   = ref(false)
const toast        = ref(null)

const servings = computed(() => {
  const s = props.food?.servings?.serving
  if (!s) return []
  return Array.isArray(s) ? s : [s]
})

const serving = computed(() =>
  servings.value.find(s => s.serving_id === selectedServingId.value) ?? servings.value[0] ?? {}
)

const asNum = (v) => { const n = Number(v); return Number.isFinite(n) ? n : 0 }
const hasVal = (v) => v !== undefined && v !== null && v !== ''

const fmt = (v, d = 1) => {
  if (!hasVal(v)) return '-'
  const n = Number(v)
  if (!Number.isFinite(n)) return '-'
  return n.toLocaleString('fr-FR', { maximumFractionDigits: d })
}

const calories = computed(() => asNum(serving.value.calories))
const protein  = computed(() => asNum(serving.value.protein))
const carbs    = computed(() => asNum(serving.value.carbohydrate))
const fat      = computed(() => asNum(serving.value.fat))

const macroTotal = computed(() => Math.max(1, protein.value + carbs.value + fat.value))

const PC = 'oklch(60% 0.16 240)'
const CC = 'oklch(73% 0.18 80)'
const FC = 'oklch(64% 0.18 25)'

const pPct = computed(() => Math.round((protein.value / macroTotal.value) * 100))
const cPct = computed(() => Math.round((carbs.value   / macroTotal.value) * 100))
const fPct = computed(() => 100 - pPct.value - cPct.value)

const nutritionRows = computed(() => [
  { label: 'Calories',                val: serving.value.calories,          unit: 'kcal', d: 0, bold: true },
  { label: 'Protéines',               val: serving.value.protein,           unit: 'g',    d: 1 },
  { label: 'Glucides',                val: serving.value.carbohydrate,      unit: 'g',    d: 1 },
  { label: '— dont sucres',           val: serving.value.sugar,             unit: 'g',    d: 1, indent: true },
  { label: 'Fibres',                  val: serving.value.fiber,             unit: 'g',    d: 1 },
  { label: 'Lipides',                 val: serving.value.fat,               unit: 'g',    d: 1 },
  { label: '— dont saturés',          val: serving.value.saturated_fat,     unit: 'g',    d: 2, indent: true },
  { label: '— dont polyinsaturés',    val: serving.value.polyunsaturated_fat, unit: 'g',  d: 2, indent: true },
  { label: '— dont monoinsaturés',    val: serving.value.monounsaturated_fat, unit: 'g',  d: 2, indent: true },
  { label: '— dont acides gras trans',val: serving.value.trans_fat,         unit: 'g',    d: 2, indent: true },
  { label: 'Cholestérol',             val: serving.value.cholesterol,       unit: 'mg',   d: 1 },
  { label: 'Sodium',                  val: serving.value.sodium,            unit: 'mg',   d: 1 },
  { label: 'Potassium',               val: serving.value.potassium,         unit: 'mg',   d: 1 },
  { label: 'Calcium',                 val: serving.value.calcium,           unit: '%',    d: 1 },
  { label: 'Fer',                     val: serving.value.iron,              unit: '%',    d: 1 },
  { label: 'Vitamine A',              val: serving.value.vitamin_a,         unit: '%',    d: 1 },
  { label: 'Vitamine C',              val: serving.value.vitamin_c,         unit: '%',    d: 1 },
].filter(r => hasVal(r.val)))

const allergens   = computed(() => props.food?.food_attributes?.allergens ?? [])
const preferences = computed(() => props.food?.food_attributes?.preferences ?? [])
const attrState   = (v) => v === 1 ? 'Oui' : v === 0 ? 'Non' : '–'

const searchParams = computed(() => ({ q: props.query, meal: props.meal }))

const totalCal = computed(() => Math.round(calories.value * Math.max(0.1, quantity.value)))

const addToJournal = () => {
  if (!props.food) return
  addingFood.value = true
  const q = Math.max(0.1, quantity.value)
  router.post(route('journal.store'), {
    food_id:             props.food.food_id,
    food_name:           props.food.food_name,
    meal_type:           selectedMeal.value,
    calories:            Math.round(calories.value * q),
    protein:             +(protein.value * q).toFixed(1),
    carbs:               +(carbs.value   * q).toFixed(1),
    fat:                 +(fat.value     * q).toFixed(1),
    serving_description: serving.value.serving_description ?? '',
    quantity:            q,
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      toast.value = `${props.food.food_name} ajouté au journal`
      setTimeout(() => { toast.value = null }, 3000)
    },
    onFinish: () => { addingFood.value = false },
  })
}
</script>

<template>
  <MainLayout :title="food?.food_name ?? 'Aliment'">
    <div class="px-5 md:px-8 py-6 max-w-3xl">

      <!-- Retour -->
      <Link
        :href="route('search.index', searchParams)"
        class="inline-flex items-center gap-1.5 text-sm font-semibold text-neutral-400 hover:text-green-600 mb-6 transition-colors"
      >
        <ArrowLeft :size="15" />
        Retour à la recherche
      </Link>

      <!-- Erreur API -->
      <div v-if="searchError" class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
        {{ searchError.message }}
      </div>

      <!-- Introuvable -->
      <div v-else-if="!food" class="rounded-2xl bg-white p-14 text-center" style="box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.07)">
        <Leaf :size="32" class="mx-auto mb-3 text-neutral-300" />
        <p class="font-semibold text-neutral-600">Aliment introuvable</p>
      </div>

      <!-- ══════════════════════════════════════════════════ -->
      <div v-else class="space-y-6">

        <!-- ── 1. HERO ──────────────────────────────────── -->
        <div class="bg-white" style="border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.07)">

          <!-- Image ou dégradé vert plein largeur -->
          <div class="relative overflow-hidden" style="height: 180px; border-radius: 20px 20px 0 0">
            <img
              v-if="food.image_url"
              :src="food.image_url"
              :alt="food.food_name"
              class="absolute inset-0 h-full w-full object-cover"
            >
            <div
              v-else
              class="absolute inset-0 flex items-center justify-center"
              style="background: linear-gradient(135deg, oklch(62% 0.18 145) 0%, oklch(54% 0.17 145) 100%)"
            >
              <span
                class="absolute font-display font-black text-white select-none"
                style="font-size: 180px; opacity: 0.08; line-height: 1; top: -20px"
              >{{ food.food_name?.[0] }}</span>
              <Leaf :size="52" class="relative text-white" style="opacity: 0.5" />
            </div>
          </div>

          <!-- Contenu sous l'image -->
          <div class="p-5 md:p-6">
            <!-- Badges + nom -->
            <div class="flex flex-wrap items-center gap-2 mb-2">
              <AppBadge v-if="food.food_type === 'Generic'" color="green">Générique</AppBadge>
              <AppBadge v-if="food.brand_name" color="neutral">{{ food.brand_name }}</AppBadge>
              <span class="text-[10px] font-bold uppercase tracking-widest text-neutral-400">Base FatSecret</span>
            </div>

            <h1 class="font-display text-[26px] md:text-[32px] leading-tight text-neutral-900">
              {{ food.food_name }}
            </h1>
            <p v-if="food.original_food_name && food.original_food_name !== food.food_name"
               class="text-sm text-neutral-400 mt-1">
              {{ food.original_food_name }}
            </p>

            <!-- Calorie + macros rapides -->
            <div class="mt-5 pt-5 border-t border-neutral-100 grid grid-cols-4 gap-3">
              <div>
                <p class="font-mono text-4xl font-black text-neutral-900 leading-none">{{ fmt(calories, 0) }}</p>
                <p class="text-[11px] font-bold text-neutral-400 uppercase tracking-wide mt-1.5">kcal</p>
              </div>
              <div>
                <p class="font-mono text-4xl font-black leading-none" style="color: oklch(60% 0.16 240)">{{ fmt(protein, 1) }}</p>
                <p class="text-[11px] font-bold uppercase tracking-wide mt-1.5" style="color: oklch(70% 0.14 240)">Prot. g</p>
              </div>
              <div>
                <p class="font-mono text-4xl font-black text-amber-500 leading-none">{{ fmt(carbs, 1) }}</p>
                <p class="text-[11px] font-bold text-amber-400 uppercase tracking-wide mt-1.5">Gluc. g</p>
              </div>
              <div>
                <p class="font-mono text-4xl font-black leading-none" style="color: oklch(64% 0.18 25)">{{ fmt(fat, 1) }}</p>
                <p class="text-[11px] font-bold uppercase tracking-wide mt-1.5" style="color: oklch(72% 0.16 25)">Lip. g</p>
              </div>
            </div>

            <!-- Barre macros -->
            <div class="mt-4 h-2 rounded-full overflow-hidden flex">
              <div class="h-full transition-all" :style="{ width: pPct + '%', background: PC }" />
              <div class="h-full transition-all" :style="{ width: cPct + '%', background: CC }" />
              <div class="h-full transition-all" :style="{ width: fPct + '%', background: FC }" />
            </div>

            <p class="mt-2 text-xs text-neutral-400">{{ serving.serving_description }}</p>
          </div>
        </div>

        <!-- ── 2. TUILES MACROS ─────────────────────────── -->
        <div class="grid grid-cols-3 gap-3">
          <!-- Protéines -->
          <div style="border-radius: 20px; background: oklch(95% 0.03 240)" class="p-4">
            <p class="font-mono text-2xl font-black leading-none mb-0.5" style="color: oklch(60% 0.16 240)">
              {{ fmt(protein, 1) }}g
            </p>
            <p class="text-[11px] font-bold mb-3" style="color: oklch(55% 0.12 240)">Protéines</p>
            <div class="h-1.5 rounded-full overflow-hidden" style="background: oklch(88% 0.06 240)">
              <div class="h-full rounded-full" :style="{ width: pPct + '%', background: PC }" />
            </div>
            <p class="text-[11px] text-neutral-400 font-semibold mt-1.5">{{ pPct }}%</p>
          </div>

          <!-- Glucides -->
          <div style="border-radius: 20px; background: oklch(95% 0.05 85)" class="p-4">
            <p class="font-mono text-2xl font-black text-amber-600 leading-none mb-0.5">
              {{ fmt(carbs, 1) }}g
            </p>
            <p class="text-[11px] font-bold text-amber-500 mb-3">Glucides</p>
            <div class="h-1.5 rounded-full overflow-hidden bg-amber-100">
              <div class="h-full rounded-full" :style="{ width: cPct + '%', background: CC }" />
            </div>
            <p class="text-[11px] text-neutral-400 font-semibold mt-1.5">{{ cPct }}%</p>
          </div>

          <!-- Lipides -->
          <div style="border-radius: 20px; background: oklch(95% 0.04 25)" class="p-4">
            <p class="font-mono text-2xl font-black leading-none mb-0.5" style="color: oklch(64% 0.18 25)">
              {{ fmt(fat, 1) }}g
            </p>
            <p class="text-[11px] font-bold mb-3" style="color: oklch(58% 0.14 25)">Lipides</p>
            <div class="h-1.5 rounded-full overflow-hidden" style="background: oklch(88% 0.06 25)">
              <div class="h-full rounded-full" :style="{ width: fPct + '%', background: FC }" />
            </div>
            <p class="text-[11px] text-neutral-400 font-semibold mt-1.5">{{ fPct }}%</p>
          </div>
        </div>

        <!-- ── 3. TABLEAU NUTRITIONNEL ──────────────────── -->
        <div class="bg-white p-5 md:p-6" style="border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.07)">
          <div class="flex items-center justify-between mb-4">
            <p class="text-xs font-bold uppercase tracking-widest text-neutral-400">Valeurs nutritionnelles</p>
            <p class="text-xs font-semibold text-neutral-400">{{ serving.serving_description }}</p>
          </div>
          <div class="divide-y divide-neutral-100">
            <div
              v-for="row in nutritionRows"
              :key="row.label"
              class="flex items-center justify-between gap-4 py-3"
            >
              <span
                class="text-sm"
                :class="{
                  'pl-5 text-neutral-400': row.indent,
                  'font-bold text-neutral-900': row.bold && !row.indent,
                  'text-neutral-600': !row.bold && !row.indent,
                }"
              >{{ row.label }}</span>
              <span class="font-mono text-sm font-bold text-neutral-900 shrink-0">
                {{ fmt(row.val, row.d) }}&thinsp;<span class="font-sans text-xs font-semibold text-neutral-400">{{ row.unit }}</span>
              </span>
            </div>
          </div>
        </div>

        <!-- ── 4. TOUTES LES PORTIONS ──────────────────── -->
        <div
          v-if="servings.length > 1"
          class="bg-white p-5 md:p-6"
          style="border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.07)"
        >
          <div class="flex items-center justify-between mb-4">
            <p class="text-xs font-bold uppercase tracking-widest text-neutral-400">Toutes les portions</p>
            <span class="text-xs font-bold bg-neutral-100 text-neutral-500 rounded-full px-2.5 py-1">{{ servings.length }}</span>
          </div>
          <div class="space-y-1">
            <button
              v-for="item in servings"
              :key="item.serving_id"
              @click="selectedServingId = item.serving_id"
              class="w-full text-left px-3 py-3 transition-all"
              style="border-radius: 12px"
              :class="selectedServingId === item.serving_id ? 'ring-1 ring-green-300' : 'hover:bg-neutral-50'"
              :style="selectedServingId === item.serving_id ? 'background: oklch(97% 0.04 145)' : ''"
            >
              <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-neutral-900 truncate">{{ item.serving_description }}</p>
                  <p v-if="item.metric_serving_amount" class="text-xs text-neutral-400 mt-0.5">
                    {{ fmt(item.metric_serving_amount) }} {{ item.metric_serving_unit }}
                  </p>
                </div>
                <div class="flex items-center gap-4 shrink-0 text-right">
                  <div>
                    <p class="font-mono text-sm font-black text-neutral-900">{{ fmt(item.calories, 0) }}</p>
                    <p class="text-[10px] font-bold text-neutral-400">kcal</p>
                  </div>
                  <div>
                    <p class="font-mono text-sm font-bold" :style="{ color: PC }">{{ fmt(item.protein, 1) }}</p>
                    <p class="text-[10px] font-bold text-neutral-400">prot</p>
                  </div>
                  <div>
                    <p class="font-mono text-sm font-bold text-amber-500">{{ fmt(item.carbohydrate, 1) }}</p>
                    <p class="text-[10px] font-bold text-neutral-400">gluc</p>
                  </div>
                  <div>
                    <p class="font-mono text-sm font-bold" :style="{ color: FC }">{{ fmt(item.fat, 1) }}</p>
                    <p class="text-[10px] font-bold text-neutral-400">lip</p>
                  </div>
                </div>
              </div>
            </button>
          </div>
        </div>

        <!-- ── 5. AJOUTER AU JOURNAL ───────────────────── -->
        <div
          class="bg-white p-5 md:p-6"
          style="border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.07)"
        >
          <p class="text-xs font-bold uppercase tracking-widest text-neutral-400 mb-4">Ajouter au journal</p>

          <!-- Portion + Quantité -->
          <div class="grid grid-cols-2 gap-3 mb-4">
            <div>
              <p class="text-xs font-semibold text-neutral-500 mb-1.5">Portion</p>
              <select
                v-model="selectedServingId"
                class="h-10 w-full rounded-xl border border-neutral-200 bg-neutral-50 px-3 text-sm text-neutral-700 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/20"
              >
                <option v-for="item in servings" :key="item.serving_id" :value="item.serving_id">
                  {{ item.serving_description }}
                </option>
              </select>
            </div>
            <div>
              <p class="text-xs font-semibold text-neutral-500 mb-1.5">Quantité</p>
              <input
                v-model.number="quantity"
                type="number"
                min="0.1"
                step="0.5"
                class="h-10 w-full rounded-xl border border-neutral-200 bg-neutral-50 px-3 text-sm text-neutral-700 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/20"
              >
            </div>
          </div>

          <!-- Repas -->
          <div class="mb-4">
            <p class="text-xs font-semibold text-neutral-500 mb-1.5">Repas</p>
            <div class="grid grid-cols-4 gap-2">
              <button
                v-for="m in MEALS"
                :key="m.key"
                @click="selectedMeal = m.key"
                class="h-9 text-[11px] font-semibold transition-all truncate px-1"
                style="border-radius: 9999px"
                :class="selectedMeal === m.key
                  ? 'bg-green-500 text-white shadow-sm'
                  : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'"
              >{{ m.label }}</button>
            </div>
          </div>

          <!-- Bouton -->
          <button
            @click="addToJournal"
            :disabled="addingFood"
            class="w-full h-12 font-bold text-sm text-white bg-green-500 hover:bg-green-600 transition-all shadow-sm hover:-translate-y-px active:translate-y-0 disabled:opacity-50 flex items-center justify-center gap-2"
            style="border-radius: 9999px"
          >
            <Loader2 v-if="addingFood" :size="16" class="animate-spin" />
            <span v-else>Ajouter au journal · {{ fmt(totalCal, 0) }} kcal</span>
          </button>

          <a
            v-if="food.food_url"
            :href="food.food_url"
            target="_blank"
            rel="noreferrer"
            class="mt-3 w-full h-9 inline-flex items-center justify-center gap-1.5 border border-neutral-200 text-sm font-semibold text-neutral-500 hover:border-green-300 hover:text-green-600 transition-colors"
            style="border-radius: 9999px"
          >
            Voir sur FatSecret <ExternalLink :size="12" />
          </a>
        </div>

        <!-- ── 6. ALLERGÈNES ───────────────────────────── -->
        <div
          v-if="allergens.length || preferences.length"
          class="bg-white p-5 md:p-6"
          style="border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.07)"
        >
          <p class="text-xs font-bold uppercase tracking-widest text-neutral-400 mb-4">Allergènes & préférences</p>
          <div class="grid gap-5 sm:grid-cols-2">
            <div v-if="allergens.length">
              <p class="text-[10px] font-bold uppercase tracking-widest text-neutral-300 mb-3">Allergènes</p>
              <div class="space-y-2">
                <div v-for="item in allergens" :key="item.id" class="flex items-center justify-between gap-3">
                  <span class="text-sm text-neutral-600">{{ item.name }}</span>
                  <span
                    class="text-[10px] font-bold rounded-full px-2.5 py-1"
                    :class="item.value === 1 ? 'bg-red-50 text-red-500' : item.value === 0 ? 'bg-green-50 text-green-600' : 'bg-neutral-100 text-neutral-400'"
                  >{{ attrState(item.value) }}</span>
                </div>
              </div>
            </div>
            <div v-if="preferences.length">
              <p class="text-[10px] font-bold uppercase tracking-widest text-neutral-300 mb-3">Préférences</p>
              <div class="space-y-2">
                <div v-for="item in preferences" :key="item.id" class="flex items-center justify-between gap-3">
                  <span class="text-sm text-neutral-600">{{ item.name }}</span>
                  <span
                    class="text-[10px] font-bold rounded-full px-2.5 py-1"
                    :class="item.value === 1 ? 'bg-green-50 text-green-600' : item.value === 0 ? 'bg-red-50 text-red-500' : 'bg-neutral-100 text-neutral-400'"
                  >{{ attrState(item.value) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Toast -->
    <Transition name="toast">
      <div
        v-if="toast"
        class="fixed bottom-20 md:bottom-6 left-1/2 -translate-x-1/2 bg-neutral-900 text-white text-sm font-medium px-5 py-2.5 rounded-full shadow-xl z-50 whitespace-nowrap"
      >
        {{ toast }}
      </div>
    </Transition>
  </MainLayout>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(-50%) translateY(10px); }
</style>
