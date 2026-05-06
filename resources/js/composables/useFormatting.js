export function useFormatting() {
    const number = (value, digits = 1) => {
        if (value === null || value === undefined || value === '') return '-'

        const parsed = Number(value)

        return Number.isFinite(parsed) ? parsed.toLocaleString('fr-FR', { maximumFractionDigits: digits }) : '-'
    }

    const date = (value, options = {}) => {
        if (!value) return ''

        return new Date(value).toLocaleDateString('fr-FR', options)
    }

    const isoToday = () => new Date().toISOString().split('T')[0]

    return { number, date, isoToday }
}
