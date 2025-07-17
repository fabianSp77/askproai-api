import type { Config } from 'tailwindcss'
import adminConfig from '@askproai/config/tailwind/admin'

const config: Config = {
  ...adminConfig,
  content: [
    './src/**/*.{js,ts,jsx,tsx,mdx}',
    '../../packages/ui/src/**/*.{js,ts,jsx,tsx}',
  ],
}

export default config