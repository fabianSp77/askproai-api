const baseConfig = require('./base')

/** @type {import('tailwindcss').Config} */
module.exports = {
  ...baseConfig,
  theme: {
    ...baseConfig.theme,
    extend: {
      ...baseConfig.theme.extend,
      colors: {
        ...baseConfig.theme.extend.colors,
        // Business Portal spezifische Farben
        business: {
          primary: 'hsl(var(--business-primary))',
          'primary-hover': 'hsl(var(--business-primary-hover))',
          secondary: 'hsl(var(--business-secondary))',
          'secondary-hover': 'hsl(var(--business-secondary-hover))',
          accent: 'hsl(var(--business-accent))',
          'accent-hover': 'hsl(var(--business-accent-hover))',
        },
        // Call-Status Farben
        call: {
          active: 'hsl(var(--call-active))',
          ended: 'hsl(var(--call-ended))',
          missed: 'hsl(var(--call-missed))',
          failed: 'hsl(var(--call-failed))',
        },
      },
      backgroundImage: {
        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
        'gradient-conic': 'conic-gradient(from 180deg at 50% 50%, var(--tw-gradient-stops))',
        'gradient-mesh': 'url("data:image/svg+xml,%3Csvg width="100" height="100" xmlns="http://www.w3.org/2000/svg"%3E%3Cdefs%3E%3Cpattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"%3E%3Cpath d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(0,0,0,0.05)" stroke-width="0.5"/%3E%3C/pattern%3E%3C/defs%3E%3Crect width="100" height="100" fill="url(%23grid)" /%3E%3C/svg%3E")',
      },
      animation: {
        ...baseConfig.theme.extend.animation,
        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'bounce-slow': 'bounce 2s infinite',
        'spin-slow': 'spin 3s linear infinite',
      },
    },
  },
}