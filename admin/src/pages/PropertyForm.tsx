import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import LocationPicker from '../components/LocationPicker'
import { api, type ApiEnvelope } from '../lib/api'

interface PropertyType {
  id: number
  name: string
}

interface City {
  id: number
  name: string
  state: string
}

interface Locality {
  id: number
  name: string
}

interface Amenity {
  id: number
  name: string
  category: string
}

interface FormState {
  title: string
  description: string
  property_type_id: string
  listing_type: 'sale' | 'rent'
  price: string
  price_negotiable: boolean
  area_sqft: string
  bedrooms: string
  bathrooms: string
  floor_no: string
  total_floors: string
  furnishing_status: string
  city_id: string
  locality_id: string
  address: string
  latitude: string
  longitude: string
  rera_registration_no: string
  is_draft: boolean
  amenity_ids: number[]
}

const emptyForm: FormState = {
  title: '',
  description: '',
  property_type_id: '',
  listing_type: 'sale',
  price: '',
  price_negotiable: false,
  area_sqft: '',
  bedrooms: '',
  bathrooms: '',
  floor_no: '',
  total_floors: '',
  furnishing_status: '',
  city_id: '',
  locality_id: '',
  address: '',
  latitude: '',
  longitude: '',
  rera_registration_no: '',
  is_draft: false,
  amenity_ids: [],
}

