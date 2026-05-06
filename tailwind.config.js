import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'
import typography from '@tailwindcss/typography'

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                display: ['DM Serif Display', 'Georgia', 'serif'],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },

            colors: {
                // Primaire vert
                green: {
                    50: 'oklch(97% 0.04 145)',
                    100: 'oklch(93% 0.08 145)',
                    200: 'oklch(87% 0.12 145)',
                    400: 'oklch(71% 0.18 145)',
                    500: 'oklch(62% 0.18 145)',
                    600: 'oklch(54% 0.17 145)',
                    700: 'oklch(45% 0.15 145)',
                },
                // Accent orange
                orange: {
                    50: 'oklch(97% 0.04 55)',
                    100: 'oklch(93% 0.08 55)',
                    500: 'oklch(72% 0.18 55)',
                    600: 'oklch(64% 0.18 55)',
                },
                // Amber (avertissement)
                amber: {
                    100: 'oklch(95% 0.06 80)',
                    400: 'oklch(80% 0.17 80)',
                    500: 'oklch(73% 0.18 80)',
                    600: 'oklch(65% 0.18 80)',
                    700: 'oklch(57% 0.17 80)',
                },
                // Bleu (eau)
                blue: {
                    100: 'oklch(93% 0.06 240)',
                    400: 'oklch(70% 0.15 240)',
                    500: 'oklch(60% 0.16 240)',
                },
                // Coral (lipides)
                coral: {
                    100: 'oklch(94% 0.06 25)',
                    400: 'oklch(72% 0.17 25)',
                    500: 'oklch(64% 0.18 25)',
                },
                // Neutres chauds
                neutral: {
                    0: '#FFFFFF',
                    25: '#FAFAF8',
                    50: '#F5F4F0',
                    100: '#EEECEA',
                    200: '#E0DDD9',
                    300: '#CCCAC5',
                    400: '#B0ADA7',
                    500: '#8E8B85',
                    600: '#6B6965',
                    700: '#4A4845',
                    800: '#2E2D2B',
                    900: '#1A1917',
                },
            },

            borderRadius: {
                sm: '6px',
                md: '10px',
                lg: '16px',
                xl: '24px',
                pill: '9999px',
            },

            boxShadow: {
                sm: '0 1px 3px rgba(0,0,0,0.06), 0 2px 8px rgba(0,0,0,0.04)',
                md: '0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.07)',
                lg: '0 4px 6px rgba(0,0,0,0.05), 0 10px 30px rgba(0,0,0,0.10)',
            },

            transitionTimingFunction: {
                spring: 'cubic-bezier(0.34, 1.56, 0.64, 1)',
                out: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
        },
    },

    plugins: [forms, typography],
}
