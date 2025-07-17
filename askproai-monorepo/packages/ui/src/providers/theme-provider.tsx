'use client'

import * as React from 'react'
import { useTheme } from '../hooks/use-theme'

interface ThemeProviderProps {
  children: React.ReactNode
  defaultTheme?: 'light' | 'dark' | 'system'
  storageKey?: string
  enableSystem?: boolean
  disableTransitionOnChange?: boolean
}

type Theme = 'light' | 'dark' | 'system'

interface ThemeProviderState {
  theme: Theme
  setTheme: (theme: Theme) => void
  systemTheme: 'light' | 'dark'
}

const ThemeProviderContext = React.createContext<ThemeProviderState | undefined>(undefined)

export function ThemeProvider({
  children,
  defaultTheme = 'system',
  storageKey = 'askproai-theme',
  enableSystem = true,
  disableTransitionOnChange = true,
  ...props
}: ThemeProviderProps) {
  const [theme, setThemeState] = React.useState<Theme>(defaultTheme)
  const [systemTheme, setSystemTheme] = React.useState<'light' | 'dark'>('light')

  React.useEffect(() => {
    const root = window.document.documentElement

    // Get system theme
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    setSystemTheme(mediaQuery.matches ? 'dark' : 'light')

    // Get stored theme
    try {
      const storedTheme = localStorage.getItem(storageKey) as Theme | null
      if (storedTheme) {
        setThemeState(storedTheme)
      }
    } catch (e) {
      // Ignore errors from localStorage
    }

    // Listen for system theme changes
    const handleChange = (e: MediaQueryListEvent) => {
      setSystemTheme(e.matches ? 'dark' : 'light')
    }
    
    mediaQuery.addEventListener('change', handleChange)
    return () => mediaQuery.removeEventListener('change', handleChange)
  }, [storageKey])

  const applyTheme = React.useCallback((theme: Theme) => {
    const root = window.document.documentElement
    const effectiveTheme = theme === 'system' ? systemTheme : theme

    // Disable transitions temporarily
    if (disableTransitionOnChange) {
      const css = document.createElement('style')
      css.type = 'text/css'
      css.appendChild(
        document.createTextNode(
          `* {
            -webkit-transition: none !important;
            -moz-transition: none !important;
            -o-transition: none !important;
            -ms-transition: none !important;
            transition: none !important;
          }`
        )
      )
      document.head.appendChild(css)

      // Force browser to flush CSS changes
      window.getComputedStyle(css).opacity
      
      // Re-enable transitions after a frame
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          document.head.removeChild(css)
        })
      })
    }

    // Apply theme class
    root.classList.remove('light', 'dark')
    root.classList.add(effectiveTheme)

    // Set color-scheme for native elements
    root.style.colorScheme = effectiveTheme
  }, [systemTheme, disableTransitionOnChange])

  React.useEffect(() => {
    applyTheme(theme)
  }, [theme, applyTheme])

  const setTheme = React.useCallback((newTheme: Theme) => {
    setThemeState(newTheme)
    
    // Store theme preference
    try {
      localStorage.setItem(storageKey, newTheme)
    } catch (e) {
      // Ignore errors from localStorage
    }
  }, [storageKey])

  const value = React.useMemo(
    () => ({
      theme,
      setTheme,
      systemTheme,
    }),
    [theme, setTheme, systemTheme]
  )

  return (
    <ThemeProviderContext.Provider {...props} value={value}>
      {children}
    </ThemeProviderContext.Provider>
  )
}

export function useThemeContext() {
  const context = React.useContext(ThemeProviderContext)
  if (context === undefined) {
    throw new Error('useThemeContext must be used within a ThemeProvider')
  }
  return context
}