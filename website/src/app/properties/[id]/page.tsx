import type { Metadata } from 'next'
import Link from 'next/link'
import { notFound } from 'next/navigation'
import EmiCalculator from '@/components/EmiCalculator'
import UnlockContact from '@/components/UnlockContact'
import { ApiError, apiFetch } from '@/lib/api'
import { getSession } from '@/lib/session'
import type { PropertySummary } from '@/lib/types'

type Params = Promise<{ id: string }>

async function getProperty(id: string): Promise<PropertySummary | null> {
  try {
    return await apiFetch<PropertySummary>(`/properties/${id}`, { revalidate: 60 })
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) return null
    throw error
  }
}

export async function generateMetadata({ params }: { params: Params }): Promise<Metadata> {
  const { id } = await params
  const property = await getProperty(id)

  if (!property) return { title: 'Property not found' }

  return {
    title: property.title,
    description: (property.description ?? '').slice(0, 155) || `${property.title} in ${property.locality}, ${property.city}`,
    openGraph: {
      title: property.title,
      images: property.images.slice(0, 1).map((i) => i.url),
    },
  }
}

export default async function PropertyDetailPage({ params }: { params: Params }) {
  const { id } = await params
  const property = await getProperty(id)

  if (!property) notFound()

  const session = await getSession()

  return (
    <div className="mx-auto max-w-6xl px-4 py-8">
      <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <Gallery property={property} />

          <div className="mt-6 flex items-start justify-between">
            <div>
              <h1 className="text-2xl font-bold text-neutral-900">{property.title}</h1>
              <p className="text-neutral-500">
                {property.address}
                {property.locality ? (
                  <>
                    {' · '}
                    <Link href={`/localities/${property.locality_id}`} className="text-primary-700 hover:underline">
                      {property.locality} insights
                    </Link>
                  </>
                ) : null}
              </p>
            </div>
            {property.owner?.is_verified_owner && (
              <span className="shrink-0 rounded-full bg-primary-50 px-3 py-1 text-sm font-medium text-primary-700">
                ✓ Verified owner
              </span>
            )}
          </div>

          <p className="mt-2 text-2xl font-bold text-primary-700">
            ₹{Number(property.price).toLocaleString('en-IN')}
            {property.listing_type === 'rent' && <span className="text-base font-normal text-neutral-500">/mo</span>}
            {property.price_negotiable && <span className="ml-2 text-sm font-normal text-neutral-500">(negotiable)</span>}
          </p>

          <div className="mt-6 grid grid-cols-2 gap-4 rounded-xl border border-neutral-200 p-4 sm:grid-cols-4">
            <Spec label="Type" value={property.property_type} />
            <Spec label="Bedrooms" value={property.bedrooms} />
            <Spec label="Bathrooms" value={property.bathrooms} />
            <Spec label="Area" value={property.area_sqft ? `${property.area_sqft} sqft` : null} />
          </div>

          {property.description && (
            <div className="mt-6">
              <h2 className="mb-2 font-semibold text-neutral-900">Description</h2>
              <p className="whitespace-pre-line text-neutral-600">{property.description}</p>
            </div>
          )}

          {property.amenities && property.amenities.length > 0 && (
            <div className="mt-6">
              <h2 className="mb-2 font-semibold text-neutral-900">Amenities</h2>
              <div className="flex flex-wrap gap-2">
                {property.amenities.map((a) => (
                  <span key={a} className="rounded-full bg-neutral-100 px-3 py-1 text-sm text-neutral-600">
                    {a}
                  </span>
                ))}
              </div>
            </div>
          )}

          {property.rera_registration_no && (
            <p className="mt-6 text-xs text-neutral-400">RERA: {property.rera_registration_no}</p>
          )}
        </div>

        <div className="space-y-6">
          <UnlockContact propertyId={property.id} isSignedIn={!!session} />
          <EmiCalculator price={Number(property.price)} />
        </div>
      </div>
    </div>
  )
}

function Gallery({ property }: { property: PropertySummary }) {
  const primary = property.images.find((i) => i.is_primary) ?? property.images[0]

  if (!primary) {
    return <div className="flex aspect-video items-center justify-center rounded-2xl bg-neutral-100 text-neutral-400">No photos yet</div>
  }

  return (
    <div className="space-y-2">
      {/* eslint-disable-next-line @next/next/no-img-element */}
      <img src={primary.url} alt={property.title} className="aspect-video w-full rounded-2xl object-cover" />
      {property.images.length > 1 && (
        <div className="grid grid-cols-4 gap-2">
          {property.images.slice(0, 4).map((image) => (
            // eslint-disable-next-line @next/next/no-img-element
            <img key={image.id} src={image.url} alt="" className="aspect-square rounded-lg object-cover" />
          ))}
        </div>
      )}
    </div>
  )
}

function Spec({ label, value }: { label: string; value: string | number | null }) {
  return (
    <div>
      <p className="text-xs text-neutral-400">{label}</p>
      <p className="font-medium text-neutral-800">{value ?? '—'}</p>
    </div>
  )
}
