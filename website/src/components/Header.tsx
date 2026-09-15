import Image from 'next/image'
import Link from 'next/link'
import { getSession } from '@/lib/session'
import HeaderAuthActions from './HeaderAuthActions'

export default async function Header() {
  const session = await getSession()

  return (
    <header className="border-b border-neutral-200 bg-white">
      <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
        <Link href="/" className="flex items-center gap-2 font-serif text-xl font-bold text-primary-700">
          <Image src="/logo.png" alt="" width={32} height={32} className="rounded" priority />
          PropDealers
        </Link>

        <nav className="hidden items-center gap-6 text-sm font-medium text-neutral-600 md:flex">
          <Link href="/properties?listing_type=sale" className="hover:text-primary-700">
            Buy
          </Link>
          <Link href="/properties?listing_type=rent" className="hover:text-primary-700">
            Rent
          </Link>
          <Link href="/blog" className="hover:text-primary-700">
            Blog
          </Link>
          <Link href="/faq" className="hover:text-primary-700">
            FAQ
          </Link>
        </nav>

        <HeaderAuthActions user={session?.user ?? null} />
      </div>
    </header>
  )
}
