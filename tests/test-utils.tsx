import React, { ReactElement } from 'react'
import { render, RenderOptions } from '@testing-library/react'
import { BrowserRouter } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

// Mock Providers
const AllTheProviders = ({ children }: { children: React.ReactNode }) => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
    },
  })

  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        {children}
      </BrowserRouter>
    </QueryClientProvider>
  )
}

const customRender = (
  ui: ReactElement,
  options?: Omit<RenderOptions, 'wrapper'>,
) => render(ui, { wrapper: AllTheProviders, ...options })

// Re-export everything
export * from '@testing-library/react'
export { customRender }

// Test data factories
export const createMockUser = (overrides = {}) => ({
  id: 1,
  name: 'Test User',
  email: 'test@example.com',
  company_id: 1,
  branch_id: 1,
  role: 'admin',
  ...overrides,
})

export const createMockAppointment = (overrides = {}) => ({
  id: 1,
  customer_name: 'John Doe',
  customer_email: 'john@example.com',
  customer_phone: '+491234567890',
  appointment_datetime: '2025-01-15 10:00:00',
  service_name: 'Test Service',
  staff_name: 'Test Staff',
  status: 'scheduled',
  ...overrides,
})

export const createMockCall = (overrides = {}) => ({
  id: 1,
  call_id: 'call_123',
  from_number: '+491234567890',
  to_number: '+499876543210',
  duration: 120,
  transcript: 'Test transcript',
  status: 'completed',
  created_at: '2025-01-14 10:00:00',
  ...overrides,
})

export const createMockCompany = (overrides = {}) => ({
  id: 1,
  name: 'Test Company',
  slug: 'test-company',
  active: true,
  settings: {},
  ...overrides,
})

export const createMockBranch = (overrides = {}) => ({
  id: 1,
  company_id: 1,
  name: 'Test Branch',
  address: 'Test Street 1',
  city: 'Test City',
  postal_code: '12345',
  phone: '+491234567890',
  email: 'branch@example.com',
  ...overrides,
})

// Helper für async tests
export const waitForLoadingToFinish = () =>
  waitFor(() => {
    expect(screen.queryByTestId('loading')).not.toBeInTheDocument()
  })

// API response mocks
export const mockApiResponse = <T,>(data: T, options = {}) => ({
  data,
  status: 200,
  message: 'Success',
  ...options,
})

export const mockApiError = (message = 'Error occurred', status = 400) => ({
  data: null,
  status,
  message,
  errors: {},
})

// Local storage mock
export const mockLocalStorage = () => {
  const store: Record<string, string> = {}
  
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value
    },
    removeItem: (key: string) => {
      delete store[key]
    },
    clear: () => {
      Object.keys(store).forEach(key => delete store[key])
    },
  }
}

// Custom matchers
expect.extend({
  toBeWithinRange(received: number, floor: number, ceiling: number) {
    const pass = received >= floor && received <= ceiling
    if (pass) {
      return {
        message: () => `expected ${received} not to be within range ${floor} - ${ceiling}`,
        pass: true,
      }
    } else {
      return {
        message: () => `expected ${received} to be within range ${floor} - ${ceiling}`,
        pass: false,
      }
    }
  },
})