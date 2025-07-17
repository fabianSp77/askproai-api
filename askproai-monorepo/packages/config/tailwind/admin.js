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
        // Admin-spezifische Farben
        admin: {
          sidebar: 'hsl(var(--admin-sidebar))',
          'sidebar-hover': 'hsl(var(--admin-sidebar-hover))',
          'sidebar-active': 'hsl(var(--admin-sidebar-active))',
          header: 'hsl(var(--admin-header))',
          'header-border': 'hsl(var(--admin-header-border))',
        },
        // Status-Farben für Admin
        status: {
          scheduled: 'hsl(var(--status-scheduled))',
          confirmed: 'hsl(var(--status-confirmed))',
          completed: 'hsl(var(--status-completed))',
          cancelled: 'hsl(var(--status-cancelled))',
          'no-show': 'hsl(var(--status-no-show))',
        },
      },
      spacing: {
        'sidebar': '16rem',
        'sidebar-collapsed': '4rem',
        'header': '4rem',
      },
      screens: {
        '3xl': '1920px',
        '4xl': '2560px',
      },
      fontSize: {
        '2xs': ['0.625rem', { lineHeight: '0.75rem' }],
      },
      zIndex: {
        'dropdown': '1000',
        'modal': '1050',
        'popover': '1100',
        'tooltip': '1150',
        'notification': '1200',
      },
    },
  },
}