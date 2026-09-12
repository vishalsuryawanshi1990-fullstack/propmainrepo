import { cookies } from 'next/headers'
import { NextResponse } from 'next/server'
import { ApiError, apiFetchEnvelope } from '@/lib/api'
import { SESSION_COOKIE } from '@/lib/session'

export async function POST(request: Request) {
  const token = (await cookies()).get(SESSION_COOKIE)?.value

  if (!token) {
    return NextResponse.json({ success: false, message: 'Sign in required.' }, { status: 401 })
  }

  const body = await request.json()

  try {
    const envelope = await apiFetchEnvelope('/unlocks/spend', { method: 'POST', body: JSON.stringify(body), token })
    return NextResponse.json(envelope)
  } catch (error) {
    if (error instanceof ApiError) {
      return NextResponse.json({ success: false, message: error.message }, { status: error.status })
    }
    return NextResponse.json({ success: false, message: 'Something went wrong.' }, { status: 500 })
  }
}
