import { cookies } from 'next/headers'
import { apiFetch } from './api'

export const SESSION_COOKIE = 'ec_session'

export interface SessionUser {
  id: number
  name: string
  phone: string
  email: string | null
  roles: string[]
  wallet_credits?: number
}

/** Read-only session accessor for Server Components. */
export async function getSession(): Promise<{ token: string; user: SessionUser } | null> {
  const token = (await cookies()).get(SESSION_COOKIE)?.value

  if (!token) return null

  try {
    const user = await apiFetch<SessionUser>('/me', { token, revalidate: 0 })
    return { token, user }
  } catch {
    return null
  }
}
