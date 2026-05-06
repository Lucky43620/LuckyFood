import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

export function useJournalActions() {
    const addingFood = ref(null)
    const toast = ref(null)
    let toastTimer = null

    const showToast = (message) => {
        toast.value = message
        clearTimeout(toastTimer)
        toastTimer = setTimeout(() => {
            toast.value = null
        }, 3000)
    }

    const addFoodToJournal = (food, payload, options = {}) => {
        const loadingKey = options.loadingKey ?? food.food_id ?? true
        addingFood.value = loadingKey

        router.post(
            route('journal.store'),
            {
                food_id: food.food_id,
                food_name: food.food_name,
                ...payload,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => showToast(`${food.food_name} ajouté au journal`),
                onFinish: () => {
                    addingFood.value = null
                },
            },
        )
    }

    return { addingFood, toast, addFoodToJournal, showToast }
}
