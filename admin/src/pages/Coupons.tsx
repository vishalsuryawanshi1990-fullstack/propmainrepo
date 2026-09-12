import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { api, type ApiEnvelope } from '../lib/api'

interface Coupon {
  id: number
  code: string
  type: 'fixed_credits' | 'percentage_discount'
  value: number
  price: number
  max_redemptions: number | null
  redemptions_count: number
  is_active: boolean
}

interface CouponForm {
  code: string
  type: 'fixed_credits' | 'percentage_discount'
  value: string
  price: string
  max_redemptions: string
}

const emptyForm: CouponForm = { code: '', type: 'fixed_credits', value: '', price: '', max_redemptions: '' }

export default function Coupons() {
  const queryClient = useQueryClient()
  const [form, setForm] = useState<CouponForm>(emptyForm)

  const { data: coupons, isLoading } = useQuery({
    queryKey: ['admin', 'coupons'],
    queryFn: async () => (await api.get<ApiEnvelope<Coupon[]>>('/admin/coupons')).data.data,
  })

  const create = useMutation({
    mutationFn: () =>
      api.post('/admin/coupons', {
        code: form.code,
        type: form.type,
        value: Number(form.value),
        price: Number(form.price),
        max_redemptions: form.max_redemptions ? Number(form.max_redemptions) : null,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'coupons'] })
      setForm(emptyForm)
    },
  })

  const toggleActive = useMutation({
    mutationFn: ({ id, is_active }: { id: number; is_active: boolean }) =>
      api.patch(`/admin/coupons/${id}`, { is_active }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin', 'coupons'] }),
  })

  const remove = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/coupons/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin', 'coupons'] }),
  })

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">Coupon Bundles</h1>

      <form
        onSubmit={(e) => {
          e.preventDefault()
          create.mutate()
        }}
        className="grid grid-cols-2 gap-3 rounded-xl border border-neutral-200 bg-white p-4 md:grid-cols-5 dark:border-neutral-800 dark:bg-neutral-900"
      >
        <input
          required
          placeholder="Code (e.g. BULK10)"
          value={form.code}
          onChange={(e) => setForm({ ...form, code: e.target.value.toUpperCase() })}
          className="rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800"
        />
        <select
          value={form.type}
          onChange={(e) => setForm({ ...form, type: e.target.value as CouponForm['type'] })}
          className="rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800"
        >
          <option value="fixed_credits">Fixed credits</option>
          <option value="percentage_discount">Percentage discount</option>
        </select>
        <input
          required
          type="number"
          placeholder="Value"
          value={form.value}
          onChange={(e) => setForm({ ...form, value: e.target.value })}
          className="rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800"
        />
        <input
          required
          type="number"
          placeholder="Price (₹)"
          value={form.price}
          onChange={(e) => setForm({ ...form, price: e.target.value })}
          className="rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800"
        />
        <button
          type="submit"
          disabled={create.isPending}
          className="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50"
        >
          Create
        </button>
      </form>

      <div className="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800">
        <table className="w-full text-sm">
          <thead className="bg-neutral-50 text-left text-neutral-500 dark:bg-neutral-900">
            <tr>
              <th className="px-4 py-2 font-medium">Code</th>
              <th className="px-4 py-2 font-medium">Type</th>
              <th className="px-4 py-2 font-medium">Value</th>
              <th className="px-4 py-2 font-medium">Price</th>
              <th className="px-4 py-2 font-medium">Redemptions</th>
              <th className="px-4 py-2 font-medium">Active</th>
              <th className="px-4 py-2" />
            </tr>
          </thead>
          <tbody className="divide-y divide-neutral-100 dark:divide-neutral-800">
            {isLoading && (
              <tr>
                <td colSpan={7} className="px-4 py-6 text-center text-neutral-500">
                  Loading…
                </td>
              </tr>
            )}
            {coupons?.map((coupon) => (
              <tr key={coupon.id} className="bg-white dark:bg-neutral-900">
                <td className="px-4 py-2 font-mono text-neutral-900 dark:text-neutral-50">{coupon.code}</td>
                <td className="px-4 py-2 text-neutral-600 dark:text-neutral-300">{coupon.type}</td>
                <td className="px-4 py-2">{coupon.value}</td>
                <td className="px-4 py-2">₹{coupon.price}</td>
                <td className="px-4 py-2">
                  {coupon.redemptions_count}
                  {coupon.max_redemptions ? ` / ${coupon.max_redemptions}` : ''}
                </td>
                <td className="px-4 py-2">
                  <button
                    onClick={() => toggleActive.mutate({ id: coupon.id, is_active: !coupon.is_active })}
                    className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                      coupon.is_active
                        ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300'
                        : 'bg-neutral-200 text-neutral-600 dark:bg-neutral-700 dark:text-neutral-300'
                    }`}
                  >
                    {coupon.is_active ? 'Active' : 'Inactive'}
                  </button>
                </td>
                <td className="px-4 py-2 text-right">
                  <button
                    onClick={() => remove.mutate(coupon.id)}
                    className="text-xs font-medium text-red-600 hover:underline"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
