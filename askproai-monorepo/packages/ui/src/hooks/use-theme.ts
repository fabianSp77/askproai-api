import { useEffect, useState } from 'react'

type Theme = 'dark' | 'light' | 'system'

export function useTheme() {
  const [theme, setTheme] = useState<Theme>('system')

  useEffect(() => {
    const root = window.document.documentElement
    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches
      ? 'dark'
      : 'light'

    // Get stored theme or default to system
    const storedTheme = localStorage.getItem('theme') as Theme | null
    const initialTheme = storedTheme || 'system'
    setTheme(initialTheme)

    // Apply theme
    const applyTheme = (theme: Theme) => {
      const effectiveTheme = theme === 'system' ? systemTheme : theme
      root.classList.remove('light', 'dark')
      root.classList.add(effectiveTheme)
    }

    applyTheme(initialTheme)

    // Listen for system theme changes
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    const handleChange = () => {
      if (theme === 'system') {
        applyTheme('system')
      }
    }
    mediaQuery.addEventListener('change', handleChange)

    return () => mediaQuery.removeEventListener('change', handleChange)
  }, [theme])

  const setThemeAndStore = (newTheme: Theme) => {
    setTheme(newTheme)
    localStorage.setItem('theme', newTheme)
  }

  return { theme, setTheme: setThemeAndStore }
}