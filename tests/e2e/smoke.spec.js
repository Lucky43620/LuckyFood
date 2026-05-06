import { test, expect } from '@playwright/test'

test('home page renders LuckyFood entry points', async ({ page }) => {
    await page.goto('/')

    await expect(page.getByText('LuckyFood').first()).toBeVisible()
    await expect(page.getByRole('link', { name: /connexion/i })).toBeVisible()
})
