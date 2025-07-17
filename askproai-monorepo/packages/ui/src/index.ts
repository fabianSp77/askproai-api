// Core components
export * from './components/button'
export * from './components/card'
export * from './components/dialog'
export * from './components/dropdown-menu'
export * from './components/input'
export * from './components/label'
export * from './components/select'
export * from './components/separator'
export * from './components/switch'
export * from './components/tabs'
export * from './components/textarea'
export * from './components/toast'
export * from './components/tooltip'

// Layout components
export * from './components/layout/header'
export * from './components/layout/sidebar'
export * from './components/layout/mobile-nav'

// Providers
export * from './providers/theme-provider'

// Utility exports
export * from './lib/utils'

// Hooks
export * from './hooks/use-toast'
export * from './hooks/use-mobile'
export * from './hooks/use-theme'

// Style imports - für direkte Imports in Apps
export const styles = {
  global: './styles/globals.css',
  adminTokens: './styles/admin-tokens.css',
  businessTokens: './styles/business-tokens.css',
} as const