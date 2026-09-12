import { Navigate, Outlet } from 'react-router-dom'
import { useAuthStore } from '../lib/auth'

/** Mirrors the backend's role:admin gate on coupons/rewards/cms/analytics/audit-logs. */
export default function RequireAdmin() {
  const isAdmin = useAuthStore((s) => s.isAdmin())

  if (!isAdmin) {
    return <Navigate to="/" replace />
  }

  return <Outlet />
}
