import { cookies } from 'next/headers'
import { NextResponse } from 'next/server'
import { ApiError, apiFetchEnvelope } from '@/lib/api'
import { SESSION_COOKIE } from '@/lib/session'

interface PresignResponse {
  upload_url: string
  method: 'PUT' | 'POST'
  path: string
}

/**
 * Collapses the backend's presign -> raw upload -> attach flow into one
 * request from the browser's point of view — the client never talks to
 * the Laravel API directly (BFF pattern, see src/lib/session.ts), so this
 * route handler does all three hops server-side and returns the final
 * attach result.
 */
export async function POST(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  const token = (await cookies()).get(SESSION_COOKIE)?.value

  if (!token) {
    return NextResponse.json({ success: false, message: 'Sign in required.' }, { status: 401 })
  }

  const formData = await request.formData()
  const file = formData.get('file')
  const isPrimary = formData.get('is_primary') === 'true'

  if (!(file instanceof File)) {
    return NextResponse.json({ success: false, message: 'No file provided.' }, { status: 422 })
  }

  const extension = file.name.split('.').pop()?.toLowerCase() ?? ''

  try {
    const presign = await apiFetchEnvelope<PresignResponse>(
      `/properties/${id}/image/presigned-url?extension=${extension}&content_type=${encodeURIComponent(file.type)}`,
      { token },
    )

    const uploadResponse = await fetch(presign.data.upload_url, {
      method: presign.data.method,
      body: file,
      headers: {
        'Content-Type': file.type,
        ...(presign.data.method === 'POST' ? { Authorization: `Bearer ${token}` } : {}),
      },
    })

    if (!uploadResponse.ok) {
      return NextResponse.json({ success: false, message: 'Upload failed.' }, { status: 502 })
    }

    const attach = await apiFetchEnvelope(`/properties/${id}/image`, {
      method: 'POST',
      body: JSON.stringify({ path: presign.data.path, is_primary: isPrimary }),
      token,
    })

    return NextResponse.json(attach, { status: 201 })
  } catch (error) {
    if (error instanceof ApiError) {
      return NextResponse.json(
        { success: false, message: error.message, errors: error.errors },
        { status: error.status },
      )
    }
    return NextResponse.json({ success: false, message: 'Something went wrong.' }, { status: 500 })
  }
}
