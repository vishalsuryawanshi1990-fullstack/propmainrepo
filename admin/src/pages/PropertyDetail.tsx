import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { api, type ApiEnvelope } from '../lib/api'
import { useAuthStore } from '../lib/auth'
import { attachYoutubeVideo, uploadPropertyImage, uploadPropertyVideo } from '../lib/media'

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
interface Media {
  id: number
  url: string
  source?: string
  is_primary: boolean
}
interface PropertyDetailData {
  id: number
  title: string
  description: string | null
  property_type: string | null
  listing_type: 'sale' | 'rent'
  price: number
  price_negotiable: boolean
  area_sqft: number | null
  bedrooms: number | null
  bathrooms: number | null
  floor_no: number | null
  total_floors: number | null
  furnishing_status: string | null
  city_id: number
  city: string | null
  locality_id: number
  locality: string | null
  address: string
  latitude: number
  longitude: number
  rera_registration_no: string | null
  status: string
  amenities: string[]
  images: Media[]
  videos: Media[]
  owner: { id: number; name: string } | null
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
}

export default function PropertyDetail() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const isAdmin = useAuthStore((s) => s.isAdmin())

  const [form, setForm] = useState<FormState | null>(null)
  const [youtubeUrl, setYoutubeUrl] = useState('')
  const [uploading, setUploading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [rejectReason, setRejectReason] = useState('')
  const [showReject, setShowReject] = useState(false)

  const { data: property, isLoading } = useQuery({
    queryKey: ['admin', 'property', id],
    queryFn: async () => (await api.get<ApiEnvelope<PropertyDetailData>>(`/properties/${id}`)).data.data,
  })

  useEffect(() => {
    if (!property) return
    setForm({
      title: property.title,
      description: property.description ?? '',
      property_type_id: '',
      listing_type: property.listing_type,
      price: String(property.price),
      price_negotiable: property.price_negotiable,
      area_sqft: property.area_sqft !== null ? String(property.area_sqft) : '',
      bedrooms: property.bedrooms !== null ? String(property.bedrooms) : '',
      bathrooms: property.bathrooms !== null ? String(property.bathrooms) : '',
      floor_no: property.floor_no !== null ? String(property.floor_no) : '',
      total_floors: property.total_floors !== null ? String(property.total_floors) : '',
      furnishing_status: property.furnishing_status ?? '',
      city_id: String(property.city_id),
      locality_id: String(property.locality_id),
      address: property.address,
      latitude: String(property.latitude),
      longitude: String(property.longitude),
      rera_registration_no: property.rera_registration_no ?? '',
    })
  }, [property])

  const { data: propertyTypes } = useQuery({
    queryKey: ['property-types'],
    queryFn: async () => (await api.get<ApiEnvelope<PropertyType[]>>('/property-types')).data.data,
  })
  const { data: cities } = useQuery({
    queryKey: ['cities'],
    queryFn: async () => (await api.get<ApiEnvelope<City[]>>('/cities')).data.data,
  })
  const { data: localities } = useQuery({
    queryKey: ['localities', form?.city_id],
    queryFn: async () =>
      (await api.get<ApiEnvelope<Locality[]>>('/localities', { params: { city_id: form?.city_id } })).data.data,
    enabled: !!form?.city_id,
  })
  const { data: amenities } = useQuery({
    queryKey: ['amenities'],
    queryFn: async () => (await api.get<ApiEnvelope<Amenity[]>>('/amenities')).data.data,
  })

  const refresh = () => queryClient.invalidateQueries({ queryKey: ['admin', 'property', id] })

  const save = useMutation({
    mutationFn: () => {
      if (!form) throw new Error('Form not ready')
      return api.patch(`/properties/${id}`, {
        title: form.title,
        description: form.description || null,
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
      })
    },
    onSuccess: () => {
      setError(null)
      refresh()
    },
    onError: () => setError('Failed to save changes.'),
  })

  const approve = useMutation({
    mutationFn: () => api.post(`/admin/properties/${id}/approve`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'properties', 'pending'] })
      navigate('/properties')
    },
  })

  const reject = useMutation({
    mutationFn: () => api.post(`/admin/properties/${id}/reject`, { reason: rejectReason }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'properties', 'pending'] })
      navigate('/properties')
    },
  })

  const deleteMedia = useMutation({
    mutationFn: ({ type, mediaId }: { type: 'image' | 'video'; mediaId: number }) =>
      api.delete(`/properties/${id}/${type}/${mediaId}`),
    onSuccess: refresh,
  })

  const handleImageUpload = async (files: FileList | null) => {
    if (!files || files.length === 0 || !property) return
    setUploading(true)
    setError(null)
    try {
      for (const file of Array.from(files)) {
        await uploadPropertyImage(property.id, file, property.images.length === 0)
      }
      refresh()
    } catch {
      setError('Failed to upload one or more images.')
    } finally {
      setUploading(false)
    }
  }

  const handleVideoUpload = async (files: FileList | null) => {
    if (!files || files.length === 0 || !property) return
    setUploading(true)
    setError(null)
    try {
      for (const file of Array.from(files)) {
        await uploadPropertyVideo(property.id, file, property.videos.length === 0)
      }
      refresh()
    } catch {
      setError('Failed to upload video.')
    } finally {
      setUploading(false)
    }
  }

  const handleAddYoutube = async () => {
    if (!youtubeUrl.trim() || !property) return
    setError(null)
    try {
      await attachYoutubeVideo(property.id, youtubeUrl.trim(), property.videos.length === 0)
      setYoutubeUrl('')
      refresh()
    } catch {
      setError('That doesn’t look like a valid YouTube link.')
    }
  }

  if (isLoading || !property || !form) {
    return <p className="text-neutral-500">Loading…</p>
  }

  const inputClass =
    'w-full rounded-lg border border-neutral-300 px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800'
  const labelClass = 'mb-1 block text-sm font-medium text-neutral-700 dark:text-neutral-300'

  return (
    <div className="max-w-4xl space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <button onClick={() => navigate('/properties')} className="mb-1 text-sm text-neutral-500 hover:underline">
            ← Back to Listings
          </button>
          <h1 className="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">{property.title}</h1>
          <p className="text-sm text-neutral-500">
            Status: <span className="font-medium">{property.status}</span> · Owner: {property.owner?.name ?? '—'}
          </p>
        </div>

        {property.status === 'pending_review' && (
          <div className="flex gap-2">
            <button
              onClick={() => approve.mutate()}
              disabled={approve.isPending}
              className="rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
            >
              Approve
            </button>
            <button
              onClick={() => setShowReject(true)}
              className="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-700"
            >
              Reject
            </button>
          </div>
        )}
      </div>

      {showReject && (
        <div className="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
          <label className={labelClass}>Reason for rejection</label>
          <textarea
            autoFocus
            rows={2}
            value={rejectReason}
            onChange={(e) => setRejectReason(e.target.value)}
            className={inputClass}
          />
          <div className="mt-2 flex justify-end gap-2">
            <button onClick={() => setShowReject(false)} className="px-3 py-1.5 text-sm text-neutral-600">
              Cancel
            </button>
            <button
              disabled={!rejectReason.trim() || reject.isPending}
              onClick={() => reject.mutate()}
              className="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-medium text-white disabled:opacity-50"
            >
              Confirm reject
            </button>
          </div>
        </div>
      )}

      {error && (
        <p className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-950 dark:text-red-300">
          {error}
        </p>
      )}

      {/* Media management */}
      <div className="space-y-4 rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900">
        <h2 className="font-semibold text-neutral-900 dark:text-neutral-50">Photos</h2>
        <div className="flex flex-wrap gap-3">
          {property.images.map((img) => (
            <div key={img.id} className="relative">
              <img src={img.url} alt="" className="h-24 w-24 rounded-lg object-cover" />
              {img.is_primary && (
                <span className="absolute left-1 top-1 rounded bg-primary-600 px-1.5 py-0.5 text-[10px] font-medium text-white">
                  Primary
                </span>
              )}
              {isAdmin && (
                <button
                  onClick={() => deleteMedia.mutate({ type: 'image', mediaId: img.id })}
                  className="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-xs text-white"
                >
                  ×
                </button>
              )}
            </div>
          ))}
        </div>
        {isAdmin && (
          <input
            type="file"
            accept="image/jpeg,image/png"
            multiple
            disabled={uploading}
            onChange={(e) => handleImageUpload(e.target.files)}
            className="text-sm"
          />
        )}

        <h2 className="pt-2 font-semibold text-neutral-900 dark:text-neutral-50">Videos</h2>
        <div className="space-y-2">
          {property.videos.map((v) => (
            <div key={v.id} className="flex items-center justify-between rounded-lg border border-neutral-200 p-2 text-sm dark:border-neutral-700">
              <a href={v.url} target="_blank" rel="noreferrer" className="truncate text-primary-700 hover:underline dark:text-primary-300">
                {v.source === 'youtube' ? '▶ ' : '🎬 '}
                {v.url}
              </a>
              {isAdmin && (
                <button
                  onClick={() => deleteMedia.mutate({ type: 'video', mediaId: v.id })}
                  className="ml-2 shrink-0 text-red-600 hover:underline"
                >
                  Remove
                </button>
              )}
            </div>
          ))}
        </div>
        {isAdmin && (
          <div className="space-y-2">
            <input
              type="file"
              accept="video/mp4,video/quicktime"
              disabled={uploading}
              onChange={(e) => handleVideoUpload(e.target.files)}
              className="text-sm"
            />
            <div className="flex gap-2">
              <input
                placeholder="https://www.youtube.com/watch?v=..."
                value={youtubeUrl}
                onChange={(e) => setYoutubeUrl(e.target.value)}
                className={inputClass}
              />
              <button
                onClick={handleAddYoutube}
                className="shrink-0 rounded-lg bg-neutral-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-neutral-700"
              >
                Add YouTube link
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Editable details */}
      <form
        onSubmit={(e) => {
          e.preventDefault()
          save.mutate()
        }}
        className="space-y-6 rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900"
      >
        <h2 className="font-semibold text-neutral-900 dark:text-neutral-50">Details</h2>

        <fieldset disabled={!isAdmin} className="space-y-6 disabled:opacity-60">
          <div>
            <label className={labelClass}>Title</label>
            <input
              required
              value={form.title}
              onChange={(e) => setForm({ ...form, title: e.target.value })}
              className={inputClass}
            />
          </div>

          <div>
            <label className={labelClass}>Description</label>
            <textarea
              rows={4}
              value={form.description}
              onChange={(e) => setForm({ ...form, description: e.target.value })}
              className={inputClass}
            />
          </div>

          <div className="grid grid-cols-2 gap-4 md:grid-cols-3">
            <div>
              <label className={labelClass}>Property type</label>
              <select disabled value="" onChange={() => {}} className={inputClass}>
                <option value="">{property.property_type}</option>
                {propertyTypes?.map((t) => (
                  <option key={t.id} value={t.id}>
                    {t.name}
                  </option>
                ))}
              </select>
              <p className="mt-1 text-xs text-neutral-400">Changing property type isn't supported here yet.</p>
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
                value={form.area_sqft}
                onChange={(e) => setForm({ ...form, area_sqft: e.target.value })}
                className={inputClass}
              />
            </div>
            <div>
              <label className={labelClass}>Bedrooms</label>
              <input
                type="number"
                value={form.bedrooms}
                onChange={(e) => setForm({ ...form, bedrooms: e.target.value })}
                className={inputClass}
              />
            </div>
            <div>
              <label className={labelClass}>Bathrooms</label>
              <input
                type="number"
                value={form.bathrooms}
                onChange={(e) => setForm({ ...form, bathrooms: e.target.value })}
                className={inputClass}
              />
            </div>
            <div>
              <label className={labelClass}>Furnishing</label>
              <input
                value={form.furnishing_status}
                onChange={(e) => setForm({ ...form, furnishing_status: e.target.value })}
                className={inputClass}
              />
            </div>
            <div>
              <label className={labelClass}>Floor no.</label>
              <input
                type="number"
                value={form.floor_no}
                onChange={(e) => setForm({ ...form, floor_no: e.target.value })}
                className={inputClass}
              />
            </div>
            <div>
              <label className={labelClass}>Total floors</label>
              <input
                type="number"
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
                value={form.city_id}
                onChange={(e) => setForm({ ...form, city_id: e.target.value, locality_id: '' })}
                className={inputClass}
              >
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
                value={form.locality_id}
                onChange={(e) => setForm({ ...form, locality_id: e.target.value })}
                className={inputClass}
              >
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
              value={form.address}
              onChange={(e) => setForm({ ...form, address: e.target.value })}
              className={inputClass}
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className={labelClass}>Latitude</label>
              <input
                required
                type="number"
                step="any"
                value={form.latitude}
                onChange={(e) => setForm({ ...form, latitude: e.target.value })}
                className={inputClass}
              />
            </div>
            <div>
              <label className={labelClass}>Longitude</label>
              <input
                required
                type="number"
                step="any"
                value={form.longitude}
                onChange={(e) => setForm({ ...form, longitude: e.target.value })}
                className={inputClass}
              />
            </div>
          </div>

          <div>
            <label className={labelClass}>RERA registration no.</label>
            <input
              value={form.rera_registration_no}
              onChange={(e) => setForm({ ...form, rera_registration_no: e.target.value })}
              className={inputClass}
            />
          </div>

          {amenities && amenities.length > 0 && (
            <div>
              <label className={labelClass}>Amenities (current)</label>
              <div className="flex flex-wrap gap-2">
                {property.amenities.length === 0 && <p className="text-sm text-neutral-400">None set.</p>}
                {property.amenities.map((name) => (
                  <span
                    key={name}
                    className="rounded-full border border-neutral-300 px-3 py-1 text-xs text-neutral-600 dark:border-neutral-700 dark:text-neutral-300"
                  >
                    {name}
                  </span>
                ))}
              </div>
            </div>
          )}

          <div className="flex justify-end">
            <button
              type="submit"
              disabled={save.isPending}
              className="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50"
            >
              {save.isPending ? 'Saving…' : 'Save changes'}
            </button>
          </div>
        </fieldset>
      </form>
    </div>
  )
}
