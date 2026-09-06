import { Routes, Route } from 'react-router-dom'
import { Providers } from '@/components/Providers'
import { ProtectedRoute } from '@/components/ProtectedRoute'
import { SessionExpiredHandler } from '@/components/SessionExpiredHandler'
import { ReauthDialog } from '@/features/auth/components/ReauthDialog'
import { AppLayout } from '@/components/layout/AppLayout'
import { PlaceholderPage, NotFoundPage } from '@/components/layout/PlaceholderPage'
import { LoginPage } from '@/features/auth/pages/LoginPage'
import { RegisterPage } from '@/features/auth/pages/RegisterPage'
import { ForgotPasswordPage } from '@/features/auth/pages/ForgotPasswordPage'
import { ResetPasswordPage } from '@/features/auth/pages/ResetPasswordPage'
import { VaultListPage } from '@/features/vault/pages/VaultListPage'
import { VaultItemDetailPage } from '@/features/vault/pages/VaultItemDetailPage'
import { VaultItemCreatePage } from '@/features/vault/pages/VaultItemCreatePage'
import { VaultItemEditPage } from '@/features/vault/pages/VaultItemEditPage'
import { ToolsPage } from '@/features/tools/ToolsPage'
import { PersonalDashboardPage } from '@/features/dashboard/pages/PersonalDashboardPage'
import { CompanyDashboardPage } from '@/features/dashboard/pages/CompanyDashboardPage'
import { SharedVaultListPage } from '@/features/shared-vault/pages/SharedVaultListPage'
import { SharedVaultItemDetailPage } from '@/features/shared-vault/pages/SharedVaultItemDetailPage'
import { SharedVaultItemCreatePage } from '@/features/shared-vault/pages/SharedVaultItemCreatePage'
import { SharedVaultItemEditPage } from '@/features/shared-vault/pages/SharedVaultItemEditPage'
import { TeamVaultListPage } from '@/features/shared-vault/pages/TeamVaultListPage'
import { TeamsListPage } from '@/features/teams/pages/TeamsListPage'
import { TeamMembersPage } from '@/features/teams/pages/TeamMembersPage'
import { AccessRequestsPage } from '@/features/access-requests/pages/AccessRequestsPage'
import { RequestHistoryPage } from '@/features/access-requests/pages/RequestHistoryPage'
import { PublicLinkPage } from '@/features/secure-links/pages/PublicLinkPage'
import { FileListPage } from '@/features/secure-files/pages/FileListPage'
import { FileDetailPage } from '@/features/secure-files/pages/FileDetailPage'
import { FileTrashPage } from '@/features/secure-files/pages/FileTrashPage'
import { NoteListPage } from '@/features/secure-notes/pages/NoteListPage'
import { NoteDetailPage } from '@/features/secure-notes/pages/NoteDetailPage'
import { NoteTrashPage } from '@/features/secure-notes/pages/NoteTrashPage'
import { RecentItemsPage } from '@/features/search/pages/RecentItemsPage'
import { ExpiringAccessPage } from '@/features/search/pages/ExpiringAccessPage'
import { ActivityLogsPage } from '@/features/activity-logs/pages/ActivityLogsPage'

function App() {
  return (
    <Providers>
      <SessionExpiredHandler />
      <ReauthDialog />
      <Routes>
        {/* Public auth routes */}
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="/forgot-password" element={<ForgotPasswordPage />} />
        <Route path="/reset-password" element={<ResetPasswordPage />} />

        {/* Public secure link access (no auth required) */}
        <Route path="/s/:token" element={<PublicLinkPage />} />

        {/* Protected routes — wrapped in AppLayout with sidebar + navbar */}
        <Route
          element={
            <ProtectedRoute>
              <AppLayout />
            </ProtectedRoute>
          }
        >
          <Route path="/" element={<PersonalDashboardPage />} />
          <Route path="/vault" element={<VaultListPage />} />
          <Route path="/vault/new" element={<VaultItemCreatePage />} />
          <Route path="/vault/items/:id" element={<VaultItemDetailPage />} />
          <Route path="/vault/items/:id/edit" element={<VaultItemEditPage />} />
          <Route path="/vault/trash" element={<PlaceholderPage title="Trash" module="Module F07" />} />
          <Route path="/shared" element={<SharedVaultListPage />} />
          <Route path="/shared/new" element={<SharedVaultItemCreatePage />} />
          <Route path="/shared/items/:id" element={<SharedVaultItemDetailPage />} />
          <Route path="/shared/items/:id/edit" element={<SharedVaultItemEditPage />} />
          <Route path="/shared/teams/:teamId" element={<TeamVaultListPage />} />
          <Route path="/files" element={<FileListPage />} />
          <Route path="/files/:id" element={<FileDetailPage />} />
          <Route path="/files/trash" element={<FileTrashPage />} />
          <Route path="/notes" element={<NoteListPage />} />
          <Route path="/notes/new" element={<NoteDetailPage />} />
          <Route path="/notes/:id" element={<NoteDetailPage />} />
          <Route path="/notes/trash" element={<NoteTrashPage />} />
          <Route path="/access-requests" element={<AccessRequestsPage />} />
          <Route path="/access-requests/history" element={<RequestHistoryPage />} />
          <Route path="/recent" element={<RecentItemsPage />} />
          <Route path="/expiring" element={<ExpiringAccessPage />} />
          <Route path="/activity" element={<ActivityLogsPage />} />
          <Route path="/admin" element={<CompanyDashboardPage />} />
          <Route path="/admin/teams" element={<TeamsListPage />} />
          <Route path="/admin/teams/:teamId/members" element={<TeamMembersPage />} />
          <Route path="/settings" element={<PlaceholderPage title="Settings" module="Module F23" />} />
          <Route path="/tools" element={<ToolsPage />} />
        </Route>

        {/* 404 catch-all */}
        <Route path="*" element={<NotFoundPage />} />
      </Routes>
    </Providers>
  )
}

export default App
