import preset from './vendor/filament/support/tailwind.config.preset'
import colors from 'tailwindcss/colors'

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
        ...colors,
        primary: '#0075BF',
        secondary: '#073568',
      },
    },
  },
  plugins: [],
}
