import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { AnimatePresence, motion } from 'framer-motion'
import { useState } from 'react'
import { api, type ApiEnvelope } from '../lib/api'

interface KycDocumentRow {
  id: number
  user_id: number
  doc_type: string
  status: string
  download_url: string | null
}

export default function Kyc() {
  const queryClient = useQueryClient()
  const [rejectingId, setRejectingId] = useState<number | null>(null)
  const [reason, setReason] = useState('')

  const { data: documents, isLoading } = useQuery({
    queryKey: ['admin', 'kyc', 'pending'],
    queryFn: async () => (await api.get<ApiEnvelope<KycDocumentRow[]>>('/admin/kyc/pending')).data.data,
  })

  const verify = useMutation({
    mutationFn: (id: number) => api.post(`/admin/kyc/${id}/verify`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin', 'kyc', 'pending'] }),
  })

  const rejectMutation = useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) => api.post(`/admin/kyc/${id}/reject`, { reason }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'kyc', 'pending'] })
      setRejectingId(null)
      setReason('')
    },
  })

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">KYC Review</h1>

      {isLoading && <p className="text-neutral-500">Loading…</p>}
      {!isLoading && documents?.length === 0 && (
        <p className="rounded-xl border border-dashed border-neutral-300 p-8 text-center text-neutral-500 dark:border-neutral-700">
          Nothing pending review.
        </p>
      )}

      <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <AnimatePresence>
          {documents?.map((doc) => (
            <motion.div
              key={doc.id}
              layout
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              exit={{ opacity: 0, x: 200, transition: { duration: 0.25 } }}
              className="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900"
            >
              <p className="mb-1 text-xs uppercase tracking-wide text-neutral-400">{doc.doc_type}</p>
              <p className="mb-3 text-sm text-neutral-600 dark:text-neutral-300">User #{doc.user_id}</p>

              {doc.download_url ? (
                <a
                  href={doc.download_url}
                  target="_blank"
                  rel="noreferrer"
                  className="mb-3 block rounded-lg bg-neutral-100 px-3 py-2 text-center text-sm font-medium text-primary-700 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-primary-300"
                >
                  View document
                </a>
              ) : null}

              <div className="flex gap-2">
                <button
                  onClick={() => verify.mutate(doc.id)}
                  disabled={verify.isPending}
                  className="flex-1 rounded-lg bg-green-600 py-1.5 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
                >
                  Verify
                </button>
                <button
                  onClick={() => setRejectingId(doc.id)}
                  className="flex-1 rounded-lg bg-red-600 py-1.5 text-sm font-medium text-white hover:bg-red-700"
                >
                  Reject
                </button>
              </div>
            </motion.div>
          ))}
        </AnimatePresence>
      </div>

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
              onClick={(e) => e.stopPropagation()}
              className="w-full max-w-sm rounded-2xl bg-white p-6 dark:bg-neutral-900"
            >
              <h2 className="mb-3 font-semibold text-neutral-900 dark:text-neutral-50">Rejection reason</h2>
              <textarea
                autoFocus
                value={reason}
                onChange={(e) => setReason(e.target.value)}
                rows={3}
                className="w-full rounded-lg border border-neutral-300 p-2 text-sm dark:border-neutral-700 dark:bg-neutral-800"
              />
              <div className="mt-4 flex justify-end gap-2">
                <button
                  onClick={() => setRejectingId(null)}
                  className="rounded-lg px-3 py-1.5 text-sm text-neutral-600 hover:bg-neutral-100 dark:hover:bg-neutral-800"
                >
                  Cancel
                </button>
                <button
                  disabled={!reason.trim() || rejectMutation.isPending}
                  onClick={() => rejectMutation.mutate({ id: rejectingId, reason })}
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
