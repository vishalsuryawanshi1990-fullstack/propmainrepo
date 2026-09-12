import { cookies } from 'next/headers'
import { NextResponse } from 'next/server'
import { ApiError, apiFetchEnvelope } from '@/lib/api'
import { SESSION_COOKIE } from '@/lib/session'

interface VerifyResponse {
  token: string
  is_new_user: boolean
  needs_registration: boolean
}

export async function POST(request: Request) {
  const body = await request.json()

  try {
    const envelope = await apiFetchEnvelope<VerifyResponse>('/auth/otp/verify', {
      method: 'POST',
      body: JSON.stringify(body),
    })

    // httpOnly: the token is never exposed to client-side JS at all —
    // every authenticated action from a Client Component goes through
    // one of these route handlers, never straight to the backend.
    ;(await cookies()).set(SESSION_COOKIE, envelope.data.token, {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      sameSite: 'lax',
      path: '/',
      maxAge: 60 * 60 * 24 * 30,
    })

    return NextResponse.json({
      success: true,
      data: { needs_registration: envelope.data.needs_registration },
      message: envelope.message,
    })
  } catch (error) {
    if (error instanceof ApiError) {
      return NextResponse.json({ success: false, message: error.message }, { status: error.status })
    }
    return NextResponse.json({ success: false, message: 'Something went wrong.' }, { status: 500 })
  }
}
