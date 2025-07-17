# Development Guide

## 🛠 Development Setup

### System Requirements

- **Node.js**: 20.x oder höher
- **npm**: 10.x oder höher
- **Git**: 2.x oder höher
- **OS**: macOS, Linux, oder Windows mit WSL2

### IDE Setup

#### VS Code (Empfohlen)

Installiere folgende Extensions:
- ESLint
- Prettier
- Tailwind CSS IntelliSense
- TypeScript Vue Plugin (Volar)
- Prisma
- GitLens

Settings (`/.vscode/settings.json`):
```json
{
  "editor.formatOnSave": true,
  "editor.defaultFormatter": "esbenp.prettier-vscode",
  "editor.codeActionsOnSave": {
    "source.fixAll.eslint": true
  },
  "typescript.tsdk": "node_modules/typescript/lib",
  "tailwindCSS.experimental.classRegex": [
    ["cva\\(([^)]*)\\)", "[\"'`]([^\"'`]*).*?[\"'`]"],
    ["cx\\(([^)]*)\\)", "(?:'|\"|`)([^']*)(?:'|\"|`)"]
  ]
}
```

## 🏃‍♂️ Local Development

### First Time Setup

```bash
# Clone repository
git clone <repo-url>
cd askproai-monorepo

# Install dependencies
npm install

# Setup git hooks
npm run prepare

# Create env files
cp apps/admin/.env.example apps/admin/.env.local
cp apps/business/.env.example apps/business/.env.local

# Start development
npm run dev
```

### Daily Workflow

```bash
# Start all services
npm run dev

# Start specific app
npm run dev:admin
npm run dev:business

# Run Storybook
npm run storybook

# Run tests in watch mode
npm run test:watch
```

## 📁 Project Structure

```
askproai-monorepo/
├── apps/
│   ├── admin/              # Admin Portal
│   │   ├── app/           # Next.js App Router
│   │   ├── components/    # App-specific components
│   │   ├── hooks/         # App-specific hooks
│   │   ├── lib/           # App utilities
│   │   └── public/        # Static assets
│   └── business/          # Business Portal (same structure)
│
├── packages/
│   ├── ui/                # Shared UI Library
│   │   ├── src/
│   │   │   ├── components/
│   │   │   ├── hooks/
│   │   │   ├── providers/
│   │   │   └── styles/
│   │   └── tsup.config.ts
│   │
│   ├── config/            # Shared Configurations
│   │   ├── eslint/
│   │   ├── tailwind/
│   │   └── typescript/
│   │
│   ├── auth/              # Authentication Service
│   │   ├── src/
│   │   │   ├── providers/
│   │   │   ├── hooks/
│   │   │   └── utils/
│   │   └── package.json
│   │
│   └── api-client/        # Type-safe API Client
│       ├── src/
│       │   ├── client/
│       │   ├── types/
│       │   └── utils/
│       └── package.json
│
├── services/              # Microservices (optional)
│   └── gateway/          # API Gateway
│
└── tools/                # Build tools & scripts
    └── scripts/
```

## 🧩 Component Development

### Creating a New Component

```bash
# Generate component with CLI
npm run generate:component Button

# Or manually create files:
# packages/ui/src/components/button.tsx
# packages/ui/src/components/button.stories.tsx
# packages/ui/src/components/button.test.tsx
```

### Component Template

```tsx
// packages/ui/src/components/my-component.tsx
import * as React from 'react'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '../lib/utils'

const myComponentVariants = cva(
  'base-classes',
  {
    variants: {
      variant: {
        default: 'default-classes',
        primary: 'primary-classes',
      },
      size: {
        sm: 'text-sm',
        md: 'text-base',
        lg: 'text-lg',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'md',
    },
  }
)

export interface MyComponentProps
  extends React.HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof myComponentVariants> {
  // Additional props
}

export const MyComponent = React.forwardRef<HTMLDivElement, MyComponentProps>(
  ({ className, variant, size, ...props }, ref) => {
    return (
      <div
        ref={ref}
        className={cn(myComponentVariants({ variant, size, className }))}
        {...props}
      />
    )
  }
)

MyComponent.displayName = 'MyComponent'
```

### Story Template

```tsx
// packages/ui/src/components/my-component.stories.tsx
import type { Meta, StoryObj } from '@storybook/react'
import { MyComponent } from './my-component'

const meta = {
  title: 'Components/MyComponent',
  component: MyComponent,
  parameters: {
    layout: 'centered',
  },
  tags: ['autodocs'],
} satisfies Meta<typeof MyComponent>

export default meta
type Story = StoryObj<typeof meta>

export const Default: Story = {
  args: {
    children: 'Hello World',
  },
}
```

## 🧪 Testing

### Unit Tests

```tsx
// component.test.tsx
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MyComponent } from './my-component'

