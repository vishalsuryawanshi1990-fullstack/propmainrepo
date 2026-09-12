import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export interface AdminUser {
  id: number
  name: string
  email: string | null
  phone: string
  roles: string[]
}

interface AuthState {
  token: string | null
  user: AdminUser | null
  setSession: (token: string, user: AdminUser) => void
  logout: () => void
  isAdmin: () => boolean
}

/**
 * Persisted to localStorage — acceptable here since this is a Bearer
 * token for an internal admin tool, not a public-facing app; the
 * backend still enforces a short TTL for admin/moderator tokens
 * (App\Support\TokenTtl).
 */
export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      token: null,
      user: null,
      setSession: (token, user) => set({ token, user }),
      logout: () => set({ token: null, user: null }),
      isAdmin: () => get().user?.roles.includes('admin') ?? false,
    }),
    { name: 'estateconnect-admin-auth' },
  ),
)
