import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts'
import { api, type ApiEnvelope } from '../lib/api'

interface Reward {
  id: number
  reward_type: string
  value: number
  probability_weight: number
  is_active: boolean
  odds_percent: number
}

const COLORS = ['var(--color-primary-500)', 'var(--color-accent-500)', '#94a3b8', '#22c55e']

export default function ScratchRewards() {
  const queryClient = useQueryClient()
  const [editing, setEditing] = useState<Record<number, number>>({})

  const { data: rewards, isLoading } = useQuery({
    queryKey: ['admin', 'scratch-rewards'],
    queryFn: async () => (await api.get<ApiEnvelope<Reward[]>>('/admin/scratch-rewards')).data.data,
  })

  const updateWeight = useMutation({
    mutationFn: ({ id, probability_weight }: { id: number; probability_weight: number }) =>
      api.patch(`/admin/scratch-rewards/${id}`, { probability_weight }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin', 'scratch-rewards'] }),
  })

  const chartData = (rewards ?? [])
    .filter((r) => r.is_active)
    .map((r) => ({ name: r.reward_type, value: editing[r.id] ?? r.probability_weight }))

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">Scratch Card Odds</h1>
      <p className="max-w-xl text-sm text-neutral-500">
        Reward types are a closed enum with no cash option (05-security-compliance.md — rewards must stay
        non-cash/non-withdrawable). Weights are relative, not percentages — the odds column is computed live.
      </p>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div className="space-y-3">
          {isLoading && <p className="text-neutral-500">Loading…</p>}
          {rewards?.map((reward) => (
            <div
              key={reward.id}
              className="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900"
            >
              <div className="mb-2 flex items-center justify-between">
                <span className="font-medium capitalize text-neutral-900 dark:text-neutral-50">
                  {reward.reward_type.replace('_', ' ')}
                </span>
                <span className="text-sm text-neutral-500">
                  {reward.value > 0 ? `value: ${reward.value}` : ''} · {reward.odds_percent}%
                </span>
              </div>
              <input
                type="range"
                min={1}
                max={100}
                value={editing[reward.id] ?? reward.probability_weight}
                onChange={(e) => setEditing({ ...editing, [reward.id]: Number(e.target.value) })}
                onMouseUp={() =>
                  updateWeight.mutate({ id: reward.id, probability_weight: editing[reward.id] ?? reward.probability_weight })
                }
                className="w-full accent-primary-600"
              />
            </div>
          ))}
        </div>

        <div className="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
          <ResponsiveContainer width="100%" height={280}>
            <PieChart>
              <Pie data={chartData} dataKey="value" nameKey="name" innerRadius={60} outerRadius={100}>
                {chartData.map((_, i) => (
                  <Cell key={i} fill={COLORS[i % COLORS.length]} />
                ))}
              </Pie>
              <Tooltip />
            </PieChart>
          </ResponsiveContainer>
        </div>
      </div>
    </div>
  )
}
