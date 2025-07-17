# AskProAI Monorepo

State-of-the-art unified platform for Admin and Business portals with perfect harmonization.

[![CI](https://github.com/askproai/monorepo/actions/workflows/ci.yml/badge.svg)](https://github.com/askproai/monorepo/actions/workflows/ci.yml)
[![Deploy](https://github.com/askproai/monorepo/actions/workflows/deploy.yml/badge.svg)](https://github.com/askproai/monorepo/actions/workflows/deploy.yml)
[![License](https://img.shields.io/badge/license-Proprietary-red.svg)](LICENSE)

## 🏗 Architecture

This monorepo uses [Turborepo](https://turbo.build/) and contains:

### Apps
- `admin` - Admin Portal (Next.js 14 with App Router)
- `business` - Business Portal (Next.js 14 with App Router)  
- `api` - Laravel Backend (existing)

### Packages
- `@askproai/ui` - Shared UI component library (Shadcn/ui + Tailwind)
- `@askproai/utils` - Shared utilities and helpers
- `@askproai/types` - Shared TypeScript types
- `@askproai/config` - Shared configuration (ESLint, TypeScript, Tailwind)

### Services
- `@askproai/auth` - Authentication service
- `@askproai/api-client` - API client with type safety
- `@askproai/realtime` - WebSocket service for real-time features

## 🚀 Getting Started

### Prerequisites
- Node.js 18+ 
- npm 10+
- PHP 8.2+ (for Laravel backend)

### Installation
```bash
# Install dependencies
npm install

# Setup environment variables
cp apps/admin/.env.example apps/admin/.env.local
cp apps/business/.env.example apps/business/.env.local

# Run development servers
npm run dev
```

### Available Scripts
- `npm run dev` - Start all apps in development mode
- `npm run build` - Build all apps for production
- `npm run test` - Run tests across all packages
- `npm run lint` - Lint all packages
- `npm run format` - Format code with Prettier

## 🎨 Design System

We use a custom design system built on top of:
- **Tailwind CSS** for utility-first styling
- **Shadcn/ui** for high-quality, accessible components
- **Framer Motion** for animations
- **Radix UI** for unstyled, accessible primitives

## 📱 Mobile First

All applications are built with a mobile-first approach:
- Progressive Web App (PWA) capabilities
- Touch-optimized interactions
- Responsive breakpoints: 320px, 768px, 1024px, 1440px
- Offline support with service workers

## 🔐 Security

- JWT-based authentication
- Role-based access control (RBAC)
- API rate limiting
- CSRF protection
- XSS prevention
- SQL injection protection

## 📊 Performance

Target metrics:
- Lighthouse score: 95+
- First Contentful Paint: < 1.5s
- Time to Interactive: < 3.5s
- Core Web Vitals: All green

## 🧪 Testing

- Unit tests with Vitest
- Integration tests with React Testing Library
- E2E tests with Playwright
- Visual regression tests with Chromatic

## 📦 Deployment

The monorepo supports multiple deployment strategies:
- Vercel for Next.js apps
- Docker containers
- Traditional VPS deployment
- CI/CD with GitHub Actions

## 🤝 Contributing

Please read our contributing guidelines before submitting PRs.

## 📄 License

Proprietary - All rights reserved