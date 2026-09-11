/**
 * Tailwind configuration.
 *
 * The palette below is lifted from the USL My Portal records system so the
 * portfolio looks like it belongs to the same family of school systems:
 * navy chrome, amber rule under the header, cream data rows, pale blue-grey
 * band headers. Colours are named by role, not by hue, so a future rebrand
 * only touches this file.
 */
import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme';

export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Livewire/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                // --- Chrome: header, sidebar, primary actions -----------------
                navy: {
                    900: '#0F2A47', // sidebar rail
                    800: '#14375E', // header bar, primary buttons
                    700: '#1B4677',
                    600: '#265A93',
                    100: '#EAF0F7', // pale band behind table section headers
                },
                // --- Accent: the thin gold rule and status highlights ---------
                amber: {
                    500: '#F0A500',
                    600: '#C98700',
                    100: '#FDF3DC',
                },
                // --- Data rows: the cream banding of the portal's tables ------
                cream: {
                    50: '#FEF8EE',
                    100: '#FCEFD9',
                    200: '#F7E2C0',
                    300: '#EFD3A4',
                },
                // --- Status colours for deadlines and attainment flags --------
                status: {
                    ontrack: '#1E7A4B',
                    watch: '#B7791F',
                    risk: '#B02A24',
                    locked: '#5B6472',
                },
            },
            fontFamily: {
                sans: ['Source Sans 3', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
        },
    },
    plugins: [forms],
};
