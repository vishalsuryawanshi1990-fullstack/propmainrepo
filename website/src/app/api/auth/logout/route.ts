import { cookies } from 'next/headers'
import { NextResponse } from 'next/server'
import { apiFetchEnvelope } from '@/lib/api'
import { SESSION_COOKIE } from '@/lib/session'

export async function POST() {
  const store = await cookies()
  const token = store.get(SESSION_COOKIE)?.value

  if (token) {
    await apiFetchEnvelope('/auth/logout', { method: 'POST', token }).catch(() => null)
  }

  store.delete(SESSION_COOKIE)

  return NextResponse.json({ success: true, message: 'Logged out.' })
}
