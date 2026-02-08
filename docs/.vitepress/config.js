import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'AskPro API Gateway',
  description: 'Multi-tenant AI Voice Agent & Appointment Platform',

  head: [
    ['link', { rel: 'icon', href: '/favicon.ico' }],
    ['meta', { name: 'theme-color', content: '#3b82f6' }],
    ['meta', { name: 'og:type', content: 'website' }],
    ['meta', { name: 'og:title', content: 'AskPro API Gateway Documentation' }],
  ],

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'Guide', link: '/guide/' },
      { text: 'API Reference', link: '/api/' },
      { text: 'Reference', link: '/reference/' },
      {
        text: 'Interactive API',
        link: '/docs/api',
        target: '_blank'
      }
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Getting Started',
          items: [
            { text: 'Introduction', link: '/guide/' },
            { text: 'Quick Start', link: '/guide/quick-start' },
            { text: 'Architecture', link: '/guide/architecture' },
          ]
        },
        {
          text: 'Core Features',
          items: [
            { text: 'Service Gateway', link: '/guide/service-gateway' },
            { text: 'Multi-Tenancy', link: '/guide/multi-tenancy' },
            { text: 'Security', link: '/guide/security' },
          ]
        },
        {
          text: 'Integrations',
          items: [
            { text: 'Retell.ai Voice Agent', link: '/guide/retell' },
            { text: 'Cal.com Scheduling', link: '/guide/calcom' },
            { text: 'Webhooks', link: '/guide/webhooks' },
          ]
        }
      ],
      '/api/': [
        {
          text: 'API Overview',
          items: [
            { text: 'Introduction', link: '/api/' },
            { text: 'Authentication', link: '/api/authentication' },
            { text: 'Rate Limiting', link: '/api/rate-limiting' },
          ]
        },
        {
          text: 'Endpoints',
          items: [
            { text: 'Retell Webhooks', link: '/api/retell-webhooks' },
            { text: 'Cal.com Webhooks', link: '/api/calcom-webhooks' },
            { text: 'Service Gateway', link: '/api/service-gateway' },
          ]
        }
      ],
      '/reference/': [
        {
          text: 'Reference',
          items: [
            { text: 'Data Models', link: '/reference/' },
            { text: 'Error Codes', link: '/reference/error-codes' },
            { text: 'Changelog', link: '/reference/changelog' },
          ]
        }
      ]
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/fabianSp77/askproai-api' }
    ],

    footer: {
      message: 'AskPro AI Gateway Documentation',
      copyright: 'Copyright 2024-2026 AskPro GmbH'
    },

    search: {
      provider: 'local'
    },

    editLink: {
      pattern: 'https://github.com/fabianSp77/askproai-api/edit/main/docs/:path',
      text: 'Edit this page on GitHub'
    },

    lastUpdated: {
      text: 'Updated at',
      formatOptions: {
        dateStyle: 'full',
        timeStyle: 'medium'
      }
    }
  },

  // Ignore dead links during build (temporary)
  ignoreDeadLinks: true,

  markdown: {
    lineNumbers: true
  },

  // Exclude old documentation files
  srcExclude: [
    '**/ISSUE_TEMPLATE.md',
    '**/BACKLOG_INDEX.md',
    '**/CALCOM_*.md',
    '**/CLAUDE_*.md',
    '**/DAILY_*.md',
    '**/LAUNCH_*.md',
    '**/MORGEN_*.md',
    '**/MYSQL_*.md',
    '**/PHONE_*.md',
    '**/PRODUCTION_*.md',
    '**/PR_*.md',
    '**/RESEARCH_*.md',
    '**/RETELL_*.md',
    '**/REVIEW_*.md',
    '**/ROLLBACK_*.md',
    '**/STAGING_*.md',
    '**/TAG3_*.md',
    '**/TASKS_*.md',
    '**/TESTPLAN_*.md',
    '**/WEBHOOK_VS_*.md',
    '**/column-manager-*.md',
    '**/CONTEXT/**',
    '**/e2e/**',
    '**/evidence/**',
    '**/profiling/**',
    '**/service-gateway/**',
    '**/*.txt'
  ],

  // Build output to public folder for Laravel serving
  outDir: '../public/docs-site',
  base: '/docs-site/'
})
