import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // Les variantes de badge sont composées dynamiquement (badge-{ton}) : Tailwind ne peut pas les détecter.
    safelist: ['badge-ok', 'badge-warn', 'badge-bad', 'badge-neutral', 'badge-ember'],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Fraunces', 'Georgia', ...defaultTheme.fontFamily.serif],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Identité : encre bleu nuit, papier chaud, braise (le signal qui passe de relais en relais).
                ink: { DEFAULT: '#0e1322', 800: '#161c30', 700: '#1f2742', 600: '#2b3556', 500: '#46517a' },
                paper: { DEFAULT: '#f6f3ec', 200: '#ece7db' },
                ember: { 300: '#fbb98d', 400: '#f78b4d', 500: '#ef6a2b', 600: '#d4551a', 700: '#a94213' },
                moss: '#2f7d5b',
                brick: '#b3402f',
                amber: { DEFAULT: '#b7791f' },
            },
            boxShadow: {
                card: '0 1px 2px rgba(14,19,34,.05), 0 10px 28px -14px rgba(14,19,34,.18)',
            },
        },
    },

    plugins: [forms],
};
