import type { Metadata } from 'next'
import { notFound } from 'next/navigation'
import { ApiError, apiFetch } from '@/lib/api'
import type { LocalityInsights } from '@/lib/types'

type Params = Promise<{ id: string }>

async function getInsights(id: string): Promise<LocalityInsights | null> {
  try {
    return await apiFetch<LocalityInsights>(`/localities/${id}/insights`, { revalidate: 3600 })
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) return null
    throw error
  }
}

export async function generateMetadata({ params }: { params: Params }): Promise<Metadata> {
  const { id } = await params
  const insights = await getInsights(id)
  if (!insights) return { title: 'Locality not found' }
  return {
    title: `${insights.locality} — Price Trends & Insights`,
    description: `Average price per sqft, sample size, and month-over-month trend for ${insights.locality}.`,
  }
}

export default async function LocalityInsightsPage({ params }: { params: Params }) {
  const { id } = await params
  const insights = await getInsights(id)

  if (!insights) notFound()

  const trendEntries = Object.entries(insights.trend_by_month)

  return (
    <div className="mx-auto max-w-3xl px-4 py-12">
      <h1 className="text-3xl font-bold text-neutral-900">{insights.locality}</h1>
      <p className="mt-1 text-neutral-500">Based on {insights.sample_size} live listings</p>

      <div className="mt-8 rounded-2xl border border-neutral-200 bg-white p-6 text-center">
        <p className="text-sm text-neutral-500">Average price per sqft</p>
        <p className="text-3xl font-bold text-primary-700">₹{insights.avg_price_sqft.toLocaleString('en-IN')}</p>
      </div>

      {trendEntries.length > 0 && (
        <div className="mt-8">
          <h2 className="mb-3 font-semibold text-neutral-900">Price trend by month</h2>
          <div className="space-y-2">
            {trendEntries.map(([month, value]) => (
              <div key={month} className="flex items-center justify-between rounded-lg bg-white px-4 py-2 text-sm">
                <span className="text-neutral-500">{month}</span>
                <span className="font-medium text-neutral-800">₹{value.toLocaleString('en-IN')}/sqft</span>
              </div>
            ))}
          </div>
        </div>
      )}

      <p className="mt-8 text-xs text-neutral-400">
        Computed from current live listings, not a historical time series — figures update as listings change.
      </p>
    </div>
  )
}
