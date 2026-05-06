import { describe, expect, it } from 'vitest'
import { useFormatting } from '../../resources/js/composables/useFormatting'

describe('useFormatting', () => {
    it('formats nutrition values for French users', () => {
        const { number } = useFormatting()

        expect(number(12.345, 1)).toBe('12,3')
        expect(number(null)).toBe('-')
    })
})
