import type { Metadata } from 'next'
import { notFound } from 'next/navigation'
import { ApiError, apiFetch } from '@/lib/api'
import type { BlogPost } from '@/lib/types'

type Params = Promise<{ slug: string }>

async function getPost(slug: string): Promise<BlogPost | null> {
  try {
    return await apiFetch<BlogPost>(`/blogs/${slug}`, { revalidate: 300 })
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) return null
    throw error
  }
}

export async function generateMetadata({ params }: { params: Params }): Promise<Metadata> {
  const { slug } = await params
  const post = await getPost(slug)
  if (!post) return { title: 'Post not found' }
  return { title: post.title, description: post.excerpt ?? undefined }
}

export default async function BlogPostPage({ params }: { params: Params }) {
  const { slug } = await params
  const post = await getPost(slug)

  if (!post) notFound()

  return (
    <article className="mx-auto max-w-2xl px-4 py-12">
      <h1 className="mb-6 text-3xl font-bold text-neutral-900">{post.title}</h1>
      <div className="prose prose-neutral max-w-none whitespace-pre-line text-neutral-700">{post.body}</div>
    </article>
  )
}
