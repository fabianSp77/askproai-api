import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                danger: {
                    '50': 'rgb(var(--color-danger-50))',
                    '100': 'rgb(var(--color-danger-100))',
                    '200': 'rgb(var(--color-danger-200))',
                    '300': 'rgb(var(--color-danger-300))',
                    '400': 'rgb(var(--color-danger-400))',
                    '500': 'rgb(var(--color-danger-500))',
                    '600': 'rgb(var(--color-danger-600))',
                    '700': 'rgb(var(--color-danger-700))',
                    '800': 'rgb(var(--color-danger-800))',
                    '900': 'rgb(var(--color-danger-900))',
                    '950': 'rgb(var(--color-danger-950))',
                },
            },
        },
    },
}
