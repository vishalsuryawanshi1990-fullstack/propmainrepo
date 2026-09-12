import { NextRequest, NextResponse } from 'next/server'
import { ApiError, apiFetchEnvelope } from '@/lib/api'

export async function GET(request: NextRequest) {
  const cityId = request.nextUrl.searchParams.get('city_id')

  if (!cityId) {
    return NextResponse.json({ success: false, message: 'city_id is required.' }, { status: 422 })
  }

  try {
    const envelope = await apiFetchEnvelope(`/localities?city_id=${cityId}`, { revalidate: 3600 })
    return NextResponse.json(envelope)
  } catch (error) {
    if (error instanceof ApiError) {
      return NextResponse.json({ success: false, message: error.message }, { status: error.status })
    }
    return NextResponse.json({ success: false, message: 'Something went wrong.' }, { status: 500 })
  }
}