describe('MyComponent', () => {
  it('renders correctly', () => {
    render(<MyComponent>Test</MyComponent>)
    expect(screen.getByText('Test')).toBeInTheDocument()
  })

  it('handles click events', async () => {
    const handleClick = vi.fn()
    const user = userEvent.setup()
    
    render(<MyComponent onClick={handleClick}>Click me</MyComponent>)
    await user.click(screen.getByText('Click me'))
    
    expect(handleClick).toHaveBeenCalledTimes(1)
  })
})
```

### Integration Tests

```tsx
// app.test.tsx
import { render, screen, waitFor } from '@testing-library/react'
import { mockServer } from '@/test/mock-server'
import { App } from './app'

beforeAll(() => mockServer.listen())
afterEach(() => mockServer.resetHandlers())
afterAll(() => mockServer.close())

test('loads and displays data', async () => {
  render(<App />)
  
  await waitFor(() => {
    expect(screen.getByText('Dashboard')).toBeInTheDocument()
  })
})
```

### E2E Tests

```ts
// e2e/login.spec.ts
import { test, expect } from '@playwright/test'

test('user can login', async ({ page }) => {
  await page.goto('/login')
  
  await page.fill('[name="email"]', 'test@example.com')
  await page.fill('[name="password"]', 'password')
  await page.click('button[type="submit"]')
  
  await expect(page).toHaveURL('/dashboard')
  await expect(page.locator('h1')).toContainText('Dashboard')
})
```

## 🎨 Styling Guidelines

### Tailwind CSS Best Practices

```tsx
// ✅ Good - using design tokens
<div className="bg-background text-foreground border-border" />

// ❌ Bad - hardcoded colors
<div className="bg-white text-gray-900 border-gray-200" />

// ✅ Good - responsive design
<div className="p-4 md:p-6 lg:p-8" />

// ✅ Good - using cn() for conditional classes
<div className={cn(
  "base-classes",
  isActive && "active-classes",
  className
)} />
```

### CSS Custom Properties

```css
/* Use semantic tokens */
.my-component {
  color: hsl(var(--foreground));
  background: hsl(var(--background));
  border-color: hsl(var(--border));
}

/* Dark mode automatically handled */
```

## 🔧 Debugging

### Debug Mode

```bash
# Enable debug logging
DEBUG=* npm run dev

# Debug specific module
DEBUG=app:* npm run dev
```

### Chrome DevTools

1. Open Chrome DevTools
2. Go to Sources → Filesystem
3. Add project folder for live editing
4. Use React Developer Tools extension

### VS Code Debugging

```json
// .vscode/launch.json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Next.js: debug",
      "type": "node-terminal",
      "request": "launch",
      "command": "npm run dev:admin",
      "cwd": "${workspaceFolder}",
      "console": "integratedTerminal"
    }
  ]
}
```

## 📦 Package Management

### Adding Dependencies

```bash
# Add to root workspace
npm install <package> -w

# Add to specific app
npm install <package> -w apps/admin

# Add to specific package
npm install <package> -w packages/ui

# Add dev dependency
npm install -D <package> -w packages/ui
```

### Creating New Package

```bash
# Create package directory
mkdir packages/my-package
cd packages/my-package

# Initialize package
npm init -y

# Update package.json
{
  "name": "@askproai/my-package",
  "version": "0.0.0",
  "main": "./dist/index.js",
  "types": "./dist/index.d.ts",
  "scripts": {
    "build": "tsup",
    "dev": "tsup --watch"
  }
}
```

## 🚀 Performance Optimization

### Code Splitting

```tsx
// Lazy load heavy components
const HeavyComponent = lazy(() => import('./HeavyComponent'))

// Use Suspense
<Suspense fallback={<Loading />}>
  <HeavyComponent />
</Suspense>
```

### Image Optimization

```tsx
import Image from 'next/image'

// Always use Next.js Image component
<Image
  src="/hero.jpg"
  alt="Hero"
  width={1200}
  height={600}
  priority // for above-the-fold images
  placeholder="blur"
  blurDataURL={blurDataUrl}
/>
```

### Bundle Analysis

```bash
# Analyze bundle size
npm run analyze:admin
npm run analyze:business

# Check package size
npm run size
```

## 🐛 Common Issues

### Module Resolution

```bash
# Clear cache and reinstall
rm -rf node_modules package-lock.json
npm install
```

### TypeScript Errors

```bash
# Rebuild packages
npm run build:packages

# Clear TypeScript cache
rm -rf .next apps/*/.next
```

### Port Conflicts

```bash
# Kill process on port
lsof -ti:3000 | xargs kill -9

# Use different ports
PORT=3001 npm run dev:admin
```

## 📚 Resources

- [Next.js Documentation](https://nextjs.org/docs)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [Radix UI](https://www.radix-ui.com/docs)
- [Turborepo](https://turbo.build/repo/docs)
- [TypeScript](https://www.typescriptlang.org/docs)