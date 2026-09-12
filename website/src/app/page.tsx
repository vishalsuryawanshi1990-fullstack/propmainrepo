import PropertyCard from '@/components/PropertyCard'
import SearchBar from '@/components/SearchBar'
import { apiFetch } from '@/lib/api'
import type { PropertySummary } from '@/lib/types'

export default async function HomePage() {
  const featured = await apiFetch<PropertySummary[]>('/properties/featured', { revalidate: 300 }).catch(() => [])

  return (
    <div>
      <section className="border-b border-neutral-200 bg-gradient-to-b from-primary-50 to-white py-16">
        <div className="mx-auto flex max-w-6xl flex-col items-center gap-6 px-4 text-center">
          <h1 className="font-serif text-4xl font-bold text-primary-900 md:text-5xl">
            Find your next home, straight from the owner
          </h1>
          <p className="max-w-xl text-neutral-600">
            Verified listings, no broker spam. Unlock contact details with a watch-a-video or buy-a-coupon flow —
            then scratch a card for a bonus reward.
          </p>
          <SearchBar />
        </div>
      </section>

      {featured.length > 0 && (
        <section className="mx-auto max-w-6xl px-4 py-12">
          <h2 className="mb-6 text-2xl font-semibold text-neutral-900">Featured listings</h2>
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {featured.map((property) => (
              <PropertyCard key={property.id} property={property} />
            ))}
          </div>
        </section>
      )}
    </div>
  )
}
