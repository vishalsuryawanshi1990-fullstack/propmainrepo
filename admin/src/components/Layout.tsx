import { useState } from 'react'
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom'
import { AnimatePresence, motion } from 'framer-motion'
import { useAuthStore } from '../lib/auth'
import ThemeToggle from './ThemeToggle'

const NAV_ITEMS = [
  { to: '/', label: 'Dashboard', icon: '📊', end: true },
  { to: '/properties', label: 'Listings', icon: '🏠' },
  { to: '/properties/new', label: 'Add Property', icon: '➕', adminOnly: true },
  { to: '/kyc', label: 'KYC Review', icon: '🪪' },
  { to: '/users', label: 'Users', icon: '👥' },
  { to: '/reports', label: 'Reports', icon: '🚩' },
  { to: '/coupons', label: 'Coupons', icon: '🎟️', adminOnly: true },
  { to: '/scratch-rewards', label: 'Scratch Odds', icon: '🎰', adminOnly: true },
  { to: '/cms', label: 'CMS', icon: '📰', adminOnly: true },
  { to: '/analytics', label: 'Analytics', icon: '📈', adminOnly: true },
  { to: '/audit-logs', label: 'Audit Logs', icon: '📜', adminOnly: true },
]

export default function Layout() {
  const [collapsed, setCollapsed] = useState(false)
  const user = useAuthStore((s) => s.user)
  const isAdmin = useAuthStore((s) => s.isAdmin())
  const logout = useAuthStore((s) => s.logout)
  const navigate = useNavigate()
  const location = useLocation()

  const items = NAV_ITEMS.filter((item) => !item.adminOnly || isAdmin)

  return (
    <div className="flex min-h-screen bg-surface dark:bg-surface-dark">
      <motion.aside
        animate={{ width: collapsed ? 72 : 240 }}
        transition={{ duration: 0.2, ease: 'easeInOut' }}
        className="flex flex-col border-r border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900"
      >
        <div className="flex h-16 items-center justify-center border-b border-neutral-200 px-2 dark:border-neutral-800">
          <span className="truncate text-lg font-bold text-primary-700 dark:text-primary-200">
            {collapsed ? 'EC' : 'EstateConnect'}
          </span>
        </div>

        <nav className="flex-1 space-y-1 p-2">
          {items.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.end}
              className={({ isActive }) =>
                `flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                  isActive
                    ? 'bg-primary-500/10 text-primary-700 dark:text-primary-200'
                    : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800'
                }`
              }
            >
              <span className="text-base">{item.icon}</span>
              {!collapsed && <span className="truncate">{item.label}</span>}
            </NavLink>
          ))}
        </nav>

        <button
          onClick={() => setCollapsed((c) => !c)}
          className="border-t border-neutral-200 p-3 text-sm text-neutral-500 hover:bg-neutral-100 dark:border-neutral-800 dark:hover:bg-neutral-800"
        >
          {collapsed ? '»' : '« Collapse'}
        </button>
      </motion.aside>

      <div className="flex flex-1 flex-col">
        <header className="flex h-16 items-center justify-between border-b border-neutral-200 bg-white px-6 dark:border-neutral-800 dark:bg-neutral-900">
          <div />
          <div className="flex items-center gap-4">
            <ThemeToggle />
            <span className="text-sm text-neutral-600 dark:text-neutral-300">
              {user?.name} <span className="text-neutral-400">· {user?.roles.join(', ')}</span>
            </span>
            <button
              onClick={() => {
                logout()
                navigate('/login', { replace: true })
              }}
              className="rounded-lg px-3 py-1.5 text-sm font-medium text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800"
            >
              Sign out
            </button>
          </div>
        </header>

        <main className="flex-1 overflow-y-auto p-6">
          <AnimatePresence mode="wait">
            <motion.div
              key={location.pathname}
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -8 }}
              transition={{ duration: 0.15 }}
            >
              <Outlet />
            </motion.div>
          </AnimatePresence>
        </main>
      </div>
    </div>
  )
}
