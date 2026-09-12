import Link from 'next/link'

export default function Footer() {
  return (
    <footer className="mt-16 border-t border-neutral-200 bg-white py-10">
      <div className="mx-auto grid max-w-6xl grid-cols-2 gap-8 px-4 text-sm text-neutral-500 md:grid-cols-4">
        <div>
          <p className="mb-3 font-semibold text-neutral-800">EstateConnect</p>
          <p>Verified listings, direct from owners.</p>
        </div>
        <div>
          <p className="mb-3 font-semibold text-neutral-800">Explore</p>
          <ul className="space-y-2">
            <li>
              <Link href="/properties?listing_type=sale">Properties for sale</Link>
            </li>
            <li>
              <Link href="/properties?listing_type=rent">Properties for rent</Link>
            </li>
          </ul>
        </div>
        <div>
          <p className="mb-3 font-semibold text-neutral-800">Company</p>
          <ul className="space-y-2">
            <li>
              <Link href="/blog">Blog</Link>
            </li>
            <li>
              <Link href="/faq">FAQ</Link>
            </li>
          </ul>
        </div>
        <div>
          <p className="mb-3 font-semibold text-neutral-800">Legal</p>
          <p>
            Listings are shown as &quot;verified&quot; per our KYC process, not guaranteed genuine — always confirm
            details independently.
          </p>
        </div>
      </div>
    </footer>
  )
}
