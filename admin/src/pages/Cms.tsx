import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { api, type ApiEnvelope } from '../lib/api'

type Tab = 'banners' | 'blogs' | 'faqs'

interface Banner {
  id: number
  title: string
  image_path: string
  placement: string
  is_active: boolean
}
interface Blog {
  id: number
  title: string
  slug: string
  status: string
}
interface Faq {
  id: number
  question: string
  answer: string
  is_active: boolean
}

export default function Cms() {
  const [tab, setTab] = useState<Tab>('banners')

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">CMS</h1>

      <div className="flex gap-1 border-b border-neutral-200 dark:border-neutral-800">
        {(['banners', 'blogs', 'faqs'] as const).map((t) => (
          <button
            key={t}
            onClick={() => setTab(t)}
            className={`px-4 py-2 text-sm font-medium capitalize ${
              tab === t
                ? 'border-b-2 border-primary-600 text-primary-700 dark:text-primary-300'
                : 'text-neutral-500 hover:text-neutral-700'
            }`}
          >
            {t}
          </button>
        ))}
      </div>

      {tab === 'banners' && <BannersTab />}
      {tab === 'blogs' && <BlogsTab />}
      {tab === 'faqs' && <FaqsTab />}
    </div>
  )
}

function BannersTab() {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({ title: '', image_path: '' })

  const { data } = useQuery({
    queryKey: ['admin', 'cms', 'banners'],
    queryFn: async () => (await api.get<ApiEnvelope<{ data: Banner[] }>>('/admin/cms/banners')).data.data,
  })

  const create = useMutation({
    mutationFn: () => api.post('/admin/cms/banners', form),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'cms', 'banners'] })
      setForm({ title: '', image_path: '' })
    },
  })

  const remove = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/cms/banners/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin', 'cms', 'banners'] }),
  })

  const banners = Array.isArray(data) ? data : (data?.data ?? [])

  return (
    <div className="space-y-4">
      <form
        onSubmit={(e) => {
          e.preventDefault()
          create.mutate()
        }}
        className="flex gap-2"
      >
        <input
          required
          placeholder="Title"
          value={form.title}
          onChange={(e) => setForm({ ...form, title: e.target.value })}
          className="rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800"
        />
        <input
          required
          placeholder="Image path"
          value={form.image_path}
          onChange={(e) => setForm({ ...form, image_path: e.target.value })}
          className="flex-1 rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800"
        />
        <button className="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700">
          Add
        </button>
      </form>

      <ul className="divide-y divide-neutral-100 rounded-xl border border-neutral-200 dark:divide-neutral-800 dark:border-neutral-800">
        {banners.map((b) => (
          <li key={b.id} className="flex items-center justify-between bg-white p-3 text-sm dark:bg-neutral-900">
            <span>
              {b.title} <span className="text-neutral-400">· {b.placement}</span>
            </span>
            <button onClick={() => remove.mutate(b.id)} className="text-red-600 hover:underline">
              Delete
            </button>
          </li>
        ))}
      </ul>
    </div>
  )
}

function BlogsTab() {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({ title: '', slug: '', body: '', status: 'draft' })

  const { data } = useQuery({
    queryKey: ['admin', 'cms', 'blogs'],
    queryFn: async () => (await api.get<ApiEnvelope<{ data: Blog[] }>>('/admin/cms/blogs')).data.data,
  })

  const create = useMutation({
    mutationFn: () => api.post('/admin/cms/blogs', form),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'cms', 'blogs'] })
      setForm({ title: '', slug: '', body: '', status: 'draft' })
    },
  })

  const blogs = Array.isArray(data) ? data : (data?.data ?? [])

  return (
    <div className="space-y-4">
      <form
        onSubmit={(e) => {
          e.preventDefault()
          create.mutate()
        }}
        className="space-y-2 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900"
      >
        <div className="flex gap-2">
          <input
            required
            placeholder="Title"
            value={form.title}
            onChange={(e) => setForm({ ...form, title: e.target.value })}
            className="flex-1 rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800"
          />
          <input
            required
            placeholder="slug-like-this"
            value={form.slug}
            onChange={(e) => setForm({ ...form, slug: e.target.value })}
            className="w-48 rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800"
          />
        </div>
        <textarea
          required
          placeholder="Body"
          rows={3}
          value={form.body}
          onChange={(e) => setForm({ ...form, body: e.target.value })}
          className="w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800"
        />
        <div className="flex items-center justify-between">
          <select
            value={form.status}
            onChange={(e) => setForm({ ...form, status: e.target.value })}
            className="rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800"
          >
            <option value="draft">Draft</option>
            <option value="published">Published</option>
          </select>
          <button className="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700">
            Save post
          </button>
        </div>
      </form>

      <ul className="divide-y divide-neutral-100 rounded-xl border border-neutral-200 dark:divide-neutral-800 dark:border-neutral-800">
        {blogs.map((b) => (
          <li key={b.id} className="flex items-center justify-between bg-white p-3 text-sm dark:bg-neutral-900">
            <span>{b.title}</span>
            <span className="text-xs text-neutral-500">{b.status}</span>
          </li>
        ))}
        {blogs.length === 0 && <li className="p-6 text-center text-neutral-500">No posts yet.</li>}
      </ul>
    </div>
  )
}

function FaqsTab() {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({ question: '', answer: '' })

  const { data } = useQuery({
    queryKey: ['admin', 'cms', 'faqs'],
    queryFn: async () => (await api.get<ApiEnvelope<{ data: Faq[] }>>('/admin/cms/faqs')).data.data,
  })

  const create = useMutation({
    mutationFn: () => api.post('/admin/cms/faqs', form),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'cms', 'faqs'] })
      setForm({ question: '', answer: '' })
    },
  })

  const faqs = Array.isArray(data) ? data : (data?.data ?? [])

  return (
    <div className="space-y-4">
      <form
        onSubmit={(e) => {
          e.preventDefault()
          create.mutate()
        }}
        className="space-y-2"
      >
        <input
          required
          placeholder="Question"
          value={form.question}
          onChange={(e) => setForm({ ...form, question: e.target.value })}
          className="w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800"
        />
        <textarea
          required
          placeholder="Answer"
          value={form.answer}
          onChange={(e) => setForm({ ...form, answer: e.target.value })}
          className="w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800"
        />
        <button className="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700">
          Add FAQ
        </button>
      </form>

      <ul className="space-y-2">
        {faqs.map((f) => (
          <li key={f.id} className="rounded-xl border border-neutral-200 bg-white p-3 text-sm dark:border-neutral-800 dark:bg-neutral-900">
            <p className="font-medium text-neutral-900 dark:text-neutral-50">{f.question}</p>
            <p className="text-neutral-500">{f.answer}</p>
          </li>
        ))}
      </ul>
    </div>
  )
}
