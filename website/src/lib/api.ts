const API_BASE_URL = process.env.API_BASE_URL ?? 'http://localhost:8000/api/v1'

/** Every backend response follows this envelope — see 04-api-specification.md. */
export interface ApiEnvelope<T> {
  success: boolean
  data: T
  message: string
  meta?: { page: number; per_page: number; total: number }
}

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public errors?: Record<string, string[]>,
  ) {
    super(message)
  }
}

interface ApiFetchOptions extends Omit<RequestInit, 'cache'> {
  token?: string | null
  /** Next.js fetch caching — omit for the default "no cache" (fresh every request). */
  revalidate?: number | false
}

/**
 * Server-side fetch helper, returning the full envelope (so callers that
 * need pagination `meta` — search/listing pages — can read it). Never
 * used from Client Components — pages needing authenticated data call
 * this in a Server Component, or go through a Route Handler (app/api/*),
 * which is the only place a Client Component may reach the backend from.
 */
export async function apiFetchEnvelope<T>(path: string, options: ApiFetchOptions = {}): Promise<ApiEnvelope<T>> {
  const { token, revalidate, headers, ...rest } = options

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...rest,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...headers,
    },
    next: revalidate === undefined ? undefined : { revalidate },
  })

  const json = (await response.json().catch(() => null)) as (ApiEnvelope<T> & { errors?: Record<string, string[]> }) | null

  if (!response.ok || !json) {
    throw new ApiError(json?.message ?? 'Request failed', response.status, json?.errors)
  }

  return json
}

/** Convenience wrapper for the common case of only needing `data`. */
export async function apiFetch<T>(path: string, options: ApiFetchOptions = {}): Promise<T> {
  return (await apiFetchEnvelope<T>(path, options)).data
}
