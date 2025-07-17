import { SignJWT, jwtVerify, type JWTPayload } from 'jose'
import { User } from './types'

export interface TokenPayload extends JWTPayload {
  user: User
}

export async function signToken(
  payload: TokenPayload,
  secret: string,
  expiresIn: string = '1h'
): Promise<string> {
  const secretKey = new TextEncoder().encode(secret)
  
  return await new SignJWT(payload)
    .setProtectedHeader({ alg: 'HS256' })
    .setIssuedAt()
    .setExpirationTime(expiresIn)
    .sign(secretKey)
}

export async function verifyToken(
  token: string,
  secret: string
): Promise<TokenPayload> {
  const secretKey = new TextEncoder().encode(secret)
  
  const { payload } = await jwtVerify(token, secretKey, {
    algorithms: ['HS256'],
  })
  
  return payload as TokenPayload
}

export function isTokenExpired(token: string): boolean {
  try {
    const payload = JSON.parse(
      Buffer.from(token.split('.')[1], 'base64').toString()
    )
    return Date.now() >= payload.exp * 1000
  } catch {
    return true
  }
}