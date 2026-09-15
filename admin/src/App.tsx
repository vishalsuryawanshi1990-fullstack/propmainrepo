import { Route, Routes } from 'react-router-dom'
import Layout from './components/Layout'
import RequireAdmin from './components/RequireAdmin'
import RequireAuth from './components/RequireAuth'
import AuditLogs from './pages/AuditLogs'
import Cms from './pages/Cms'
import Analytics from './pages/Analytics'
import Coupons from './pages/Coupons'
import Dashboard from './pages/Dashboard'
import Kyc from './pages/Kyc'
import Login from './pages/Login'
import Properties from './pages/Properties'
import PropertyForm from './pages/PropertyForm'
import Reports from './pages/Reports'
import ScratchRewards from './pages/ScratchRewards'
import Users from './pages/Users'

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />

      <Route element={<RequireAuth />}>
        <Route element={<Layout />}>
          <Route path="/" element={<Dashboard />} />
          <Route path="/properties" element={<Properties />} />
          <Route path="/kyc" element={<Kyc />} />
          <Route path="/users" element={<Users />} />
          <Route path="/reports" element={<Reports />} />

          <Route element={<RequireAdmin />}>
            <Route path="/properties/new" element={<PropertyForm />} />
            <Route path="/coupons" element={<Coupons />} />
            <Route path="/scratch-rewards" element={<ScratchRewards />} />
            <Route path="/cms" element={<Cms />} />
            <Route path="/analytics" element={<Analytics />} />
            <Route path="/audit-logs" element={<AuditLogs />} />
          </Route>
        </Route>
      </Route>
    </Routes>
  )
}
