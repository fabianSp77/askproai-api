import type { Metadata } from 'next'
import { Inter } from 'next/font/google'
import { ThemeProvider } from '@askproai/ui'
import '@askproai/ui/styles/globals.css'
import '@askproai/ui/styles/admin-tokens.css'

const inter = Inter({ subsets: ['latin'] })

export const metadata: Metadata = {
  title: 'AskProAI Admin',
  description: 'Admin Portal für AskProAI',
  viewport: 'width=device-width, initial-scale=1, maximum-scale=1',
  themeColor: [
    { media: '(prefers-color-scheme: light)', color: '#ffffff' },
    { media: '(prefers-color-scheme: dark)', color: '#0a0a0a' },
  ],
  manifest: '/manifest.json',
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="de" suppressHydrationWarning>
      <body className={inter.className}>
        <ThemeProvider
          defaultTheme="system"
          enableSystem
          disableTransitionOnChange
        >
          {children}
        </ThemeProvider>
      </body>
    </html>
  )
}