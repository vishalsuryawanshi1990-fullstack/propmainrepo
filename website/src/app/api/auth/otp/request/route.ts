import { NextResponse } from 'next/server'
import { ApiError, apiFetchEnvelope } from '@/lib/api'

export async function POST(request: Request) {
  const body = await request.json()

  try {
    const envelope = await apiFetchEnvelope('/auth/otp/request', {
      method: 'POST',
      body: JSON.stringify(body),
    })
    return NextResponse.json(envelope)
  } catch (error) {
    if (error instanceof ApiError) {
      return NextResponse.json({ success: false, message: error.message }, { status: error.status })
    }
    return NextResponse.json({ success: false, message: 'Something went wrong.' }, { status: 500 })
  }
}
