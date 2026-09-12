import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { AnimatePresence, motion } from 'framer-motion'
import { useState } from 'react'
import { api, type ApiEnvelope } from '../lib/api'

interface PropertyRow {
  id: number
  title: string
  price: number
  city: string | null
  locality: string | null
  owner: { id: number; name: string; is_verified_owner: boolean } | null
  is_flagged_duplicate?: boolean
}

export default function Properties() {
  const queryClient = useQueryClient()
  const [rejectingId, setRejectingId] = useState<number | null>(null)
  const [reason, setReason] = useState('')

  const { data: properties, isLoading } = useQuery({
    queryKey: ['admin', 'properties', 'pending'],
    queryFn: async () => (await api.get<ApiEnvelope<PropertyRow[]>>('/admin/properties/pending')).data.data,
  })

  const approve = useMutation({
    mutationFn: (id: number) => api.post(`/admin/properties/${id}/approve`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin', 'properties', 'pending'] }),
  })

  const reject = useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) =>
      api.post(`/admin/properties/${id}/reject`, { reason }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'properties', 'pending'] })
      setRejectingId(null)
      setReason('')
    },
  })

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">Listing Moderation</h1>

      {isLoading && <p className="text-neutral-500">Loading…</p>}
      {!isLoading && properties?.length === 0 && (
        <p className="rounded-xl border border-dashed border-neutral-300 p-8 text-center text-neutral-500 dark:border-neutral-700">
          No listings pending review.
        </p>
      )}

      <AnimatePresence>
        {properties?.map((property) => (
          <motion.div
            key={property.id}
            layout
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: 300, transition: { duration: 0.25 } }}
            className="flex items-center justify-between rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900"
          >
            <div>
              <p className="font-medium text-neutral-900 dark:text-neutral-50">
                {property.title}
                {property.is_flagged_duplicate && (
                  <span className="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-950 dark:text-red-300">
                    Possible duplicate
                  </span>
                )}
              </p>
              <p className="text-sm text-neutral-500">
                ₹{Number(property.price).toLocaleString()} · {property.locality}, {property.city} · Owner:{' '}
                {property.owner?.name}
              </p>
            </div>

            <div className="flex items-center gap-2">
              <button
                onClick={() => approve.mutate(property.id)}
                disabled={approve.isPending}
                className="rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
              >
                Approve
              </button>
              <button
                onClick={() => setRejectingId(property.id)}
                className="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-700"
              >
                Reject
              </button>
            </div>
          </motion.div>
        ))}
      </AnimatePresence>

      <AnimatePresence>
        {rejectingId !== null && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 flex items-center justify-center bg-black/40 p-4"
            onClick={() => setRejectingId(null)}
          >
            <motion.div
              initial={{ scale: 0.95, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.95, opacity: 0 }}
              onClick={(e) => e.stopPropagation()}
              className="w-full max-w-sm rounded-2xl bg-white p-6 dark:bg-neutral-900"
            >
              <h2 className="mb-3 font-semibold text-neutral-900 dark:text-neutral-50">Reason for rejection</h2>
              <textarea
                autoFocus
                value={reason}
                onChange={(e) => setReason(e.target.value)}
                rows={3}
                className="w-full rounded-lg border border-neutral-300 p-2 text-sm dark:border-neutral-700 dark:bg-neutral-800"
                placeholder="e.g. Photos are unclear"
              />
              <div className="mt-4 flex justify-end gap-2">
                <button
                  onClick={() => setRejectingId(null)}
                  className="rounded-lg px-3 py-1.5 text-sm text-neutral-600 hover:bg-neutral-100 dark:hover:bg-neutral-800"
                >
                  Cancel
                </button>
                <button
                  disabled={!reason.trim() || reject.isPending}
                  onClick={() => reject.mutate({ id: rejectingId, reason })}
                  className="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                >
                  Confirm reject
                </button>
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  )
}
