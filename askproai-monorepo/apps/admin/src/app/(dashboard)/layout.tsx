'use client'

import { AdminLayout } from '@/components/layout/admin-layout'
import { AuthProvider } from '@askproai/auth'

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <AuthProvider apiUrl={process.env.NEXT_PUBLIC_API_URL || 'https://api.askproai.de'}>
      <AdminLayout>{children}</AdminLayout>
    </AuthProvider>
  )
}