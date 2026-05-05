<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  Leaf, LayoutDashboard, BookOpen, Search,
  ChefHat, TrendingUp, Target, Settings,
} from 'lucide-vue-next'

const page = usePage()

const NAV_ITEMS = [
  { id: 'dashboard', label: 'Tableau de bord', icon: LayoutDashboard, href: () => route('dashboard'),      match: 'Dashboard'   },
  { id: 'log',       label: 'Journal',          icon: BookOpen,        href: () => route('journal.index'),  match: 'Journal'     },
  { id: 'search',    label: 'Rechercher',        icon: Search,          href: () => route('search.index'),   match: 'Search'      },
  { id: 'recipes',   label: 'Recettes',          icon: ChefHat,         href: () => route('recipes.index'),  match: 'Recettes'    },
  { id: 'progress',  label: 'Progression',       icon: TrendingUp,      href: () => route('progression'),    match: 'Progression' },
  { id: 'goals',     label: 'Objectifs',         icon: Target,          href: () => route('goals.index'),    match: 'Objectifs'   },
]

const currentComponent = computed(() => page.component)
const isActive = (match) =>
  currentComponent.value === match || currentComponent.value.startsWith(match + '/')

const user     = computed(() => page.props.auth.user)
const initials = computed(() =>
  (user.value?.name ?? 'U').split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
)

</script>

<template>
  <aside class="hidden md:flex flex-col w-[220px] shrink-0 h-screen sticky top-0 bg-white border-r border-neutral-100">

    <!-- Logo -->
    <div class="flex items-center gap-2.5 px-5 py-6 border-b border-neutral-100">
      <div class="w-[34px] h-[34px] rounded-md bg-green-500 flex items-center justify-center shrink-0">
        <Leaf :size="18" class="text-white" />
      </div>
      <span class="font-display text-xl tracking-tight text-neutral-900">LuckyFood</span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-2.5 py-3 flex flex-col gap-0.5 overflow-y-auto">
      <Link
        v-for="item in NAV_ITEMS"
        :key="item.id"
        :href="item.href()"
        class="flex items-center gap-2.5 px-3 py-[9px] rounded-md text-[14px] transition-colors duration-150"
        :class="isActive(item.match)
          ? 'bg-green-50 text-green-600 font-semibold'
          : 'text-neutral-500 font-medium hover:bg-neutral-50 hover:text-neutral-700'"
      >
        <component :is="item.icon" :size="16" class="shrink-0" />
        {{ item.label }}
      </Link>
    </nav>

    <!-- User footer -->
    <div class="p-3.5 border-t border-neutral-100">
      <div class="flex items-center gap-2.5">
        <div class="w-[34px] h-[34px] rounded-full bg-green-100 flex items-center justify-center text-xs font-bold text-green-600 shrink-0">
          {{ initials }}
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[13px] font-semibold text-neutral-800 truncate">{{ user?.name }}</p>
          <p class="text-[11px] text-neutral-400">Forfait gratuit</p>
        </div>
        <Link
          :href="route('profile.show')"
          class="text-neutral-400 hover:text-neutral-600 p-1 transition-colors"
          aria-label="Profil"
          title="Profil"
        >
          <Settings :size="15" />
        </Link>
      </div>
    </div>

  </aside>
</template>
