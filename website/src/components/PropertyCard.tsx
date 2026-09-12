import Link from 'next/link'
import type { PropertySummary } from '@/lib/types'

export default function PropertyCard({ property }: { property: PropertySummary }) {
  const primaryImage = property.images.find((i) => i.is_primary) ?? property.images[0]

  return (
    <Link
      href={`/properties/${property.id}`}
      className="group block overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm transition hover:shadow-md"
    >
      <div className="aspect-video w-full overflow-hidden bg-neutral-100">
        {primaryImage ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={primaryImage.url}
            alt={property.title}
            className="h-full w-full object-cover transition group-hover:scale-105"
          />
        ) : (
          <div className="flex h-full items-center justify-center text-neutral-400">No photo</div>
        )}
      </div>

      <div className="p-4">
        <div className="mb-1 flex items-center justify-between">
          <h3 className="truncate font-semibold text-neutral-900">{property.title}</h3>
          {property.owner?.is_verified_owner && (
            <span className="shrink-0 rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700">
              Verified
            </span>
          )}
        </div>
        <p className="text-lg font-bold text-primary-700">
          ₹{Number(property.price).toLocaleString('en-IN')}
          {property.listing_type === 'rent' && <span className="text-sm font-normal text-neutral-500">/mo</span>}
        </p>
        <p className="mt-1 truncate text-sm text-neutral-500">
          {property.locality ? `${property.locality}, ` : ''}
          {property.city}
        </p>
        <div className="mt-2 flex gap-3 text-xs text-neutral-500">
          {property.bedrooms !== null && <span>{property.bedrooms} BHK</span>}
          {property.area_sqft !== null && <span>{property.area_sqft} sqft</span>}
          {property.furnishing_status && <span className="capitalize">{property.furnishing_status}</span>}
        </div>
      </div>
    </Link>
  )
}
