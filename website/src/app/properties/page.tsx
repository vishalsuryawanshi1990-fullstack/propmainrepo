import type { Metadata } from 'next'
import PropertyCard from '@/components/PropertyCard'
import PropertyFilters from '@/components/PropertyFilters'
import SearchBar from '@/components/SearchBar'
import { apiFetchEnvelope } from '@/lib/api'
import type { PropertySummary } from '@/lib/types'

type SearchParams = Promise<Record<string, string | string[] | undefined>>

function toQueryString(searchParams: Record<string, string | string[] | undefined>): string {
  const params = new URLSearchParams()
  for (const [key, value] of Object.entries(searchParams)) {
    if (typeof value === 'string' && value !== '') params.set(key, value)
  }
  return params.toString()
}

export async function generateMetadata({ searchParams }: { searchParams: SearchParams }): Promise<Metadata> {
  const resolved = await searchParams
  const listingType = resolved.listing_type === 'rent' ? 'Rent' : resolved.listing_type === 'sale' ? 'Buy' : ''
  const title = listingType ? `Properties for ${listingType}` : 'Search Properties'
  return { title, description: 'Browse verified property listings — filter by price, bedrooms, and location.' }
}

export default async function PropertiesPage({ searchParams }: { searchParams: SearchParams }) {
  const resolved = await searchParams
  const query = toQueryString(resolved)

  const envelope = await apiFetchEnvelope<PropertySummary[]>(`/properties?${query}`, { revalidate: 60 }).catch(
    () => ({ success: false, data: [] as PropertySummary[], message: '', meta: undefined }),
  )

  return (
    <div className="mx-auto max-w-6xl px-4 py-8">
      <div className="mb-6 space-y-4">
        <SearchBar compact />
        <PropertyFilters />
      </div>

      <p className="mb-4 text-sm text-neutral-500">{envelope.meta?.total ?? envelope.data.length} results</p>

      {envelope.data.length === 0 ? (
        <p className="rounded-xl border border-dashed border-neutral-300 p-12 text-center text-neutral-500">
          No properties match your search yet.
        </p>
      ) : (
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {envelope.data.map((property) => (
            <PropertyCard key={property.id} property={property} />
          ))}
        </div>
      )}
    </div>
  )
}
