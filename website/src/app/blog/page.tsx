import type { Metadata } from 'next'
import Link from 'next/link'
import { apiFetch } from '@/lib/api'
import type { BlogPost } from '@/lib/types'

export const metadata: Metadata = {
  title: 'Blog',
  description: 'Guides and market insights for buying, selling, and renting property.',
}

export default async function BlogIndexPage() {
  const posts = await apiFetch<BlogPost[]>('/blogs', { revalidate: 300 }).catch(() => [])

  return (
    <div className="mx-auto max-w-4xl px-4 py-12">
      <h1 className="mb-8 text-3xl font-bold text-neutral-900">Blog</h1>

      {posts.length === 0 ? (
        <p className="text-neutral-500">No posts published yet.</p>
      ) : (
        <div className="space-y-8">
          {posts.map((post) => (
            <Link key={post.id} href={`/blog/${post.slug}`} className="block group">
              <h2 className="text-xl font-semibold text-neutral-900 group-hover:text-primary-700">{post.title}</h2>
              {post.excerpt && <p className="mt-1 text-neutral-600">{post.excerpt}</p>}
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}
