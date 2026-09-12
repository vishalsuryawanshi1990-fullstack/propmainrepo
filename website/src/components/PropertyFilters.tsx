'use client'

import { useRouter, useSearchParams } from 'next/navigation'

export default function PropertyFilters() {
  const router = useRouter()
  const searchParams = useSearchParams()

  function update(key: string, value: string) {
    const params = new URLSearchParams(searchParams.toString())
    if (value) {
      params.set(key, value)
    } else {
      params.delete(key)
    }
    router.push(`/properties?${params.toString()}`)
  }

  return (
    <div className="flex flex-wrap gap-2">
      <select
        value={searchParams.get('listing_type') ?? ''}
        onChange={(e) => update('listing_type', e.target.value)}
        className="rounded-lg border border-neutral-300 px-3 py-1.5 text-sm"
      >
        <option value="">Buy or Rent</option>
        <option value="sale">Buy</option>
        <option value="rent">Rent</option>
      </select>
      <select
        value={searchParams.get('bedrooms') ?? ''}
        onChange={(e) => update('bedrooms', e.target.value)}
        className="rounded-lg border border-neutral-300 px-3 py-1.5 text-sm"
      >
        <option value="">Any BHK</option>
        {[1, 2, 3, 4].map((n) => (
          <option key={n} value={n}>
            {n}+ BHK
          </option>
        ))}
      </select>
      <select
        value={searchParams.get('sort') ?? ''}
        onChange={(e) => update('sort', e.target.value)}
        className="rounded-lg border border-neutral-300 px-3 py-1.5 text-sm"
      >
        <option value="">Most recent</option>
        <option value="price_asc">Price: Low to High</option>
        <option value="price_desc">Price: High to Low</option>
        <option value="featured">Featured first</option>
      </select>
    </div>
  )
}
