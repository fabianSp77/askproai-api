import { ApiClient, ApiClientConfig, createApiClient } from './client'
import { AppointmentsResource } from './resources/appointments'
import { CallsResource } from './resources/calls'
import { CustomersResource } from './resources/customers'

export * from './client'
export * from './resources/appointments'
export * from './resources/calls'
export * from './resources/customers'

export class AskProAIClient {
  private client: ApiClient
  
  public appointments: AppointmentsResource
  public calls: CallsResource
  public customers: CustomersResource

  constructor(config: ApiClientConfig) {
    this.client = createApiClient(config)
    
    this.appointments = new AppointmentsResource(this.client)
    this.calls = new CallsResource(this.client)
    this.customers = new CustomersResource(this.client)
  }
}

export function createAskProAIClient(config: ApiClientConfig): AskProAIClient {
  return new AskProAIClient(config)
}