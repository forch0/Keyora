import { Routes, Route } from 'react-router-dom'
import { Providers } from '@/components/Providers'
import { ProtectedRoute } from '@/components/ProtectedRoute'
import { SessionExpiredHandler } from '@/components/SessionExpiredHandler'
import { AppLayout } from '@/components/layout/AppLayout'
import { PlaceholderPage, NotFoundPage } from '@/components/layout/PlaceholderPage'
import { LoginPage } from '@/features/auth/pages/LoginPage'
import { RegisterPage } from '@/features/auth/pages/RegisterPage'
import { ForgotPasswordPage } from '@/features/auth/pages/ForgotPasswordPage'
import { ResetPasswordPage } from '@/features/auth/pages/ResetPasswordPage'

function App() {
  return (
    <Providers>
      <SessionExpiredHandler />
      <Routes>
        {/* Public auth routes */}
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="/forgot-password" element={<ForgotPasswordPage />} />
        <Route path="/reset-password" element={<ResetPasswordPage />} />

        {/* Protected routes — wrapped in AppLayout with sidebar + navbar */}
        <Route
          element={
            <ProtectedRoute>
              <AppLayout />
            </ProtectedRoute>
          }
        >
          <Route path="/" element={<PlaceholderPage title="Dashboard" module="Module F09" />} />
          <Route path="/vault" element={<PlaceholderPage title="Personal Vault" module="Module F04" />} />
          <Route path="/vault/items/:id" element={<PlaceholderPage title="Vault Item" module="Module F04" />} />
          <Route path="/vault/trash" element={<PlaceholderPage title="Trash" module="Module F07" />} />
          <Route path="/shared" element={<PlaceholderPage title="Shared Vault" module="Module F11" />} />
          <Route path="/files" element={<PlaceholderPage title="Secure Files" module="Module F16" />} />
          <Route path="/notes" element={<PlaceholderPage title="Secure Notes" module="Module F17" />} />
          <Route path="/access-requests" element={<PlaceholderPage title="Access Requests" module="Module F14" />} />
          <Route path="/activity" element={<PlaceholderPage title="Activity Logs" module="Module F19" />} />
          <Route path="/admin" element={<PlaceholderPage title="Admin" module="Module F21" />} />
          <Route path="/settings" element={<PlaceholderPage title="Settings" module="Module F23" />} />
        </Route>

        {/* 404 catch-all */}
        <Route path="*" element={<NotFoundPage />} />
      </Routes>
    </Providers>
  )
}

export default App
