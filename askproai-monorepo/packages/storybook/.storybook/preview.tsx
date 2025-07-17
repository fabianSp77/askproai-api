import React from 'react'
import type { Preview } from '@storybook/react'
import { withThemeByClassName } from '@storybook/addon-themes'
import { ThemeProvider } from '@askproai/ui'

import '@askproai/ui/styles/globals.css'

const preview: Preview = {
  parameters: {
    actions: { argTypesRegex: '^on[A-Z].*' },
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/,
      },
    },
    docs: {
      toc: true,
    },
    backgrounds: {
      default: 'light',
      values: [
        { name: 'light', value: '#ffffff' },
        { name: 'dark', value: '#0a0a0a' },
        { name: 'gray', value: '#f5f5f5' },
      ],
    },
  },
  decorators: [
    withThemeByClassName({
      themes: {
        light: 'light',
        dark: 'dark',
      },
      defaultTheme: 'light',
    }),
    (Story) => (
      <ThemeProvider defaultTheme="light" disableTransitionOnChange>
        <Story />
      </ThemeProvider>
    ),
  ],
}

export default preview