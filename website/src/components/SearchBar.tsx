'use client'

import { useRouter } from 'next/navigation'
import { useState } from 'react'

export default function SearchBar({ compact = false }: { compact?: boolean }) {
  const router = useRouter()
  const [query, setQuery] = useState('')
  const [listingType, setListingType] = useState<'sale' | 'rent'>('sale')

  function search(e: React.FormEvent) {
    e.preventDefault()
    const params = new URLSearchParams({ listing_type: listingType })
    if (query.trim()) params.set('q', query.trim())
    router.push(`/properties?${params.toString()}`)
  }

  return (
    <form
      onSubmit={search}
      className={`flex w-full items-stretch overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm ${
        compact ? '' : 'max-w-2xl'
      }`}
    >
      <select
        value={listingType}
        onChange={(e) => setListingType(e.target.value as 'sale' | 'rent')}
        className="border-r border-neutral-200 bg-neutral-50 px-3 text-sm font-medium text-neutral-700"
      >
        <option value="sale">Buy</option>
        <option value="rent">Rent</option>
      </select>
      <input
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        placeholder="Search by locality, project, or landmark…"
        className="flex-1 px-4 py-3 text-sm focus:outline-none"
      />
      <button type="submit" className="bg-primary-600 px-6 text-sm font-medium text-white hover:bg-primary-700">
        Search
      </button>
    </form>
  )
}
