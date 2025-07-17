'use client'

import { createContext, useContext, useEffect, useState, ReactNode } from 'react'
import { useRouter } from 'next/navigation'
import { User, Session, LoginInput, RegisterInput } from './types'
import { ApiAuthClient, AuthClient } from './client'

interface AuthContextValue {
  user: User | null
  session: Session | null
  loading: boolean
  error: Error | null
  login: (input: LoginInput) => Promise<void>
  register: (input: RegisterInput) => Promise<void>
  logout: () => Promise<void>
  refresh: () => Promise<void>
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined)

interface AuthProviderProps {
  children: ReactNode
  apiUrl: string
  redirectTo?: string
}

export function AuthProvider({
  children,
  apiUrl,
  redirectTo = '/login',
}: AuthProviderProps) {
  const [user, setUser] = useState<User | null>(null)
  const [session, setSession] = useState<Session | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<Error | null>(null)
  const router = useRouter()
  
  const client: AuthClient = new ApiAuthClient(apiUrl)

  useEffect(() => {
    checkSession()
  }, [])

  const checkSession = async () => {
    try {
      setLoading(true)
      const session = await client.getSession()
      if (session) {
        setSession(session)
        setUser(session.user)
      }
    } catch (err) {
      setError(err as Error)
    } finally {
      setLoading(false)
    }
  }

  const login = async (input: LoginInput) => {
    try {
      setLoading(true)
      setError(null)
      const session = await client.login(input)
      setSession(session)
      setUser(session.user)
      router.push('/')
    } catch (err) {
      setError(err as Error)
      throw err
    } finally {
      setLoading(false)
    }
  }

  const register = async (input: RegisterInput) => {
    try {
      setLoading(true)
      setError(null)
      const session = await client.register(input)
      setSession(session)
      setUser(session.user)
      router.push('/')
    } catch (err) {
      setError(err as Error)
      throw err
    } finally {
      setLoading(false)
    }
  }

  const logout = async () => {
    try {
      setLoading(true)
      await client.logout()
      setSession(null)
      setUser(null)
      router.push(redirectTo)
    } catch (err) {
      setError(err as Error)
    } finally {
      setLoading(false)
    }
  }

  const refresh = async () => {
    try {
      const session = await client.refresh()
      setSession(session)
      setUser(session.user)
    } catch (err) {
      setError(err as Error)
      await logout()
    }
  }

  return (
    <AuthContext.Provider
      value={{
        user,
        session,
        loading,
        error,
        login,
        register,
        logout,
        refresh,
      }}
    >
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider')
  }
  return context
}

export function useUser() {
  const { user } = useAuth()
  return user
}

export function useSession() {
  const { session } = useAuth()
  return session
}

export function useRequireAuth(redirectTo = '/login') {
  const { user, loading } = useAuth()
  const router = useRouter()

  useEffect(() => {
    if (!loading && !user) {
      router.push(redirectTo)
    }
  }, [user, loading, router, redirectTo])

  return { user, loading }
}

export function usePermission(permission: string) {
  const user = useUser()
  
  if (!user) return false
  if (user.role === 'super_admin') return true
  
  return user.permissions?.includes(permission) ?? false
}

export function useRole(roles: string | string[]) {
  const user = useUser()
  
  if (!user) return false
  
  const allowedRoles = Array.isArray(roles) ? roles : [roles]
  return allowedRoles.includes(user.role)
}