export default function PropertyForm() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [form, setForm] = useState<FormState>(emptyForm)
  const [error, setError] = useState<string | null>(null)

  const { data: propertyTypes } = useQuery({
    queryKey: ['property-types'],
    queryFn: async () => (await api.get<ApiEnvelope<PropertyType[]>>('/property-types')).data.data,
  })

  const { data: cities } = useQuery({
    queryKey: ['cities'],
    queryFn: async () => (await api.get<ApiEnvelope<City[]>>('/cities')).data.data,
  })

  const { data: amenities } = useQuery({
    queryKey: ['amenities'],
    queryFn: async () => (await api.get<ApiEnvelope<Amenity[]>>('/amenities')).data.data,
  })

  const { data: localities } = useQuery({
    queryKey: ['localities', form.city_id],
    queryFn: async () =>
      (await api.get<ApiEnvelope<Locality[]>>('/localities', { params: { city_id: form.city_id } })).data.data,
    enabled: form.city_id !== '',
  })

  const create = useMutation<{ data: { data: { id: number } } }, unknown, void>({
    mutationFn: () =>
      api.post('/properties', {
        title: form.title,
        description: form.description || null,
        property_type_id: Number(form.property_type_id),
        listing_type: form.listing_type,
        price: Number(form.price),
        price_negotiable: form.price_negotiable,
        area_sqft: form.area_sqft ? Number(form.area_sqft) : null,
        bedrooms: form.bedrooms ? Number(form.bedrooms) : null,
        bathrooms: form.bathrooms ? Number(form.bathrooms) : null,
        floor_no: form.floor_no ? Number(form.floor_no) : null,
        total_floors: form.total_floors ? Number(form.total_floors) : null,
        furnishing_status: form.furnishing_status || null,
        city_id: Number(form.city_id),
        locality_id: Number(form.locality_id),
        address: form.address,
        latitude: Number(form.latitude),
        longitude: Number(form.longitude),
        rera_registration_no: form.rera_registration_no || null,
        is_draft: form.is_draft,
        amenity_ids: form.amenity_ids,
      }),
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'properties', 'pending'] })
      navigate(`/properties/${response.data.data.id}`)
    },
    onError: (err: unknown) => {
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Failed to create property.'
      setError(message)
    },
  })

  const inputClass =
    'w-full rounded-lg border border-neutral-300 px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800'
  const labelClass = 'mb-1 block text-sm font-medium text-neutral-700 dark:text-neutral-300'

  const toggleAmenity = (id: number) => {
    setForm((f) => ({
      ...f,
      amenity_ids: f.amenity_ids.includes(id) ? f.amenity_ids.filter((a) => a !== id) : [...f.amenity_ids, id],
    }))
  }

  return (
    <div className="max-w-3xl space-y-6">
      <h1 className="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">Add Property</h1>
      <p className="text-sm text-neutral-500">
        Creates a listing on behalf of a seller/agent (e.g. added over phone support). It enters the same moderation
        queue as any other submission unless saved as a draft. You'll be taken to the listing's detail page next to
        add photos/videos.
      </p>

      {error && (
        <p className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-950 dark:text-red-300">
          {error}
        </p>
      )}

      <form
        onSubmit={(e) => {
          e.preventDefault()
          setError(null)
          create.mutate()
        }}
        className="space-y-6 rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900"
      >
        <div>
          <label className={labelClass}>Title</label>
          <input
            required
            maxLength={255}
            value={form.title}
            onChange={(e) => setForm({ ...form, title: e.target.value })}
            className={inputClass}
          />
        </div>

        <div>
          <label className={labelClass}>Description</label>
          <textarea
            rows={4}
            maxLength={10000}
            value={form.description}
            onChange={(e) => setForm({ ...form, description: e.target.value })}
            className={inputClass}
          />
        </div>

        <div className="grid grid-cols-2 gap-4 md:grid-cols-3">
          <div>
            <label className={labelClass}>Property type</label>
            <select
              required
              value={form.property_type_id}
              onChange={(e) => setForm({ ...form, property_type_id: e.target.value })}
              className={inputClass}
            >
              <option value="" disabled>
                Select…
              </option>
              {propertyTypes?.map((t) => (
                <option key={t.id} value={t.id}>
                  {t.name}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className={labelClass}>Listing type</label>
            <select
              value={form.listing_type}
              onChange={(e) => setForm({ ...form, listing_type: e.target.value as 'sale' | 'rent' })}
              className={inputClass}
            >
              <option value="sale">Sale</option>
              <option value="rent">Rent</option>
            </select>
          </div>

          <div>
            <label className={labelClass}>Price (₹)</label>
            <input
              required
              type="number"
              min={0}
              value={form.price}
              onChange={(e) => setForm({ ...form, price: e.target.value })}
              className={inputClass}
            />
          </div>
        </div>

        <label className="flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300">
          <input
            type="checkbox"
            checked={form.price_negotiable}
            onChange={(e) => setForm({ ...form, price_negotiable: e.target.checked })}
          />
          Price negotiable
        </label>

        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
          <div>
            <label className={labelClass}>Area (sqft)</label>
            <input
              type="number"
              min={0}
              value={form.area_sqft}
              onChange={(e) => setForm({ ...form, area_sqft: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Bedrooms</label>
            <input
              type="number"
              min={0}
              max={50}
              value={form.bedrooms}
              onChange={(e) => setForm({ ...form, bedrooms: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Bathrooms</label>
            <input
              type="number"
              min={0}
              max={50}
              value={form.bathrooms}
              onChange={(e) => setForm({ ...form, bathrooms: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Furnishing</label>
            <input
              placeholder="e.g. semi-furnished"
              value={form.furnishing_status}
              onChange={(e) => setForm({ ...form, furnishing_status: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Floor no.</label>
            <input
              type="number"
              min={0}
              value={form.floor_no}
              onChange={(e) => setForm({ ...form, floor_no: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Total floors</label>
            <input
              type="number"
              min={0}
              value={form.total_floors}
              onChange={(e) => setForm({ ...form, total_floors: e.target.value })}
              className={inputClass}
            />
          </div>
        </div>

        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className={labelClass}>City</label>
            <select
              required
              value={form.city_id}
              onChange={(e) => setForm({ ...form, city_id: e.target.value, locality_id: '' })}
              className={inputClass}
            >
              <option value="" disabled>
                Select…
              </option>
              {cities?.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}, {c.state}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className={labelClass}>Locality</label>
            <select
              required
              disabled={!form.city_id}
              value={form.locality_id}
              onChange={(e) => setForm({ ...form, locality_id: e.target.value })}
              className={inputClass}
            >
              <option value="" disabled>
                {form.city_id ? 'Select…' : 'Select a city first'}
              </option>
              {localities?.map((l) => (
                <option key={l.id} value={l.id}>
                  {l.name}
                </option>
              ))}
            </select>
          </div>
        </div>

        <div>
          <label className={labelClass}>Address</label>
          <input
            required
            maxLength={500}
            value={form.address}
            onChange={(e) => setForm({ ...form, address: e.target.value })}
            className={inputClass}
          />
        </div>

        <div>
          <label className={labelClass}>Location</label>
          <LocationPicker
            latitude={form.latitude}
            longitude={form.longitude}
            onChange={(lat, lng) => setForm({ ...form, latitude: String(lat), longitude: String(lng) })}
          />
        </div>

        <div>
          <label className={labelClass}>RERA registration no. (optional)</label>
          <input
            maxLength={100}
            value={form.rera_registration_no}
            onChange={(e) => setForm({ ...form, rera_registration_no: e.target.value })}
            className={inputClass}
          />
        </div>

        {amenities && amenities.length > 0 && (
          <div>
            <label className={labelClass}>Amenities</label>
            <div className="flex flex-wrap gap-2">
              {amenities.map((a) => (
                <button
                  type="button"
                  key={a.id}
                  onClick={() => toggleAmenity(a.id)}
                  className={`rounded-full border px-3 py-1 text-xs font-medium ${
                    form.amenity_ids.includes(a.id)
                      ? 'border-primary-600 bg-primary-500/10 text-primary-700 dark:text-primary-200'
                      : 'border-neutral-300 text-neutral-600 dark:border-neutral-700 dark:text-neutral-300'
                  }`}
                >
                  {a.name}
                </button>
              ))}
            </div>
          </div>
        )}

        <label className="flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300">
          <input
            type="checkbox"
            checked={form.is_draft}
            onChange={(e) => setForm({ ...form, is_draft: e.target.checked })}
          />
          Save as draft (skip moderation queue for now)
        </label>

        <div className="flex justify-end gap-2">
          <button
            type="button"
            onClick={() => navigate('/properties')}
            className="rounded-lg px-4 py-2 text-sm text-neutral-600 hover:bg-neutral-100 dark:hover:bg-neutral-800"
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={create.isPending || !form.latitude || !form.longitude}
            className="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50"
          >
            {create.isPending
              ? 'Creating…'
              : !form.latitude || !form.longitude
                ? 'Pin a location to continue'
                : 'Create property'}
          </button>
        </div>
      </form>
    </div>
  )
}
