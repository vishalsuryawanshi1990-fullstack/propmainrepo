import type { Metadata } from 'next'
import { apiFetch } from '@/lib/api'
import type { Faq } from '@/lib/types'

export const metadata: Metadata = {
  title: 'FAQ',
  description: 'Frequently asked questions about buying, selling, and unlocking contact details on EstateConnect.',
}

export default async function FaqPage() {
  const faqs = await apiFetch<Faq[]>('/faqs', { revalidate: 600 }).catch(() => [])

  return (
    <div className="mx-auto max-w-3xl px-4 py-12">
      <h1 className="mb-8 text-3xl font-bold text-neutral-900">Frequently Asked Questions</h1>

      {faqs.length === 0 ? (
        <p className="text-neutral-500">No FAQs published yet.</p>
      ) : (
        <div className="space-y-4">
          {faqs.map((faq) => (
            <details key={faq.id} className="group rounded-xl border border-neutral-200 bg-white p-4">
              <summary className="cursor-pointer font-medium text-neutral-900">{faq.question}</summary>
              <p className="mt-2 text-neutral-600">{faq.answer}</p>
            </details>
          ))}
        </div>
      )}
    </div>
  )
}
