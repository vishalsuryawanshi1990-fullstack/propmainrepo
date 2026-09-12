import { useQuery } from '@tanstack/react-query'
import { Bar, BarChart, CartesianGrid, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { api, type ApiEnvelope } from '../lib/api'

interface SignupRow {
  date: string
  count: number
}
interface Revenue {
  by_purpose: { purpose: string; total: number; count: number }[]
  video_ad_credits_granted: number
}

export default function Analytics() {
  const { data: signups } = useQuery({
    queryKey: ['admin', 'analytics', 'signups'],
    queryFn: async () => (await api.get<ApiEnvelope<SignupRow[]>>('/admin/analytics/signups')).data.data,
  })

  const { data: revenue } = useQuery({
    queryKey: ['admin', 'analytics', 'revenue'],
    queryFn: async () => (await api.get<ApiEnvelope<Revenue>>('/admin/analytics/revenue')).data.data,
  })

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">Analytics</h1>

      <div className="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
        <h2 className="mb-4 text-sm font-medium text-neutral-500">Signups (last 30 days)</h2>
        <ResponsiveContainer width="100%" height={240}>
          <LineChart data={signups}>
            <CartesianGrid strokeDasharray="3 3" className="opacity-30" />
            <XAxis dataKey="date" fontSize={11} />
            <YAxis fontSize={12} allowDecimals={false} />
            <Tooltip />
            <Line type="monotone" dataKey="count" stroke="var(--color-primary-500)" strokeWidth={2} dot={false} />
          </LineChart>
        </ResponsiveContainer>
      </div>

      <div className="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
        <h2 className="mb-4 text-sm font-medium text-neutral-500">
          Revenue by source
          <span className="ml-2 font-normal text-neutral-400">
            (video-ad credits are a count, not ₹ — no Payment row exists for them)
          </span>
        </h2>
        <ResponsiveContainer width="100%" height={240}>
          <BarChart data={revenue?.by_purpose}>
            <CartesianGrid strokeDasharray="3 3" className="opacity-30" />
            <XAxis dataKey="purpose" fontSize={12} />
            <YAxis fontSize={12} />
            <Tooltip />
            <Bar dataKey="total" fill="var(--color-accent-500)" radius={[6, 6, 0, 0]} />
          </BarChart>
        </ResponsiveContainer>
        <p className="mt-3 text-sm text-neutral-500">
          Video-ad credits granted: <strong>{revenue?.video_ad_credits_granted ?? 0}</strong>
        </p>
      </div>
    </div>
  )
}
