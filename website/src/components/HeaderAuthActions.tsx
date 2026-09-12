'use client'

import Link from 'next/link'
import { useRouter } from 'next/navigation'
import type { SessionUser } from '@/lib/session'

export default function HeaderAuthActions({ user }: { user: SessionUser | null }) {
  const router = useRouter()

  async function logout() {
    await fetch('/api/auth/logout', { method: 'POST' })
    router.refresh()
  }

  if (user) {
    return (
      <div className="flex items-center gap-3 text-sm">
        <span className="hidden text-neutral-600 sm:inline">Hi, {user.name}</span>
        <Link href="/post-property" className="rounded-lg bg-accent-500 px-3 py-1.5 font-medium text-white hover:bg-accent-600">
          Post property
        </Link>
        <button onClick={logout} className="font-medium text-neutral-500 hover:text-neutral-700">
          Sign out
        </button>
      </div>
    )
  }

  return (
    <Link
      href="/login"
      className="rounded-lg bg-primary-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-primary-700"
    >
      Sign in
    </Link>
  )
}
