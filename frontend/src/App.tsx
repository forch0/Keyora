import { Routes, Route } from 'react-router-dom'
import { Providers } from '@/components/Providers'
import { ProtectedRoute } from '@/components/ProtectedRoute'
import { SessionExpiredHandler } from '@/components/SessionExpiredHandler'
import { LoginPage } from '@/features/auth/pages/LoginPage'
import { RegisterPage } from '@/features/auth/pages/RegisterPage'
import { ForgotPasswordPage } from '@/features/auth/pages/ForgotPasswordPage'
import { ResetPasswordPage } from '@/features/auth/pages/ResetPasswordPage'

function PlaceholderPage({ label }: { label: string }) {
  return (
    <div className="flex min-h-screen items-center justify-center">
      <div className="text-center">
        <h1 className="text-2xl font-bold">Keyora</h1>
        <p className="mt-2 text-muted-foreground">{label}</p>
      </div>
    </div>
  )
}

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

        {/* Protected routes (placeholders for future modules) */}
        <Route
          path="/"
          element={
            <ProtectedRoute>
              <PlaceholderPage label="Dashboard — Module F09" />
            </ProtectedRoute>
          }
        />
        <Route
          path="/vault"
          element={
            <ProtectedRoute>
              <PlaceholderPage label="Personal Vault — Module F04" />
            </ProtectedRoute>
          }
        />
        <Route
          path="*"
          element={
            <ProtectedRoute>
              <PlaceholderPage label="Page not found" />
            </ProtectedRoute>
          }
        />
      </Routes>
    </Providers>
  )
}

export default App
