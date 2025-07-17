import { z } from 'zod'

export const UserRoleSchema = z.enum(['super_admin', 'admin', 'user', 'guest'])
export type UserRole = z.infer<typeof UserRoleSchema>

export const UserSchema = z.object({
  id: z.string(),
  email: z.string().email(),
  name: z.string().optional(),
  role: UserRoleSchema,
  companyId: z.string(),
  branchIds: z.array(z.string()).optional(),
  permissions: z.array(z.string()).optional(),
  avatar: z.string().url().optional(),
  emailVerified: z.boolean().default(false),
  active: z.boolean().default(true),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
})

export type User = z.infer<typeof UserSchema>

export const SessionSchema = z.object({
  user: UserSchema,
  accessToken: z.string(),
  refreshToken: z.string().optional(),
  expiresAt: z.number(),
})

export type Session = z.infer<typeof SessionSchema>

export const LoginSchema = z.object({
  email: z.string().email(),
  password: z.string().min(8),
  remember: z.boolean().optional(),
})

export type LoginInput = z.infer<typeof LoginSchema>

export const RegisterSchema = z.object({
  email: z.string().email(),
  password: z.string().min(8),
  name: z.string().min(2),
  companyName: z.string().min(2),
  phone: z.string().optional(),
})

export type RegisterInput = z.infer<typeof RegisterSchema>

export interface AuthConfig {
  jwtSecret: string
  jwtExpiry: string
  refreshTokenExpiry: string
  sessionCookieName: string
  secureCookies: boolean
  apiUrl: string
}