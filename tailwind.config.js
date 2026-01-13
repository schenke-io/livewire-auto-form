/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./workbench/resources/**/*.blade.php",
        "./workbench/resources/**/*.js",
        "./vendor/livewire/flux-pro/stubs/**/*.blade.php",
        "./vendor/livewire/flux/stubs/**/*.blade.php",
    ],
    darkMode: 'selector',
    theme: {
        extend: {
            colors: {
                zinc: {
                    50: '#fafafa',
                    100: '#f4f4f5',
                    200: '#e4e4e7',
                    300: '#d4d4d8',
                    400: '#a1a1aa',
                    500: '#71717a',
                    600: '#52525b',
                    700: '#3f3f46',
                    800: '#27272a',
                    900: '#18181b',
                    950: '#09090b',
                },
                accent: {
                    DEFAULT: 'var(--color-accent)',
                    foreground: 'var(--color-accent-foreground)',
                },
            },
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Ubuntu', 'Cantarell', 'Noto Sans', 'Helvetica Neue', 'Arial', 'Apple Color Emoji', 'Segoe UI Emoji'],
            },
        },
    },
    plugins: [],
}
