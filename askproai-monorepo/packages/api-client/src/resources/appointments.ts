import { z } from 'zod'
import { ApiClient } from '../client'

export const AppointmentSchema = z.object({
  id: z.string(),
  companyId: z.string(),
  branchId: z.string(),
  customerId: z.string(),
  staffId: z.string().optional(),
  serviceId: z.string(),
  status: z.enum(['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show']),
  startTime: z.string().datetime(),
  endTime: z.string().datetime(),
  duration: z.number(),
  price: z.number(),
  notes: z.string().optional(),
  reminderSent: z.boolean().default(false),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
})

export type Appointment = z.infer<typeof AppointmentSchema>

export const CreateAppointmentSchema = AppointmentSchema.omit({
  id: true,
  createdAt: true,
  updatedAt: true,
})

export type CreateAppointmentInput = z.infer<typeof CreateAppointmentSchema>

export const UpdateAppointmentSchema = CreateAppointmentSchema.partial()

export type UpdateAppointmentInput = z.infer<typeof UpdateAppointmentSchema>

export class AppointmentsResource {
  constructor(private client: ApiClient) {}

  async list(params?: {
    branchId?: string
    customerId?: string
    staffId?: string
    status?: string
    from?: string
    to?: string
    page?: number
    limit?: number
  }): Promise<{
    data: Appointment[]
    total: number
    page: number
    limit: number
  }> {
    return this.client.get('appointments', { searchParams: params })
  }

  async get(id: string): Promise<Appointment> {
    return this.client.get(`appointments/${id}`)
  }

  async create(data: CreateAppointmentInput): Promise<Appointment> {
    return this.client.post('appointments', data)
  }

  async update(id: string, data: UpdateAppointmentInput): Promise<Appointment> {
    return this.client.patch(`appointments/${id}`, data)
  }

  async delete(id: string): Promise<void> {
    await this.client.delete(`appointments/${id}`)
  }

  async confirm(id: string): Promise<Appointment> {
    return this.client.post(`appointments/${id}/confirm`)
  }

  async cancel(id: string, reason?: string): Promise<Appointment> {
    return this.client.post(`appointments/${id}/cancel`, { reason })
  }

  async complete(id: string): Promise<Appointment> {
    return this.client.post(`appointments/${id}/complete`)
  }

  async markNoShow(id: string): Promise<Appointment> {
    return this.client.post(`appointments/${id}/no-show`)
  }

  async checkAvailability(params: {
    branchId: string
    serviceId: string
    staffId?: string
    date: string
    duration: number
  }): Promise<{
    available: boolean
    slots: Array<{
      startTime: string
      endTime: string
      staffId?: string
    }>
  }> {
    return this.client.post('appointments/check-availability', params)
  }
}