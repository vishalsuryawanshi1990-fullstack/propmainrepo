import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { api, type ApiEnvelope } from '../lib/api'

interface AuditLogRow {
  id: number
  actor: string | null
  action: string
  subject_type: string
  subject_id: number
  before: Record<string, unknown> | null
  after: Record<string, unknown> | null
  ip_address: string | null
  created_at: string
}

export default function AuditLogs() {
  const [expanded, setExpanded] = useState<number | null>(null)

  const { data: logs, isLoading } = useQuery({
    queryKey: ['admin', 'audit-logs'],
    queryFn: async () => (await api.get<ApiEnvelope<AuditLogRow[]>>('/admin/audit-logs')).data.data,
  })

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">Audit Logs</h1>

      {isLoading && <p className="text-neutral-500">Loading…</p>}

      <div className="divide-y divide-neutral-100 rounded-xl border border-neutral-200 dark:divide-neutral-800 dark:border-neutral-800">
        {logs?.map((log) => (
          <div key={log.id} className="bg-white p-3 dark:bg-neutral-900">
            <button
              onClick={() => setExpanded(expanded === log.id ? null : log.id)}
              className="flex w-full items-center justify-between text-left text-sm"
            >
              <span>
                <span className="font-medium text-neutral-900 dark:text-neutral-50">{log.action}</span>
                <span className="ml-2 text-neutral-500">
                  {log.subject_type} #{log.subject_id} · by {log.actor ?? 'system'}
                </span>
              </span>
              <span className="text-xs text-neutral-400">{new Date(log.created_at).toLocaleString()}</span>
            </button>

            {expanded === log.id && (
              <div className="mt-3 grid grid-cols-2 gap-3 text-xs">
                <div>
                  <p className="mb-1 font-medium text-neutral-500">Before</p>
                  <pre className="overflow-x-auto rounded-lg bg-neutral-50 p-2 dark:bg-neutral-800">
                    {JSON.stringify(log.before, null, 2) ?? 'null'}
                  </pre>
                </div>
                <div>
                  <p className="mb-1 font-medium text-neutral-500">After</p>
                  <pre className="overflow-x-auto rounded-lg bg-neutral-50 p-2 dark:bg-neutral-800">
                    {JSON.stringify(log.after, null, 2) ?? 'null'}
                  </pre>
                </div>
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  )
}
