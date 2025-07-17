import { z } from 'zod'
import { ApiClient } from '../client'

export const CallSchema = z.object({
  id: z.string(),
  companyId: z.string(),
  branchId: z.string().optional(),
  retellCallId: z.string(),
  agentId: z.string(),
  customerId: z.string().optional(),
  phoneNumber: z.string(),
  fromNumber: z.string(),
  direction: z.enum(['inbound', 'outbound']),
  status: z.enum(['active', 'ended', 'failed', 'no-answer']),
  startTime: z.string().datetime(),
  endTime: z.string().datetime().optional(),
  duration: z.number().optional(),
  recordingUrl: z.string().url().optional(),
  transcript: z.string().optional(),
  summary: z.string().optional(),
  sentiment: z.enum(['positive', 'neutral', 'negative']).optional(),
  appointmentBooked: z.boolean().default(false),
  appointmentId: z.string().optional(),
  metadata: z.record(z.any()).optional(),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
})

export type Call = z.infer<typeof CallSchema>

export const CallFilterSchema = z.object({
  branchId: z.string().optional(),
  customerId: z.string().optional(),
  status: z.string().optional(),
  from: z.string().datetime().optional(),
  to: z.string().datetime().optional(),
  appointmentBooked: z.boolean().optional(),
  page: z.number().optional(),
  limit: z.number().optional(),
})

export type CallFilter = z.infer<typeof CallFilterSchema>

export class CallsResource {
  constructor(private client: ApiClient) {}

  async list(params?: CallFilter): Promise<{
    data: Call[]
    total: number
    page: number
    limit: number
  }> {
    return this.client.get('calls', { searchParams: params })
  }

  async get(id: string): Promise<Call> {
    return this.client.get(`calls/${id}`)
  }

  async getTranscript(id: string): Promise<{
    transcript: string
    messages: Array<{
      role: 'agent' | 'customer'
      content: string
      timestamp: string
    }>
  }> {
    return this.client.get(`calls/${id}/transcript`)
  }

  async getRecording(id: string): Promise<{
    url: string
    duration: number
    size: number
  }> {
    return this.client.get(`calls/${id}/recording`)
  }

  async getAnalytics(params?: {
    branchId?: string
    from?: string
    to?: string
    interval?: 'hour' | 'day' | 'week' | 'month'
  }): Promise<{
    totalCalls: number
    answeredCalls: number
    missedCalls: number
    averageDuration: number
    appointmentsBooked: number
    conversionRate: number
    peakHours: Array<{ hour: number; count: number }>
    callsByDay: Array<{ date: string; count: number }>
  }> {
    return this.client.get('calls/analytics', { searchParams: params })
  }

  async exportCalls(params: {
    format: 'csv' | 'xlsx' | 'pdf'
    filters?: CallFilter
  }): Promise<{
    url: string
    expiresAt: string
  }> {
    return this.client.post('calls/export', params)
  }
}