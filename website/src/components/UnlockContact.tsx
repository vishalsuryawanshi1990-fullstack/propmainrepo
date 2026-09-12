'use client'

import Link from 'next/link'
import { useState } from 'react'

interface Contact {
  name: string
  phone: string
  email: string | null
}

type Status = 'idle' | 'loading' | 'need-signin' | 'need-credits' | 'unlocked' | 'error'

/**
 * Web unlock path is deliberately simpler than the app's: rewarded-video
 * (AdMob) is a mobile SDK concept that doesn't exist in a browser, so
 * here it's "spend a credit you already have, or top up in the app" —
 * not a re-implementation of the full video/coupon/scratch-card loop.
 */
export default function UnlockContact({ propertyId, isSignedIn }: { propertyId: number; isSignedIn: boolean }) {
  const [status, setStatus] = useState<Status>('idle')
  const [contact, setContact] = useState<Contact | null>(null)
  const [message, setMessage] = useState<string | null>(null)

  async function handleClick() {
    if (!isSignedIn) {
      setStatus('need-signin')
      return
    }

    setStatus('loading')

    const checkRes = await fetch('/api/unlocks/check', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ property_id: propertyId }),
    })
    const checkJson = await checkRes.json()

    if (!checkRes.ok) {
      setStatus('error')
      setMessage(checkJson.message)
      return
    }

    if (checkJson.data.already_unlocked) {
      setContact(checkJson.data.contact)
      setStatus('unlocked')
      return
    }

    if (!checkJson.data.can_unlock_directly) {
      setStatus('need-credits')
      return
    }

    const spendRes = await fetch('/api/unlocks/spend', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ property_id: propertyId }),
    })
    const spendJson = await spendRes.json()

    if (!spendRes.ok) {
      setStatus('error')
      setMessage(spendJson.message)
      return
    }

    setContact(spendJson.data.contact)
    setStatus('unlocked')
  }

  if (status === 'unlocked' && contact) {
    return (
      <div className="rounded-xl border border-accent-500/30 bg-accent-400/10 p-4">
        <p className="text-sm text-neutral-500">Contact</p>
        <p className="font-semibold text-neutral-900">{contact.name}</p>
        <p className="text-primary-700">{contact.phone}</p>
        {contact.email && <p className="text-sm text-neutral-500">{contact.email}</p>}
      </div>
    )
  }

  return (
    <div className="space-y-2">
      <button
        onClick={handleClick}
        disabled={status === 'loading'}
        className="w-full rounded-xl bg-accent-500 py-3 font-semibold text-white transition hover:bg-accent-600 disabled:opacity-50"
      >
        {status === 'loading' ? 'Checking…' : 'View Contact'}
      </button>

      {status === 'need-signin' && (
        <p className="text-sm text-neutral-500">
          <Link href="/login" className="text-primary-700 underline">
            Sign in
          </Link>{' '}
          to view the owner&apos;s contact details.
        </p>
      )}

      {status === 'need-credits' && (
        <p className="text-sm text-neutral-500">
          You&apos;re out of unlock credits. Open the EstateConnect app to watch a rewarded video or buy a coupon —
          new credits work here too.
        </p>
      )}

      {status === 'error' && <p className="text-sm text-red-600">{message}</p>}
    </div>
  )
}
