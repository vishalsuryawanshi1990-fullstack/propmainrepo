import { useQuery } from '@tanstack/react-query'
import { motion } from 'framer-motion'
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { api, type ApiEnvelope } from '../lib/api'
import { useAuthStore } from '../lib/auth'
import StatCard from '../components/StatCard'

interface Funnel {
  signups: number
  used_free_unlock: number
  topped_up_via_video: number
  topped_up_via_coupon: number
  repeat_unlockers: number
}

interface StatusCount {
  status: string
  count: number
}

export default function Dashboard() {
  const isAdmin = useAuthStore((s) => s.isAdmin())

  const { data: pendingProperties } = useQuery({
    queryKey: ['admin', 'properties', 'pending', 'count'],
    queryFn: async () => {
      const res = await api.get<ApiEnvelope<unknown[]>>('/admin/properties/pending')
      return res.data.meta?.total ?? res.data.data.length
    },
  })

  const { data: pendingKyc } = useQuery({
    queryKey: ['admin', 'kyc', 'pending', 'count'],
    queryFn: async () => {
      const res = await api.get<ApiEnvelope<unknown[]>>('/admin/kyc/pending')
      return res.data.meta?.total ?? res.data.data.length
    },
  })

  const { data: funnel } = useQuery({
    queryKey: ['admin', 'analytics', 'unlock-funnel'],
    queryFn: async () => (await api.get<ApiEnvelope<Funnel>>('/admin/analytics/unlock-funnel')).data.data,
    enabled: isAdmin,
  })

  const { data: byStatus } = useQuery({
    queryKey: ['admin', 'analytics', 'listings-by-status'],
    queryFn: async () =>
      (await api.get<ApiEnvelope<StatusCount[]>>('/admin/analytics/listings-by-status')).data.data,
    enabled: isAdmin,
  })

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">Dashboard</h1>

      <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
        <StatCard label="Pending listings" value={pendingProperties ?? 0} accent={(pendingProperties ?? 0) > 0} />
        <StatCard label="Pending KYC" value={pendingKyc ?? 0} accent={(pendingKyc ?? 0) > 0} />
        {isAdmin && <StatCard label="Total signups" value={funnel?.signups ?? 0} />}
        {isAdmin && <StatCard label="Repeat unlockers" value={funnel?.repeat_unlockers ?? 0} />}
      </div>

      {isAdmin && byStatus && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          className="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900"
        >
          <h2 className="mb-4 text-sm font-medium text-neutral-500">Listings by status</h2>
          <ResponsiveContainer width="100%" height={240}>
            <BarChart data={byStatus}>
              <CartesianGrid strokeDasharray="3 3" className="opacity-30" />
              <XAxis dataKey="status" fontSize={12} />
              <YAxis fontSize={12} allowDecimals={false} />
              <Tooltip />
              <Bar dataKey="count" fill="var(--color-primary-500)" radius={[6, 6, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </motion.div>
      )}

      {isAdmin && funnel && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          className="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900"
        >
          <h2 className="mb-4 text-sm font-medium text-neutral-500">Unlock funnel</h2>
          <ResponsiveContainer width="100%" height={240}>
            <BarChart
              layout="vertical"
              data={[
                { stage: 'Signups', count: funnel.signups },
                { stage: 'Used free unlock', count: funnel.used_free_unlock },
                { stage: 'Topped up (video)', count: funnel.topped_up_via_video },
                { stage: 'Topped up (coupon)', count: funnel.topped_up_via_coupon },
                { stage: 'Repeat unlockers', count: funnel.repeat_unlockers },
              ]}
            >
              <CartesianGrid strokeDasharray="3 3" className="opacity-30" />
              <XAxis type="number" fontSize={12} allowDecimals={false} />
              <YAxis type="category" dataKey="stage" fontSize={12} width={140} />
              <Tooltip />
              <Bar dataKey="count" fill="var(--color-accent-500)" radius={[0, 6, 6, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </motion.div>
      )}
    </div>
  )
}
