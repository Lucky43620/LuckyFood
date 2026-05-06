<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Leaf, LayoutDashboard, BookOpen, Search, ChefHat, TrendingUp, Target, Settings, Star } from 'lucide-vue-next'

const page = usePage()

const NAV_ITEMS = [
    {
        id: 'dashboard',
        label: 'Tableau de bord',
        icon: LayoutDashboard,
        href: () => route('dashboard'),
        match: 'Dashboard',
    },
    { id: 'log', label: 'Journal', icon: BookOpen, href: () => route('journal.index'), match: 'Journal' },
    { id: 'search', label: 'Rechercher', icon: Search, href: () => route('search.index'), match: 'Search' },
    { id: 'favorites', label: 'Favoris', icon: Star, href: () => route('favorites.index'), match: 'Favoris' },
    { id: 'recipes', label: 'Recettes', icon: ChefHat, href: () => route('recipes.index'), match: 'Recettes' },
    { id: 'progress', label: 'Progression', icon: TrendingUp, href: () => route('progression'), match: 'Progression' },
    { id: 'goals', label: 'Objectifs', icon: Target, href: () => route('goals.index'), match: 'Objectifs' },
]

const currentComponent = computed(() => page.component)
const isActive = (match) => currentComponent.value === match || currentComponent.value.startsWith(match + '/')

const user = computed(() => page.props.auth.user)
const initials = computed(() =>
    (user.value?.name ?? 'U')
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2),
)
</script>

<template>
    <aside
        class="sticky top-0 hidden h-screen w-[220px] shrink-0 flex-col border-r border-neutral-100 bg-white md:flex"
    >
        <!-- Logo -->
        <div class="flex items-center gap-2.5 border-b border-neutral-100 px-5 py-6">
            <div class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-md bg-green-500">
                <Leaf :size="18" class="text-white" />
            </div>
            <span class="font-display text-xl tracking-tight text-neutral-900">LuckyFood</span>
        </div>

        <!-- Navigation -->
        <nav class="flex flex-1 flex-col gap-0.5 overflow-y-auto px-2.5 py-3">
            <Link
                v-for="item in NAV_ITEMS"
                :key="item.id"
                :href="item.href()"
                class="flex items-center gap-2.5 rounded-md px-3 py-[9px] text-[14px] transition-colors duration-150"
                :class="
                    isActive(item.match)
                        ? 'bg-green-50 font-semibold text-green-600'
                        : 'font-medium text-neutral-500 hover:bg-neutral-50 hover:text-neutral-700'
                "
            >
                <component :is="item.icon" :size="16" class="shrink-0" />
                {{ item.label }}
            </Link>
        </nav>

        <!-- User footer -->
        <div class="border-t border-neutral-100 p-3.5">
            <div class="flex items-center gap-2.5">
                <div
                    class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-full bg-green-100 text-xs font-bold text-green-600"
                >
                    {{ initials }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[13px] font-semibold text-neutral-800">{{ user?.name }}</p>
                    <p class="text-[11px] text-neutral-400">Forfait gratuit</p>
                </div>
                <Link
                    :href="route('profile.show')"
                    class="p-1 text-neutral-400 transition-colors hover:text-neutral-600"
                    aria-label="Profil"
                    title="Profil"
                >
                    <Settings :size="15" />
                </Link>
            </div>
        </div>
    </aside>
</template>
