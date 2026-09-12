import type { Metadata } from 'next'
import { Inter, Lora } from 'next/font/google'
import Footer from '@/components/Footer'
import Header from '@/components/Header'
import './globals.css'

const inter = Inter({ variable: '--font-inter', subsets: ['latin'] })
const lora = Lora({ variable: '--font-lora', subsets: ['latin'] })

export const metadata: Metadata = {
  title: {
    default: 'EstateConnect — Verified Properties, Direct from Owners',
    template: '%s | EstateConnect',
  },
  description:
    'Search verified property listings for sale and rent. Connect directly with owners — no broker spam.',
}

export default function RootLayout({ children }: LayoutProps<'/'>) {
  return (
    <html lang="en" className={`${inter.variable} ${lora.variable} h-full`}>
      <body className="flex min-h-full flex-col">
        <Header />
        <main className="flex-1">{children}</main>
        <Footer />
      </body>
    </html>
  )
}
