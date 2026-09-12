import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { api, type ApiEnvelope } from '../lib/api'

interface UserRow {
  id: number
  name: string
  phone: string
  email: string | null
  roles: string[]
  status: string
  created_at: string
}

const STATUS_STYLES: Record<string, string> = {
  active: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
  suspended: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
  banned: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
}

export default function Users() {
  const [search, setSearch] = useState('')
  const queryClient = useQueryClient()

  const { data: users, isLoading } = useQuery({
    queryKey: ['admin', 'users', search],
    queryFn: async () =>
      (await api.get<ApiEnvelope<UserRow[]>>('/admin/users', { params: { search: search || undefined } })).data.data,
  })

  const updateStatus = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      api.patch(`/admin/users/${id}/status`, { status }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin', 'users'] }),
  })

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">Users</h1>
        <input
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Search name, phone, email…"
          className="w-64 rounded-lg border border-neutral-300 px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800"
        />
      </div>

      <div className="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800">
        <table className="w-full text-sm">
          <thead className="bg-neutral-50 text-left text-neutral-500 dark:bg-neutral-900">
            <tr>
              <th className="px-4 py-2 font-medium">Name</th>
              <th className="px-4 py-2 font-medium">Phone</th>
              <th className="px-4 py-2 font-medium">Roles</th>
              <th className="px-4 py-2 font-medium">Status</th>
              <th className="px-4 py-2 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-neutral-100 dark:divide-neutral-800">
            {isLoading && (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-neutral-500">
                  Loading…
                </td>
              </tr>
            )}
            {users?.map((user) => (
              <tr key={user.id} className="bg-white dark:bg-neutral-900">
                <td className="px-4 py-2 font-medium text-neutral-900 dark:text-neutral-50">{user.name}</td>
                <td className="px-4 py-2 text-neutral-600 dark:text-neutral-300">{user.phone}</td>
                <td className="px-4 py-2 text-neutral-600 dark:text-neutral-300">{user.roles.join(', ')}</td>
                <td className="px-4 py-2">
                  <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_STYLES[user.status]}`}>
                    {user.status}
                  </span>
                </td>
                <td className="px-4 py-2">
                  {user.status === 'active' ? (
                    <button
                      onClick={() => updateStatus.mutate({ id: user.id, status: 'suspended' })}
                      className="rounded-lg bg-amber-500 px-2 py-1 text-xs font-medium text-white hover:bg-amber-600"
                    >
                      Suspend
                    </button>
                  ) : (
                    <button
                      onClick={() => updateStatus.mutate({ id: user.id, status: 'active' })}
                      className="rounded-lg bg-green-600 px-2 py-1 text-xs font-medium text-white hover:bg-green-700"
                    >
                      Reactivate
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
