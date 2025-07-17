import ky, { type Options as KyOptions } from 'ky'

export interface ApiClientConfig {
  baseUrl: string
  timeout?: number
  retry?: number
  hooks?: {
    beforeRequest?: (request: Request) => void | Promise<void>
    afterResponse?: (request: Request, response: Response) => void | Promise<void>
    beforeError?: (error: Error) => Error | Promise<Error>
  }
}

export class ApiClient {
  private ky: typeof ky

  constructor(private config: ApiClientConfig) {
    this.ky = ky.create({
      prefixUrl: config.baseUrl,
      timeout: config.timeout ?? 30000,
      retry: config.retry ?? 2,
      credentials: 'include',
      hooks: {
        beforeRequest: config.hooks?.beforeRequest ? [config.hooks.beforeRequest] : [],
        afterResponse: config.hooks?.afterResponse ? [config.hooks.afterResponse] : [],
        beforeError: config.hooks?.beforeError ? [config.hooks.beforeError] : [],
      },
    })
  }

  async get<T>(url: string, options?: KyOptions): Promise<T> {
    return this.ky.get(url, options).json<T>()
  }

  async post<T>(url: string, data?: unknown, options?: KyOptions): Promise<T> {
    return this.ky.post(url, { json: data, ...options }).json<T>()
  }

  async put<T>(url: string, data?: unknown, options?: KyOptions): Promise<T> {
    return this.ky.put(url, { json: data, ...options }).json<T>()
  }

  async patch<T>(url: string, data?: unknown, options?: KyOptions): Promise<T> {
    return this.ky.patch(url, { json: data, ...options }).json<T>()
  }

  async delete<T>(url: string, options?: KyOptions): Promise<T> {
    return this.ky.delete(url, options).json<T>()
  }

  extend(options: KyOptions): ApiClient {
    return new ApiClient({
      ...this.config,
      ...options,
    })
  }
}

export function createApiClient(config: ApiClientConfig): ApiClient {
  return new ApiClient(config)
}