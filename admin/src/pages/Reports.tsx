import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { AnimatePresence, motion } from 'framer-motion'
import { api, type ApiEnvelope } from '../lib/api'

interface ReportRow {
  id: number
  property_id: number
  property_title: string | null
  reason: string
  status: string
  created_at: string
}

export default function Reports() {
  const queryClient = useQueryClient()

  const { data: reports, isLoading } = useQuery({
    queryKey: ['admin', 'reported-listings'],
    queryFn: async () => (await api.get<ApiEnvelope<ReportRow[]>>('/admin/reported-listings')).data.data,
  })

  const resolve = useMutation({
    mutationFn: (id: number) => api.post(`/admin/reported-listings/${id}/resolve`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin', 'reported-listings'] }),
  })

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">Reported Listings</h1>

      {isLoading && <p className="text-neutral-500">Loading…</p>}
      {!isLoading && reports?.length === 0 && (
        <p className="rounded-xl border border-dashed border-neutral-300 p-8 text-center text-neutral-500 dark:border-neutral-700">
          No open reports.
        </p>
      )}

      <AnimatePresence>
        {reports?.map((report) => (
          <motion.div
            key={report.id}
            layout
            exit={{ opacity: 0, x: 200 }}
            className="flex items-center justify-between rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900"
          >
            <div>
              <p className="font-medium text-neutral-900 dark:text-neutral-50">
                {report.property_title ?? `Property #${report.property_id}`}
              </p>
              <span className="rounded-full bg-neutral-100 px-2 py-0.5 text-xs text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                {report.reason}
              </span>
            </div>
            <button
              onClick={() => resolve.mutate(report.id)}
              disabled={resolve.isPending}
              className="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50"
            >
              Mark resolved
            </button>
          </motion.div>
        ))}
      </AnimatePresence>
    </div>
  )
}
