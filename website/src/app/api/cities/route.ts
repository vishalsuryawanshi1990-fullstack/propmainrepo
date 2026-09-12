import { NextResponse } from 'next/server'
import { apiFetchEnvelope } from '@/lib/api'

export async function GET() {
  const envelope = await apiFetchEnvelope('/cities', { revalidate: 3600 })
  return NextResponse.json(envelope)
}
