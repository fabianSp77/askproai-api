import { NextRequest, NextResponse } from 'next/server'
import { verifyToken } from './jwt'

interface MiddlewareConfig {
  publicRoutes?: string[]
  authRoutes?: string[]
  apiRoutes?: string[]
  redirectTo?: string
  jwtSecret: string
  cookieName?: string
}

export function createAuthMiddleware(config: MiddlewareConfig) {
  const {
    publicRoutes = ['/login', '/register', '/reset-password'],
    authRoutes = ['/login', '/register'],
    apiRoutes = ['/api'],
    redirectTo = '/login',
    jwtSecret,
    cookieName = 'askproai-session',
  } = config

  return async function middleware(request: NextRequest) {
    const { pathname } = request.nextUrl
    
    // Skip public routes
    if (publicRoutes.some(route => pathname.startsWith(route))) {
      return NextResponse.next()
    }

    // Get session token
    const token = request.cookies.get(cookieName)?.value

    // Check if user is authenticated
    let isAuthenticated = false
    let user = null

    if (token) {
      try {
        const payload = await verifyToken(token, jwtSecret)
        isAuthenticated = true
        user = payload.user
      } catch {
        // Token is invalid or expired
      }
    }

    // Redirect authenticated users away from auth routes
    if (isAuthenticated && authRoutes.some(route => pathname.startsWith(route))) {
      return NextResponse.redirect(new URL('/', request.url))
    }

    // Protect API routes
    if (pathname.startsWith('/api')) {
      if (!isAuthenticated && !apiRoutes.some(route => pathname === route)) {
        return NextResponse.json(
          { error: 'Unauthorized' },
          { status: 401 }
        )
      }

      // Add user to request headers for API routes
      if (isAuthenticated && user) {
        const requestHeaders = new Headers(request.headers)
        requestHeaders.set('x-user-id', user.id)
        requestHeaders.set('x-user-role', user.role)
        requestHeaders.set('x-company-id', user.companyId)

        return NextResponse.next({
          request: {
            headers: requestHeaders,
          },
        })
      }
    }

    // Redirect unauthenticated users to login
    if (!isAuthenticated) {
      const url = new URL(redirectTo, request.url)
      url.searchParams.set('from', pathname)
      return NextResponse.redirect(url)
    }

    return NextResponse.next()
  }
}

export function withAuth(
  handler: (req: NextRequest, user: any) => Promise<NextResponse>,
  options?: {
    roles?: string[]
    permissions?: string[]
  }
) {
  return async (req: NextRequest) => {
    const userId = req.headers.get('x-user-id')
    const userRole = req.headers.get('x-user-role')
    
    if (!userId || !userRole) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
    }

    // Check role requirements
    if (options?.roles && !options.roles.includes(userRole)) {
      return NextResponse.json({ error: 'Forbidden' }, { status: 403 })
    }

    // TODO: Check permissions from database

    const user = {
      id: userId,
      role: userRole,
      companyId: req.headers.get('x-company-id'),
    }

    return handler(req, user)
  }
}