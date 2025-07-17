import { LoginInput, RegisterInput, Session, User } from './types'

export interface AuthClient {
  login(input: LoginInput): Promise<Session>
  register(input: RegisterInput): Promise<Session>
  logout(): Promise<void>
  refresh(): Promise<Session>
  getSession(): Promise<Session | null>
  getUser(): Promise<User | null>
  updateUser(data: Partial<User>): Promise<User>
  verifyEmail(token: string): Promise<void>
  resetPassword(email: string): Promise<void>
  confirmResetPassword(token: string, password: string): Promise<void>
}

export class ApiAuthClient implements AuthClient {
  constructor(private apiUrl: string) {}

  async login(input: LoginInput): Promise<Session> {
    const response = await fetch(`${this.apiUrl}/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(input),
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Login failed')
    }

    return response.json()
  }

  async register(input: RegisterInput): Promise<Session> {
    const response = await fetch(`${this.apiUrl}/auth/register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(input),
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Registration failed')
    }

    return response.json()
  }

  async logout(): Promise<void> {
    await fetch(`${this.apiUrl}/auth/logout`, {
      method: 'POST',
      credentials: 'include',
    })
  }

  async refresh(): Promise<Session> {
    const response = await fetch(`${this.apiUrl}/auth/refresh`, {
      method: 'POST',
      credentials: 'include',
    })

    if (!response.ok) {
      throw new Error('Failed to refresh session')
    }

    return response.json()
  }

  async getSession(): Promise<Session | null> {
    try {
      const response = await fetch(`${this.apiUrl}/auth/session`, {
        credentials: 'include',
      })

      if (!response.ok) {
        return null
      }

      return response.json()
    } catch {
      return null
    }
  }

  async getUser(): Promise<User | null> {
    try {
      const response = await fetch(`${this.apiUrl}/auth/user`, {
        credentials: 'include',
      })

      if (!response.ok) {
        return null
      }

      return response.json()
    } catch {
      return null
    }
  }

  async updateUser(data: Partial<User>): Promise<User> {
    const response = await fetch(`${this.apiUrl}/auth/user`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(data),
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to update user')
    }

    return response.json()
  }

  async verifyEmail(token: string): Promise<void> {
    const response = await fetch(`${this.apiUrl}/auth/verify-email`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token }),
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Email verification failed')
    }
  }

  async resetPassword(email: string): Promise<void> {
    const response = await fetch(`${this.apiUrl}/auth/reset-password`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email }),
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Password reset failed')
    }
  }

  async confirmResetPassword(token: string, password: string): Promise<void> {
    const response = await fetch(`${this.apiUrl}/auth/confirm-reset-password`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token, password }),
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Password reset confirmation failed')
    }
  }
}