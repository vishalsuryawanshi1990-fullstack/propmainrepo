export interface PropertySummary {
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
  is_featured: boolean
  views_count: number
  images: { id: number; url: string; is_primary: boolean }[]
  videos: { id: number; url: string }[]
  amenities?: string[]
  owner?: { id: number; name: string; is_verified_owner: boolean }
  created_at: string
}

export interface LocalityInsights {
  locality: string
  avg_price_sqft: number
  sample_size: number
  trend_by_month: Record<string, number>
}

export interface BlogPost {
  id: number
  title: string
  slug: string
  cover_image_path: string | null
  excerpt: string | null
  body: string
  status: string
  published_at: string | null
}

export interface Faq {
  id: number
  question: string
  answer: string
  category: string | null
}

export interface PublicProfile {
  id: number
  name: string
  role: string
  business_name: string | null
  rera_id: string | null
  bio: string | null
  is_verified_owner: boolean
  member_since: string
}
