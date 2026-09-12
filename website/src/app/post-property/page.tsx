import { redirect } from 'next/navigation'
import { getSession } from '@/lib/session'
import PostPropertyForm from './PostPropertyForm'

export const metadata = { title: 'Post a Property' }

export default async function PostPropertyPage() {
  const session = await getSession()

  if (!session) redirect('/login')

  if (!session.user.roles.some((r) => r === 'seller' || r === 'agent')) {
    return (
      <div className="mx-auto max-w-lg px-4 py-16 text-center">
        <h1 className="mb-2 text-2xl font-bold text-neutral-900">Owner/agent account required</h1>
        <p className="text-neutral-500">
          Your account is registered as a buyer. Posting a listing needs a seller or agent account — sign up again
          with that role, or contact support to switch.
        </p>
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-2xl px-4 py-12">
      <h1 className="mb-6 text-2xl font-bold text-neutral-900">Post a Property</h1>
      <PostPropertyForm />
    </div>
  )
}
