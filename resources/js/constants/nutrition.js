import { Apple, Moon, Sun, Utensils } from 'lucide-vue-next'

export const MEALS = [
    { key: 'breakfast', label: 'Petit-déjeuner' },
    { key: 'lunch', label: 'Déjeuner' },
    { key: 'snack', label: 'Collation' },
    { key: 'dinner', label: 'Dîner' },
]

export const MEAL_CONFIGS = [
    { key: 'breakfast', name: 'Petit-déjeuner', icon: Sun, iconBg: 'bg-amber-100', iconColor: 'text-amber-600' },
    { key: 'lunch', name: 'Déjeuner', icon: Utensils, iconBg: 'bg-green-50', iconColor: 'text-green-600' },
    { key: 'snack', name: 'Collation', icon: Apple, iconBg: 'bg-orange-50', iconColor: 'text-orange-500' },
    { key: 'dinner', name: 'Dîner', icon: Moon, iconBg: 'bg-neutral-100', iconColor: 'text-neutral-500' },
]

export const AVAILABLE_RECIPE_TAGS = [
    'Haut en protéines',
    'Végétarien',
    'Végan',
    'Rapide',
    'Sans gluten',
    'Faible en calories',
]

export const RECIPE_CATEGORY_COLORS = {
    'Haut en protéines': 'green',
    Végétarien: 'amber',
    Végan: 'blue',
    'Sans gluten': 'orange',
    Rapide: 'neutral',
}
