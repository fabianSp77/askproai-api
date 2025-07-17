import { z } from 'zod'
import { ApiClient } from '../client'

export const CustomerSchema = z.object({
  id: z.string(),
  companyId: z.string(),
  branchIds: z.array(z.string()).default([]),
  firstName: z.string(),
  lastName: z.string(),
  email: z.string().email().optional(),
  phone: z.string(),
  alternativePhone: z.string().optional(),
  dateOfBirth: z.string().optional(),
  gender: z.enum(['male', 'female', 'other']).optional(),
  address: z.object({
    street: z.string().optional(),
    city: z.string().optional(),
    state: z.string().optional(),
    postalCode: z.string().optional(),
    country: z.string().optional(),
  }).optional(),
  notes: z.string().optional(),
  tags: z.array(z.string()).default([]),
  preferences: z.object({
    communicationMethod: z.enum(['email', 'sms', 'phone', 'none']).optional(),
    reminderTime: z.number().optional(),
    language: z.string().optional(),
  }).optional(),
  statistics: z.object({
    totalAppointments: z.number().default(0),
    completedAppointments: z.number().default(0),
    cancelledAppointments: z.number().default(0),
    noShowCount: z.number().default(0),
    totalSpent: z.number().default(0),
    lastVisit: z.string().datetime().optional(),
  }).optional(),
  active: z.boolean().default(true),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
})

export type Customer = z.infer<typeof CustomerSchema>

export const CreateCustomerSchema = CustomerSchema.omit({
  id: true,
  statistics: true,
  createdAt: true,
  updatedAt: true,
})

export type CreateCustomerInput = z.infer<typeof CreateCustomerSchema>

export const UpdateCustomerSchema = CreateCustomerSchema.partial()

export type UpdateCustomerInput = z.infer<typeof UpdateCustomerSchema>

export class CustomersResource {
  constructor(private client: ApiClient) {}

  async list(params?: {
    branchId?: string
    search?: string
    tags?: string[]
    active?: boolean
    page?: number
    limit?: number
  }): Promise<{
    data: Customer[]
    total: number
    page: number
    limit: number
  }> {
    return this.client.get('customers', { searchParams: params })
  }

  async get(id: string): Promise<Customer> {
    return this.client.get(`customers/${id}`)
  }

  async getByPhone(phone: string): Promise<Customer | null> {
    const response = await this.client.get<{ data: Customer[] }>('customers', {
      searchParams: { phone },
    })
    return response.data[0] || null
  }

  async create(data: CreateCustomerInput): Promise<Customer> {
    return this.client.post('customers', data)
  }

  async update(id: string, data: UpdateCustomerInput): Promise<Customer> {
    return this.client.patch(`customers/${id}`, data)
  }

  async delete(id: string): Promise<void> {
    await this.client.delete(`customers/${id}`)
  }

  async merge(primaryId: string, secondaryId: string): Promise<Customer> {
    return this.client.post(`customers/${primaryId}/merge`, { secondaryId })
  }

  async getAppointments(id: string, params?: {
    status?: string
    from?: string
    to?: string
    page?: number
    limit?: number
  }): Promise<{
    data: any[] // Use Appointment type from appointments resource
    total: number
    page: number
    limit: number
  }> {
    return this.client.get(`customers/${id}/appointments`, { searchParams: params })
  }

  async getCalls(id: string, params?: {
    from?: string
    to?: string
    page?: number
    limit?: number
  }): Promise<{
    data: any[] // Use Call type from calls resource
    total: number
    page: number
    limit: number
  }> {
    return this.client.get(`customers/${id}/calls`, { searchParams: params })
  }

  async addTag(id: string, tag: string): Promise<Customer> {
    return this.client.post(`customers/${id}/tags`, { tag })
  }

  async removeTag(id: string, tag: string): Promise<Customer> {
    return this.client.delete(`customers/${id}/tags/${tag}`)
  }

  async block(id: string, reason?: string): Promise<Customer> {
    return this.client.post(`customers/${id}/block`, { reason })
  }

  async unblock(id: string): Promise<Customer> {
    return this.client.post(`customers/${id}/unblock`)
  }
}