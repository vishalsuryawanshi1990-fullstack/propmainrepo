'use client'

import { useEffect, useRef, useState } from 'react'

declare global {
  interface Window {
    google?: typeof google
    __initLocationPicker?: () => void
  }
}

const DEFAULT_CENTER = { lat: 20.5937, lng: 78.9629 } // geographic center of India

let scriptLoadingPromise: Promise<void> | null = null

function loadGoogleMaps(apiKey: string): Promise<void> {
  if (window.google?.maps) return Promise.resolve()
  if (scriptLoadingPromise) return scriptLoadingPromise

  scriptLoadingPromise = new Promise((resolve, reject) => {
    window.__initLocationPicker = () => resolve()
    const script = document.createElement('script')
    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&callback=__initLocationPicker`
    script.async = true
    script.onerror = () => reject(new Error('Failed to load Google Maps.'))
    document.head.appendChild(script)
  })

  return scriptLoadingPromise
}

interface LocationPickerProps {
  latitude: string
  longitude: string
  onChange: (lat: number, lng: number) => void
}

/**
 * Click-anywhere-to-drop-a-pin map, replacing manual lat/long entry —
 * doc08's drop-pin picker was mobile-only; this is the same idea for the
 * website's post-property form.
 */
const GOOGLE_MAPS_API_KEY = process.env.NEXT_PUBLIC_GOOGLE_MAPS_API_KEY

export default function LocationPicker({ latitude, longitude, onChange }: LocationPickerProps) {
  const mapDivRef = useRef<HTMLDivElement>(null)
  const mapRef = useRef<google.maps.Map | null>(null)
  const markerRef = useRef<google.maps.Marker | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [ready, setReady] = useState(false)

  useEffect(() => {
    if (!GOOGLE_MAPS_API_KEY) return

    let cancelled = false

    loadGoogleMaps(GOOGLE_MAPS_API_KEY)
      .then(() => {
        if (cancelled || !mapDivRef.current) return

        const center =
          latitude && longitude ? { lat: Number(latitude), lng: Number(longitude) } : DEFAULT_CENTER

        const map = new window.google!.maps.Map(mapDivRef.current, {
          center,
          zoom: latitude && longitude ? 16 : 5,
          streetViewControl: false,
          mapTypeControl: false,
        })

        const marker = new window.google!.maps.Marker({
          position: latitude && longitude ? center : undefined,
          map,
          draggable: true,
        })

        marker.addListener('dragend', () => {
          const pos = marker.getPosition()
          if (pos) onChange(pos.lat(), pos.lng())
        })

        map.addListener('click', (e: google.maps.MapMouseEvent) => {
          if (!e.latLng) return
          marker.setPosition(e.latLng)
          onChange(e.latLng.lat(), e.latLng.lng())
        })

        mapRef.current = map
        markerRef.current = marker
        setReady(true)
      })
      .catch(() => setError('Failed to load the map. You can still search by address above.'))

    return () => {
      cancelled = true
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps -- only initialize the map once
  }, [])

  if (!GOOGLE_MAPS_API_KEY) {
    return (
      <p className="rounded-lg border border-dashed border-neutral-300 p-4 text-sm text-neutral-500">
        Map unavailable (missing Google Maps API key).
      </p>
    )
  }

  if (error) {
    return <p className="rounded-lg border border-dashed border-neutral-300 p-4 text-sm text-neutral-500">{error}</p>
  }

  return (
    <div>
      <div ref={mapDivRef} className="h-64 w-full rounded-lg border border-neutral-300" />
      <p className="mt-1 text-xs text-neutral-400">
        {ready
          ? latitude && longitude
            ? 'Pin placed — drag it or click elsewhere on the map to adjust.'
            : 'Click on the map to drop a pin at your property\'s location.'
          : 'Loading map…'}
      </p>
    </div>
  )
}
