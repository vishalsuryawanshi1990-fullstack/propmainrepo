'use client'

import { useRouter } from 'next/navigation'
import { useEffect, useState } from 'react'
import CityAutocomplete from '@/components/CityAutocomplete'
import LocationPicker from '@/components/LocationPicker'

interface Option {
  id: number
  name: string
}

interface City {
  id: number
  name: string
  state: string | null
  country: string | null
}

const initialForm = {
  title: '',
  description: '',
  property_type_id: '',
  listing_type: 'sale' as 'sale' | 'rent',
  price: '',
  city_id: '',
  locality_id: '',
  locality_text: '',
  address: '',
  latitude: '',
  longitude: '',
  bedrooms: '',
  bathrooms: '',
  area_sqft: '',
}

export default function PostPropertyForm() {
  const router = useRouter()
  const [propertyTypes, setPropertyTypes] = useState<Option[]>([])
  const [cities, setCities] = useState<City[]>([])
  const [localities, setLocalities] = useState<Option[]>([])
  const [localitiesLoadedForCityId, setLocalitiesLoadedForCityId] = useState<string | null>(null)
  const [form, setForm] = useState(initialForm)
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [submitting, setSubmitting] = useState(false)
  const [submitError, setSubmitError] = useState<string | null>(null)
  const [createdPropertyId, setCreatedPropertyId] = useState<number | null>(null)
  const [photos, setPhotos] = useState<File[]>([])
  const [uploadingPhotos, setUploadingPhotos] = useState(false)
  const [photoError, setPhotoError] = useState<string | null>(null)

  useEffect(() => {
    fetch('/api/property-types')
      .then((r) => r.json())
      .then((json) => setPropertyTypes(json.data ?? []))
    fetch('/api/cities')
      .then((r) => r.json())
      .then((json) => setCities(json.data ?? []))
  }, [])

  useEffect(() => {
    // No city selected yet — `localities` already starts empty, so there's
    // nothing to synchronize (and nothing to setState synchronously for).
    if (!form.city_id) return

    fetch(`/api/localities?city_id=${form.city_id}`)
      .then((r) => r.json())
      .then((json) => {
        setLocalities(json.data ?? [])
        setLocalitiesLoadedForCityId(form.city_id)
      })
  }, [form.city_id])

  function set<K extends keyof typeof initialForm>(key: K, value: (typeof initialForm)[K]) {
    setForm((f) => ({ ...f, [key]: value }))
  }

  async function submit(e: React.FormEvent) {
    e.preventDefault()
    setSubmitting(true)
    setErrors({})
    setSubmitError(null)

    const res = await fetch('/api/properties', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ...form,
        property_type_id: Number(form.property_type_id),
        city_id: Number(form.city_id),
        locality_id: form.locality_id ? Number(form.locality_id) : null,
        locality_text: form.locality_id ? null : form.locality_text || null,
        price: Number(form.price),
        latitude: Number(form.latitude),
        longitude: Number(form.longitude),
        bedrooms: form.bedrooms ? Number(form.bedrooms) : undefined,
        bathrooms: form.bathrooms ? Number(form.bathrooms) : undefined,
        area_sqft: form.area_sqft ? Number(form.area_sqft) : undefined,
      }),
    })
    const json = await res.json()
    setSubmitting(false)

    if (!res.ok) {
      setSubmitError(json.message)
      setErrors(json.errors ?? {})
      return
    }

    setCreatedPropertyId(json.data.id)
  }

  async function uploadPhotos() {
    if (!createdPropertyId || photos.length === 0) return
    setUploadingPhotos(true)
    setPhotoError(null)

    try {
      for (let i = 0; i < photos.length; i++) {
        const body = new FormData()
        body.append('file', photos[i])
        body.append('is_primary', String(i === 0))

        const res = await fetch(`/api/properties/${createdPropertyId}/images`, { method: 'POST', body })
        if (!res.ok) throw new Error('Upload failed')
      }
      router.push(`/properties/${createdPropertyId}`)
    } catch {
      setPhotoError('One or more photos failed to upload — please try again.')
    } finally {
      setUploadingPhotos(false)
    }
  }

  if (createdPropertyId) {
    return (
      <div className="space-y-4">
        <div className="rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">
          Listing details saved. Add at least one photo to finish — listings without photos get far less interest
          from buyers.
        </div>

        <label className="block">
          <span className="mb-1 block text-sm font-medium text-neutral-700">Photos (at least 1 required)</span>
          <input
            required
            type="file"
            accept="image/jpeg,image/png"
            multiple
            onChange={(e) => setPhotos(e.target.files ? Array.from(e.target.files) : [])}
            className="w-full rounded-lg border border-neutral-300 px-3 py-2"
          />
        </label>

        {photos.length > 0 && (
          <div className="flex flex-wrap gap-3">
            {photos.map((file, i) => (
              <img
                key={i}
                src={URL.createObjectURL(file)}
                alt=""
                className="h-20 w-20 rounded-lg object-cover"
              />
            ))}
          </div>
        )}

        {photoError && <p className="text-sm text-red-600">{photoError}</p>}

        <button
          disabled={photos.length === 0 || uploadingPhotos}
          onClick={uploadPhotos}
          className="w-full rounded-lg bg-primary-600 py-2.5 font-semibold text-white hover:bg-primary-700 disabled:opacity-50"
        >
          {uploadingPhotos ? 'Uploading…' : 'Finish and submit for review'}
        </button>
      </div>
    )
  }

  return (
    <form onSubmit={submit} className="space-y-4">
      {submitError && <div className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{submitError}</div>}

      <Field label="Title" error={errors.title}>
        <input
          required
          value={form.title}
          onChange={(e) => set('title', e.target.value)}
          className="w-full rounded-lg border border-neutral-300 px-3 py-2"
        />
      </Field>

      <Field label="Description" error={errors.description}>
        <textarea
          rows={4}
          value={form.description}
          onChange={(e) => set('description', e.target.value)}
          className="w-full rounded-lg border border-neutral-300 px-3 py-2"
        />
      </Field>

      <div className="grid grid-cols-2 gap-4">
        <Field label="Property type" error={errors.property_type_id}>
          <select
            required
            value={form.property_type_id}
            onChange={(e) => set('property_type_id', e.target.value)}
            className="w-full rounded-lg border border-neutral-300 px-3 py-2"
          >
            <option value="">Select…</option>
            {propertyTypes.map((t) => (
              <option key={t.id} value={t.id}>
                {t.name}
              </option>
            ))}
          </select>
        </Field>

        <Field label="Listing type" error={errors.listing_type}>
          <select
            value={form.listing_type}
            onChange={(e) => set('listing_type', e.target.value as 'sale' | 'rent')}
            className="w-full rounded-lg border border-neutral-300 px-3 py-2"
          >
            <option value="sale">For sale</option>
            <option value="rent">For rent</option>
          </select>
        </Field>
      </div>

      <div className="grid grid-cols-2 gap-4">
        <Field label="City" error={errors.city_id}>
          <CityAutocomplete
            cities={cities}
            value={form.city_id}
            onChange={(cityId) => {
              set('city_id', cityId)
              set('locality_id', '')
              set('locality_text', '')
            }}
          />
        </Field>

        <Field label="Locality / neighborhood" error={errors.locality_id ?? errors.locality_text}>
          {localitiesLoadedForCityId === form.city_id && localities.length === 0 ? (
            <input
              required
              placeholder="e.g. Downtown, Sector 12…"
              value={form.locality_text}
              onChange={(e) => set('locality_text', e.target.value)}
              className="w-full rounded-lg border border-neutral-300 px-3 py-2"
            />
          ) : (
            <select
              required
              disabled={!form.city_id}
              value={form.locality_id}
              onChange={(e) => set('locality_id', e.target.value)}
              className="w-full rounded-lg border border-neutral-300 px-3 py-2 disabled:opacity-50"
            >
              <option value="">Select…</option>
              {localities.map((l) => (
                <option key={l.id} value={l.id}>
                  {l.name}
                </option>
              ))}
            </select>
          )}
        </Field>
      </div>

      <Field label="Address" error={errors.address}>
        <input
          required
          value={form.address}
          onChange={(e) => set('address', e.target.value)}
          className="w-full rounded-lg border border-neutral-300 px-3 py-2"
        />
      </Field>

      <div>
        <span className="mb-1 block text-sm font-medium text-neutral-700">Pin the exact location</span>
        <LocationPicker
          latitude={form.latitude}
          longitude={form.longitude}
          onChange={(lat, lng) => {
            set('latitude', String(lat))
            set('longitude', String(lng))
          }}
        />
        {(errors.latitude || errors.longitude) && (
          <span className="mt-1 block text-xs text-red-600">{(errors.latitude ?? errors.longitude)?.[0]}</span>
        )}
      </div>

      <div className="grid grid-cols-3 gap-4">
        <Field label="Price (₹)" error={errors.price}>
          <input
            required
            type="number"
            value={form.price}
            onChange={(e) => set('price', e.target.value)}
            className="w-full rounded-lg border border-neutral-300 px-3 py-2"
          />
        </Field>
        <Field label="Bedrooms" error={errors.bedrooms}>
          <input
            type="number"
            value={form.bedrooms}
            onChange={(e) => set('bedrooms', e.target.value)}
            className="w-full rounded-lg border border-neutral-300 px-3 py-2"
          />
        </Field>
        <Field label="Area (sqft)" error={errors.area_sqft}>
          <input
            type="number"
            value={form.area_sqft}
            onChange={(e) => set('area_sqft', e.target.value)}
            className="w-full rounded-lg border border-neutral-300 px-3 py-2"
          />
        </Field>
      </div>

      <p className="text-sm text-neutral-500">
        Next you&apos;ll add photos, then the listing goes to moderation before appearing publicly.
      </p>

      <button
        disabled={submitting || !form.latitude || !form.longitude}
        className="w-full rounded-lg bg-primary-600 py-2.5 font-semibold text-white hover:bg-primary-700 disabled:opacity-50"
      >
        {submitting ? 'Submitting…' : !form.latitude || !form.longitude ? 'Pin a location to continue' : 'Continue to photos'}
      </button>
    </form>
  )
}

function Field({ label, error, children }: { label: string; error?: string[]; children: React.ReactNode }) {
  return (
    <label className="block">
      <span className="mb-1 block text-sm font-medium text-neutral-700">{label}</span>
      {children}
      {error && <span className="mt-1 block text-xs text-red-600">{error[0]}</span>}
    </label>
  )
}
