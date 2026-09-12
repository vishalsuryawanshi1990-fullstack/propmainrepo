import type { Metadata } from 'next'
import { notFound } from 'next/navigation'
import { ApiError, apiFetch } from '@/lib/api'
import type { PublicProfile } from '@/lib/types'

type Params = Promise<{ id: string }>

async function getProfile(id: string): Promise<PublicProfile | null> {
  try {
    return await apiFetch<PublicProfile>(`/users/${id}/public-profile`, { revalidate: 300 })
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) return null
    throw error
  }
}

export async function generateMetadata({ params }: { params: Params }): Promise<Metadata> {
  const { id } = await params
  const profile = await getProfile(id)
  if (!profile) return { title: 'Profile not found' }
  return { title: profile.business_name ?? profile.name, description: profile.bio ?? undefined }
}

export default async function AgentProfilePage({ params }: { params: Params }) {
  const { id } = await params
  const profile = await getProfile(id)

  if (!profile) notFound()

  return (
    <div className="mx-auto max-w-2xl px-4 py-12">
      <div className="flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-neutral-900">{profile.business_name ?? profile.name}</h1>
          <p className="text-sm capitalize text-neutral-500">{profile.role}</p>
        </div>
        {profile.is_verified_owner && (
          <span className="rounded-full bg-primary-50 px-3 py-1 text-sm font-medium text-primary-700">
            ✓ Verified
          </span>
        )}
      </div>

      {profile.bio && <p className="mt-4 text-neutral-600">{profile.bio}</p>}
      {profile.rera_id && <p className="mt-2 text-xs text-neutral-400">RERA: {profile.rera_id}</p>}

      <p className="mt-6 text-sm text-neutral-400">
        Member since {new Date(profile.member_since).toLocaleDateString('en-IN', { year: 'numeric', month: 'long' })}
      </p>
    </div>
  )
}
