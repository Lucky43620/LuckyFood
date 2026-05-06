<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { LayoutDashboard, BookOpen, Search, ChefHat, TrendingUp } from 'lucide-vue-next'

const page = usePage()

const NAV_ITEMS = [
    { id: 'dashboard', label: 'Accueil', icon: LayoutDashboard, href: () => route('dashboard'), match: 'Dashboard' },
    { id: 'log', label: 'Journal', icon: BookOpen, href: () => route('journal.index'), match: 'Journal' },
    { id: 'search', label: 'Rechercher', icon: Search, href: () => route('search.index'), match: 'Search' },
    { id: 'recipes', label: 'Recettes', icon: ChefHat, href: () => route('recipes.index'), match: 'Recettes' },
    { id: 'progress', label: 'Progression', icon: TrendingUp, href: () => route('progression'), match: 'Progression' },
]

const currentComponent = computed(() => page.component)
const isActive = (match) => currentComponent.value === match || currentComponent.value.startsWith(match + '/')
</script>

<template>
    <nav
        class="fixed inset-x-0 bottom-0 z-50 border-t border-neutral-100 bg-white pb-[env(safe-area-inset-bottom)] md:hidden"
    >
        <div class="flex">
            <Link
                v-for="item in NAV_ITEMS"
                :key="item.id"
                :href="item.href()"
                class="flex flex-1 flex-col items-center gap-1 py-2.5 transition-colors duration-150"
                :class="isActive(item.match) ? 'text-green-600' : 'text-neutral-400'"
            >
                <component :is="item.icon" :size="20" />
                <span class="text-[10px] font-medium leading-none">{{ item.label }}</span>
            </Link>
        </div>
    </nav>
</template>
