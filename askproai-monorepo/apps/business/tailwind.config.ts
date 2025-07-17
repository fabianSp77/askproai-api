import type { Config } from 'tailwindcss'
import businessConfig from '@askproai/config/tailwind/business'

const config: Config = {
  ...businessConfig,
  content: [
    './src/**/*.{js,ts,jsx,tsx,mdx}',
    '../../packages/ui/src/**/*.{js,ts,jsx,tsx}',
  ],
}

export default config