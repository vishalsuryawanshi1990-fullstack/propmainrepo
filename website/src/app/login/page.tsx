'use client'

import { useRouter } from 'next/navigation'
import { useState } from 'react'

type Step = 'phone' | 'otp' | 'register'

export default function LoginPage() {
  const router = useRouter()
  const [step, setStep] = useState<Step>('phone')
  const [phone, setPhone] = useState('')
  const [otp, setOtp] = useState('')
  const [name, setName] = useState('')
  const [role, setRole] = useState<'buyer' | 'seller' | 'agent'>('buyer')
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  async function requestOtp(e: React.FormEvent) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    const res = await fetch('/api/auth/otp/request', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone }),
    })
    const json = await res.json()
    setLoading(false)
    if (!res.ok) {
      setError(json.message)
      return
    }
    setStep('otp')
  }

  async function verifyOtp(e: React.FormEvent) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    const res = await fetch('/api/auth/otp/verify', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone, otp }),
    })
    const json = await res.json()
    setLoading(false)
    if (!res.ok) {
      setError(json.message)
      return
    }
    if (json.data.needs_registration) {
      setStep('register')
      return
    }
    router.push('/')
    router.refresh()
  }

  async function completeRegistration(e: React.FormEvent) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    const res = await fetch('/api/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, role }),
    })
    const json = await res.json()
    setLoading(false)
    if (!res.ok) {
      setError(json.message)
      return
    }
    router.push('/')
    router.refresh()
  }

  return (
    <div className="mx-auto flex max-w-sm flex-col items-center px-4 py-16">
      <h1 className="mb-1 font-serif text-2xl font-bold text-primary-900">Sign in to EstateConnect</h1>
      <p className="mb-6 text-sm text-neutral-500">We&apos;ll text you a one-time code.</p>

      {error && <div className="mb-4 w-full rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{error}</div>}

      {step === 'phone' && (
        <form onSubmit={requestOtp} className="w-full space-y-3">
          <input
            type="tel"
            required
            autoFocus
            value={phone}
            onChange={(e) => setPhone(e.target.value)}
            placeholder="Phone number"
            className="w-full rounded-lg border border-neutral-300 px-3 py-2"
          />
          <button
            disabled={loading}
            className="w-full rounded-lg bg-primary-600 py-2 font-medium text-white hover:bg-primary-700 disabled:opacity-50"
          >
            {loading ? 'Sending…' : 'Send code'}
          </button>
        </form>
      )}

      {step === 'otp' && (
        <form onSubmit={verifyOtp} className="w-full space-y-3">
          <input
            type="text"
            inputMode="numeric"
            required
            autoFocus
            value={otp}
            onChange={(e) => setOtp(e.target.value)}
            placeholder="6-digit code"
            className="w-full rounded-lg border border-neutral-300 px-3 py-2 tracking-widest"
          />
          <button
            disabled={loading}
            className="w-full rounded-lg bg-primary-600 py-2 font-medium text-white hover:bg-primary-700 disabled:opacity-50"
          >
            {loading ? 'Verifying…' : 'Verify & sign in'}
          </button>
        </form>
      )}

      {step === 'register' && (
        <form onSubmit={completeRegistration} className="w-full space-y-3">
          <input
            type="text"
            required
            autoFocus
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Your name"
            className="w-full rounded-lg border border-neutral-300 px-3 py-2"
          />
          <select
            value={role}
            onChange={(e) => setRole(e.target.value as typeof role)}
            className="w-full rounded-lg border border-neutral-300 px-3 py-2"
          >
            <option value="buyer">I&apos;m looking for a property</option>
            <option value="seller">I&apos;m an owner listing my property</option>
            <option value="agent">I&apos;m an agent/builder</option>
          </select>
          <button
            disabled={loading}
            className="w-full rounded-lg bg-primary-600 py-2 font-medium text-white hover:bg-primary-700 disabled:opacity-50"
          >
            {loading ? 'Saving…' : 'Finish sign up'}
          </button>
        </form>
      )}
    </div>
  )
}
