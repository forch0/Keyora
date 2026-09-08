# Keyora — User Guide

A complete step-by-step manual for every feature in the Keyora password and secrets management platform.

---

## Table of Contents

1. [Getting Started](#1-getting-started)
   - [1.1 Sign In](#11-sign-in)
   - [1.2 Sign In with 2FA](#12-sign-in-with-2fa)
   - [1.3 Register a New Account](#13-register-a-new-account)
   - [1.4 Forgot Password](#14-forgot-password)
   - [1.5 Reset Password](#15-reset-password)
2. [Navigation](#2-navigation)
   - [2.1 Sidebar](#21-sidebar)
   - [2.2 Navbar](#22-navbar)
   - [2.3 Global Search](#23-global-search)
   - [2.4 Workspace Switcher](#24-workspace-switcher)
   - [2.5 User Menu](#25-user-menu)
   - [2.6 Security Alerts Bell](#26-security-alerts-bell)
3. [Dashboard](#3-dashboard)
   - [3.1 Personal Dashboard](#31-personal-dashboard)
   - [3.2 Company Dashboard](#32-company-dashboard)
4. [Personal Vault](#4-personal-vault)
   - [4.1 View Vault Items](#41-view-vault-items)
   - [4.2 Search and Filter](#42-search-and-filter)
   - [4.3 Create a Vault Item](#43-create-a-vault-item)
   - [4.4 View a Vault Item](#44-view-a-vault-item)
   - [4.5 Edit a Vault Item](#45-edit-a-vault-item)
   - [4.6 Favorite / Archive / Delete](#46-favorite--archive--delete)
5. [Shared Vault](#5-shared-vault)
   - [5.1 View Shared Items](#51-view-shared-items)
   - [5.2 Create a Shared Item](#52-create-a-shared-item)
   - [5.3 View / Edit / Delete a Shared Item](#53-view--edit--delete-a-shared-item)
   - [5.4 Team Vault](#54-team-vault)
6. [Access Management](#6-access-management)
   - [6.1 Grant Access](#61-grant-access)
   - [6.2 Revoke Access](#62-revoke-access)
   - [6.3 Change Permission Level](#63-change-permission-level)
7. [Access Requests](#7-access-requests)
   - [7.1 Request Access to a Resource](#71-request-access-to-a-resource)
   - [7.2 Review a Request (Approve / Reject)](#72-review-a-request-approve--reject)
   - [7.3 Cancel a Sent Request](#73-cancel-a-sent-request)
   - [7.4 View Request History](#74-view-request-history)
8. [Secure External Sharing](#8-secure-external-sharing)
   - [8.1 Create a Secure Link](#81-create-a-secure-link)
   - [8.2 View Link Activity](#82-view-link-activity)
   - [8.3 Access a Shared Resource (Public Link)](#83-access-a-shared-resource-public-link)
9. [Secure Files](#9-secure-files)
   - [9.1 Upload Files](#91-upload-files)
   - [9.2 View and Edit a File](#92-view-and-edit-a-file)
   - [9.3 Download / Archive / Delete Files](#93-download--archive--delete-files)
   - [9.4 File Folders](#94-file-folders)
   - [9.5 File Trash](#95-file-trash)
10. [Secure Notes](#10-secure-notes)
    - [10.1 Create a Note](#101-create-a-note)
    - [10.2 View and Edit a Note](#102-view-and-edit-a-note)
    - [10.3 Pin / Delete Notes](#103-pin--delete-notes)
    - [10.4 Note Folders](#104-note-folders)
    - [10.5 Note Trash](#105-note-trash)
11. [Teams](#11-teams)
    - [11.1 Create a Team](#111-create-a-team)
    - [11.2 Edit / Delete a Team](#112-edit--delete-a-team)
    - [11.3 Manage Team Members](#113-manage-team-members)
12. [Admin: Workspace Management](#12-admin-workspace-management)
    - [12.1 Workspace Settings](#121-workspace-settings)
    - [12.2 Members List](#122-members-list)
    - [12.3 Invite a Member](#123-invite-a-member)
    - [12.4 Member Detail — Roles](#124-member-detail--roles)
    - [12.5 Suspend / Restore a Member](#125-suspend--restore-a-member)
    - [12.6 Emergency Revoke All Access](#126-emergency-revoke-all-access)
    - [12.7 Remove a Member](#127-remove-a-member)
    - [12.8 Offboard an Employee](#128-offboard-an-employee)
13. [Settings](#13-settings)
    - [13.1 Edit Profile](#131-edit-profile)
    - [13.2 Change Password](#132-change-password)
    - [13.3 Enable 2FA](#133-enable-2fa)
    - [13.4 Disable 2FA](#134-disable-2fa)
    - [13.5 Recovery Codes](#135-recovery-codes)
    - [13.6 Manage Devices](#136-manage-devices)
14. [Security Alerts](#14-security-alerts)
    - [14.1 View Alerts](#141-view-alerts)
    - [14.2 Mark / Dismiss Alerts](#142-mark--dismiss-alerts)
    - [14.3 Revoke a Device](#143-revoke-a-device)
15. [Activity Logs](#15-activity-logs)
16. [Password Tools](#16-password-tools)
    - [16.1 Password Generator](#161-password-generator)
    - [16.2 Password Strength Checker](#162-password-strength-checker)
17. [Auto-Lock & Session Management](#17-auto-lock--session-management)
    - [17.1 Manual Lock](#171-manual-lock)
    - [17.2 Unlock the App](#172-unlock-the-app)
    - [17.3 Re-Authentication Modal](#173-re-authentication-modal)
18. [Recent Items & Expiring Access](#18-recent-items--expiring-access)
    - [18.1 Recent Activity](#181-recent-activity)
    - [18.2 Expiring Access](#182-expiring-access)

---

## 1. Getting Started

### 1.1 Sign In

1. Navigate to the Keyora URL in your browser.
2. You will see the **Sign in to Keyora** page.
3. Click the **Email** field and type your email address.
4. Click the **Password** field and type your password.
5. Click the **Sign in** button.
   - **Success:** You are redirected to the Dashboard.
   - **Invalid credentials:** A red error message appears below the Password field: "Invalid email or password."
   - **Rate limited:** A toast notification appears at the top-right: "Too many requests. Try again in X seconds."

> **Tip:** If you forgot your password, click the **Forgot password?** link below the password field.

### 1.2 Sign In with 2FA

If your account has two-factor authentication enabled:

1. Follow steps 1-5 in [Sign In](#11-sign-in) above.
2. After submitting your credentials, you will see the **Two-Factor Authentication** form.
3. Click the **Authentication code** field.
4. Type the 6-digit code from your authenticator app (Google Authenticator, Authy, etc.).
5. Click the **Verify** button.
   - **Success:** You are redirected to the Dashboard.
   - **Invalid code:** An error message appears below the code field.

> **Alternative:** If you cannot access your authenticator app, click **Use recovery code**, type one of your saved recovery codes, and click **Verify**.

> **Go back:** Click **Back to login** to return to the email/password form.

### 1.3 Register a New Account

1. Go to the login page and click **Register** (or navigate to `/register`).
2. Click the **Name** field and type your full name.
3. Click the **Email** field and type your email address.
4. Click the **Password** field and type a password.
5. Click the **Confirm password** field and re-type the same password.
6. Click the **Register** button.
   - **Success:** You are redirected to the Dashboard.
   - **Validation error:** A red error message appears on the relevant field.
   - **Already have an account?** Click **Sign in** at the bottom to return to the login page.

### 1.4 Forgot Password

1. On the login page, click **Forgot password?**.
2. Click the **Email** field and type your email address.
3. Click the **Send reset link** button.
   - **Success:** The page changes to a confirmation: "Check your email" with a checkmark icon.
4. Check your email for a password reset link.
5. Click **Back to login** to return to the login page.

### 1.5 Reset Password

1. Click the reset link in your email. You will be taken to the **Reset password** page.
   - If the link is invalid or missing the token, you will see "Invalid reset link" with a **Request new link** button. Click it to go back to [Forgot Password](#14-forgot-password).
2. Click the **New password** field and type your new password.
3. Click the **Confirm new password** field and re-type the same password.
4. Click the **Reset password** button.
   - **Success:** A toast appears: "Password has been reset successfully." You are redirected to the login page.
   - **Error:** A toast or field error appears with the details.

---

## 2. Navigation

### 2.1 Sidebar

The sidebar is on the left side of the screen. It contains navigation links to all major sections.

**On desktop (1024px+):**
- The sidebar is always visible.
- Click **Collapse** at the bottom to shrink it to icon-only mode.
- Click **Collapse** again (the chevron will be flipped) to expand it back.

**On mobile (<768px):**
- The sidebar is hidden by default.
- Click the **hamburger menu icon** (three horizontal lines) in the top-left of the navbar to open it as a slide-in drawer.
- Click any link or click the **X** button or tap outside the drawer to close it.

**Navigation links:**
| Link | Route | Description |
|---|---|---|
| Dashboard | `/` | Personal dashboard |
| Personal Vault | `/vault` | Your private vault items |
| Shared Vault | `/shared` | Organization-wide vault items |
| Secure Files | `/files` | Encrypted file storage |
| Secure Notes | `/notes` | Encrypted notes |
| Access Requests | `/access-requests` | Request and approve access |
| Activity Logs | `/activity` | Audit trail |
| Admin | `/admin` | Company dashboard (admin only) |
| Teams | `/admin/teams` | Team management |
| Tools | `/tools` | Password generator and strength checker |
| Settings | `/settings` | Profile, password, 2FA, devices |

### 2.2 Navbar

The navbar is the sticky bar at the top of the screen. From left to right:

- **Hamburger menu** (mobile only) — opens the sidebar drawer.
- **Workspace switcher** — shows the current workspace name; click to switch.
- **Search bar** — click it or press **Cmd/Ctrl+K** to open global search.
- **Security alerts bell** — shows unread alert count; click to view recent alerts.
- **User menu** — click your avatar to open the dropdown.

### 2.3 Global Search

1. Press **Cmd/Ctrl+K** or click the **Search...** bar in the navbar.
2. The **Global Search** modal opens.
3. Start typing your search query. Results appear after a 300ms debounce, grouped by:
   - **Vault Items** — click to go to `/shared/items/:id`
   - **Files** — click to go to `/files/:id`
   - **Notes** — click to go to `/notes/:id`
   - **People** — click to go to the admin section
   - **Teams** — click to go to team members page
4. Click any result to navigate to that resource.
5. When no query is typed, your **recent searches** are shown.
6. Click **Cancel** or press **Escape** to close the search modal.

### 2.4 Workspace Switcher

1. Click the workspace name in the navbar (next to the building icon).
2. A dropdown appears listing all workspaces you belong to, with your role in each.
3. Click a workspace to switch to it. All subsequent API calls will be scoped to that workspace.
4. To create a new workspace, click **Create workspace** at the bottom of the dropdown.
   - Type a **Workspace name** (required).
   - Type a **Slug** (optional — auto-generated from name if left blank).
   - Click **Create**. You become the owner of the new workspace.

### 2.5 User Menu

1. Click your **avatar** (top-right corner of the navbar).
2. A dropdown menu appears showing your name and email.
3. Click **Profile** or **Settings** — both navigate to `/settings`.
4. Click **Lock App** — immediately locks the application (see [Auto-Lock](#17-auto-lock--session-management)).
5. Click **Logout** — signs you out and redirects to the login page.

### 2.6 Security Alerts Bell

1. Click the **bell icon** in the navbar.
2. A dropdown shows your most recent unread security alerts.
3. If there are unread alerts, a red badge with the count appears on the bell.
4. Click **Mark all read** to mark all alerts as read.
5. Click any alert to navigate to the full alerts page (`/security-alerts`).
6. Click **View all alerts** at the bottom of the dropdown to see all alerts.

---

## 3. Dashboard

### 3.1 Personal Dashboard

**Route:** `/` (click **Dashboard** in the sidebar)

The personal dashboard shows:

- **Welcome header** with your first name.
- **Quick action buttons:**
  - Click **Generate Password** — navigates to the Password Tools page.
  - Click **Add Item** — navigates to the Create Vault Item page.
- **Expiring access warning** (if any access grants are expiring within 24 hours) — a yellow warning card.
- **Stat cards:** Total Items, Favorites, Archived, Security Alerts.
- **Pending requests:** Cards showing sent and received pending access request counts.
- **Recent items:** A list of your recently accessed and recently created vault items, files, and notes. Click any item to navigate to its detail page.

### 3.2 Company Dashboard

**Route:** `/admin` (click **Admin** in the sidebar — admin/owner only)

The company dashboard shows:

- **Overview stats:** Total Members, Active Members (with suspended count), Vault Items, Files (with notes count).
- **Access & security stats:** Pending Requests, Active Temp Access (with 24h expiring count), Recent Alerts, Failed Logins (24h).
- **Usage card:** Progress bars for Members, Storage (MB), and Vault Items.
- **Recent Activity feed:** A list of recent company-wide activity log entries.

> This page is read-only. No interactive actions are available.

---

## 4. Personal Vault

### 4.1 View Vault Items

**Route:** `/vault` (click **Personal Vault** in the sidebar)

The vault list page shows all your private vault items in a card grid.

**Layout:**
- **Left sidebar** (desktop only): Folder tree and tag filter chips.
- **Main area:** Search bar, type filters, favorites/archived toggles, sort selector, and the item grid.

**To open an item:** Click any vault item card to navigate to its detail page.

### 4.2 Search and Filter

1. **Search:** Click the search input and type a query. Results update after 300ms. The list switches to search results mode.
2. **Filter by type:** Click one of the type buttons: **All**, **Passwords**, **API Keys**, **Server Credentials**, **Database Credentials**.
3. **Filter by folder:** Click a folder in the left sidebar folder tree. Click **All Files** to clear the folder filter.
4. **Filter by tag:** Click a tag chip in the left sidebar to toggle it.
5. **Favorites only:** Click the **Favorites** toggle button to show only favorited items.
6. **Archived only:** Click the **Archived** toggle button to show only archived items.
7. **Sort:** Click the **Sort by** dropdown and select **Name**, **Created**, or **Last Accessed**.
8. **Pagination:** Click **Prev** or **Next** at the bottom to navigate pages.

> Changing any filter (except page) resets to page 1.

### 4.3 Create a Vault Item

1. Go to the Personal Vault page (`/vault`).
2. Click the **Add Item** button in the top-right.
3. You are taken to the Create Vault Item form.
4. Fill in the fields:
   - **Name** (required) — e.g. "GitHub Account"
   - **Type** (required) — click the dropdown and select: Password, API Key, Server Credential, Database Credential, or Secure Note
   - **Username / Email** — the username or email for this credential
   - **Password** — type a password or click the **Generate** button next to the field to auto-generate one
   - **URL** — the website URL (e.g. https://github.com)
   - **Notes** — any additional notes
   - **Custom fields** — click **Add field** to add key-value pairs (e.g. "API Key" / "sk-xxx")
   - **Folder** — select a folder to organize the item (optional)
   - **Tags** — type tag names and press Enter to add them (optional)
5. Click the **Create** button.
   - **Success:** A toast appears: "Vault item created." You are redirected to the new item's detail page.
   - **Error:** A toast or field-level error message appears.

### 4.4 View a Vault Item

**Route:** `/vault/items/:id` (click any vault item card)

The detail page shows:

- **Header:** Item icon, name, type badge, archived badge, and favorite star.
- **Action buttons:** Favorite, Edit, Archive/Restore, Delete.
- **Details card:**
  - **Username** — click the **Copy** button to copy to clipboard.
  - **Password** — click the **eye icon** to show/hide; click **Copy** to copy.
  - **URL** — click the link to open in a new tab; click **Copy** to copy.
  - **Custom fields** — each with its own copy button.
  - **Notes** — plain text display.
- **Metadata card:** Created date, last updated date, last accessed date.
- **Tags:** Displayed as badges.

> **Clipboard auto-clear:** When you copy a password or sensitive value, the clipboard is automatically cleared after 30 seconds for security.

### 4.5 Edit a Vault Item

1. Open the vault item detail page.
2. Click the **Edit** button (pencil icon).
3. You are taken to the edit form with all fields pre-filled.
4. Modify any fields as needed.
5. Click the **Save** button.
   - **Success:** A toast appears: "Vault item updated." You are redirected back to the item detail page.
   - **Error:** A toast or field-level error appears.
6. To go back without saving, click **Back to item**.

### 4.6 Favorite / Archive / Delete

**Favorite:**
1. Open the vault item detail page.
2. Click the **star icon** (Favorite button) in the header.
   - The star fills in. Click again to unfavorite.

**Archive:**
1. Open the vault item detail page.
2. Click the **Archive** button.
   - **Success:** A toast appears: "Item archived." The item is now hidden from the default list (use the Archived filter to see it).
3. To restore, open the archived item and click **Restore**.
   - **Success:** A toast appears: "Item restored."

**Delete:**
1. Open the vault item detail page.
2. Click the **Delete** button (red, trash icon).
3. A confirmation dialog appears: "Are you sure you want to delete this item?"
4. Click **Delete** to confirm, or **Cancel** to abort.
   - **Success:** A toast appears: "Item moved to trash." You are redirected to the vault list.

---

## 5. Shared Vault

### 5.1 View Shared Items

**Route:** `/shared` (click **Shared Vault** in the sidebar)

The shared vault list shows organization-wide and team-scoped vault items.

**Layout:**
- **Header:** "Shared Vault" title with an **Add Org Item** button.
- **Team filter badges:** Click **Org-wide** to see org-level items, or click a team badge to see that team's items.
- **Search, type filter, sort, and pagination** work the same as the Personal Vault.

**To open an item:** Click any shared vault item card to navigate to its detail page.

> If you do not have access to the shared vault, an "Access Denied" empty state is shown.

### 5.2 Create a Shared Item

1. Go to the Shared Vault page (`/shared`).
2. Click the **Add Org Item** button.
3. Fill in the form (same fields as [Create a Vault Item](#43-create-a-vault-item)).
4. Click **Create**.
   - **Success:** A toast appears: "Shared item created." You are redirected to the new item.
   - **Permission denied:** A toast appears: "You do not have permission to create shared items."

### 5.3 View / Edit / Delete a Shared Item

**View:** Click any shared item card. The detail page shows the same information as a personal vault item, plus:
- **"Team item" or "Org item" badge** in the header.
- **Access Management panel** — see [Access Management](#6-access-management).
- **Secure Links panel** — see [Secure External Sharing](#8-secure-external-sharing).
- **Resource Activity panel** — shows recent activity for this item.

**Edit:**
1. Open the shared item detail page.
2. Click **Edit**.
3. Modify fields and click **Save**.
   - **Success:** "Shared item updated."
   - **Permission denied:** "You do not have permission to edit this item."

**Delete:**
1. Open the shared item detail page.
2. Click **Delete**.
3. Confirm in the dialog.
   - **Success:** "Item deleted." You are redirected to the shared vault list.

### 5.4 Team Vault

**Route:** `/shared/teams/:teamId` (click a team badge in the shared vault)

This page shows vault items belonging to a specific team. It works the same as the shared vault list but filtered to one team.

- Click **Add Team Item** to create a new item in this team's vault.
- Click team badges at the top to switch between teams.
- Click **Back to shared vault** to return to the main shared vault.

---

## 6. Access Management

The Access Management panel appears on the detail page of shared vault items, secure files, and secure notes. It controls who can access a specific resource.

### 6.1 Grant Access

1. Open a shared resource detail page (shared vault item, file, or note).
2. Scroll to the **Access Management** card.
3. Click the **Grant** button (or **Grant Access** if no grants exist yet).
4. The **Grant Access** dialog opens.
5. Choose who to grant access to:
   - **User tab:** Click the **Select user** dropdown and choose a workspace member.
   - **Team tab:** Check the box next to one or more teams.
   - **Workspace tab:** Grants access to every member of the workspace (no selection needed).
6. Click the **Permission** dropdown and select a level:
   - **view** — can view the resource
   - **download** — can view and download
   - **edit** — can view, download, and edit
   - **share** — can view, download, edit, and share
   - **manage** — full management including granting/revoke access
7. Click the **Time limit** dropdown and select an expiry: No expiry, 15m, 30m, 1h, or 24h.
8. (Optional) Type a number in **Max views** to limit how many times the resource can be viewed.
9. (Optional) Toggle **Start countdown on first view** to start the expiry timer from the first view rather than from now.
10. Click **Grant Access**.
    - **Success:** A toast appears: "Access granted." (or "Access granted to team." / "Access granted to N teams." / "Access granted to entire workspace.")
    - **Error:** "Failed to grant access."

### 6.2 Revoke Access

**Revoke a single grant:**
1. In the Access Management panel, find the grant row.
2. Click the **trash icon** on the right side of the row.
   - **Success:** "Access revoked."

**Revoke all grants:**
1. Click the **Revoke All** button (red, top-right of the Access Management card).
2. A confirmation dialog appears.
3. Click **Revoke All** to confirm, or **Cancel** to abort.
   - **Success:** "Revoked X grant(s)."

### 6.3 Change Permission Level

1. In the Access Management panel, find the grant row.
2. Click the **Permission** dropdown on that row.
3. Select a new permission level (view, download, edit, share, manage).
   - **Success:** "Permission updated."

---

## 7. Access Requests

### 7.1 Request Access to a Resource

1. Open a shared resource detail page that you do not have access to (or have insufficient access).
2. Click the **Request Access** button (if available on the page).
3. The **Request Access** dialog opens.
4. Click the **Permission** dropdown and select the level you need (view, download, edit, share, manage).
5. Click the **Duration** dropdown and select how long you need access: No expiry, 15m, 30m, 1h, 24h, 7d, 30d, or permanent.
6. Type a **Reason** (required) explaining why you need access.
7. Click **Submit Request**.
   - **Success:** "Access request submitted." The dialog closes.
   - **Duplicate:** "You already have a pending request for this resource."

### 7.2 Review a Request (Approve / Reject)

1. Go to the Access Requests page (`/access-requests`).
2. Click the **Received** tab to see requests others have sent to you.
3. Find a pending request and click the **Review** button.
4. The **Review Access Request** dialog opens, showing:
   - Requester name, requested permission, and resource name.
   - The reason provided by the requester.
5. Click the **Grant permission** dropdown to set the actual permission level (may differ from requested).
6. Click the **Duration** dropdown to set the access duration.
7. (Optional) Type a **Review note** explaining your decision.
8. Click **Approve** to approve, or **Reject** to reject.
   - **Approve:** "Request approved." The requester gains access.
   - **Reject:** "Request rejected."

### 7.3 Cancel a Sent Request

1. Go to the Access Requests page (`/access-requests`).
2. Click the **Sent** tab to see your outgoing requests.
3. Find a pending request and click the **Cancel** button (trash icon).
   - **Success:** "Request cancelled."

### 7.4 View Request History

1. Go to the Access Requests page (`/access-requests`).
2. Click the **History** button in the top-right.
3. The Request History page shows all past requests (approved, rejected, cancelled, expired).
4. Use the **Status filter** dropdown to filter by: All, Approved, Rejected, Cancelled, Expired.
5. Click **Back to access requests** to return.

---

## 8. Secure External Sharing

### 8.1 Create a Secure Link

Secure links allow you to share a resource with someone outside the organization. They bypass normal authentication.

1. Open a shared resource detail page (vault item, file, or note).
2. Scroll to the **Secure Links** panel.
3. Click **Create Link** (or **Share**).
4. The **Create Secure Link** dialog opens.
5. Fill in the options:
   - **Recipient email** (optional) — the email of the person you are sharing with.
   - **Permission** — select "View only" or "View & download".
   - **Expires in** — select 1h, 24h, 7d, 30d, or never.
   - **Password** (optional) — set a password the recipient must enter.
   - **Max views** (optional) — limit the number of times the link can be accessed.
   - **One-time link** — toggle on to make the link usable only once.
   - **Require email verification** (only if email is set) — toggle on to require the recipient to verify their email before accessing.
6. Click **Create Link**.
   - **Success:** "Secure link created." The generated link URL is shown for you to copy and share.

> **Warning:** The dialog displays an orange warning banner: "This link bypasses authentication. Anyone with the link can access the resource."

### 8.2 View Link Activity

1. In the Secure Links panel, find the link you want to inspect.
2. Click **View activity** (or the activity icon).
3. The **Link Activity** dialog opens, showing a list of all access events:
   - Recipient email (or "Anonymous")
   - IP address
   - User agent (browser/OS)
   - Access timestamp
4. Click **Close** when done.

### 8.3 Access a Shared Resource (Public Link)

If someone shared a secure link with you:

1. Open the link URL in your browser. The page shows the resource name, expiry, and remaining views.
2. Depending on the link's security settings:
   - **No verification needed:** Click **Access Resource** to view the resource immediately.
   - **Password required:** Type the password in the **Password** field and click **Verify & Access**.
   - **OTP required:** Type the OTP code and click **Verify & Access**.
   - **Email verification required:**
     1. Type your email in the **Email** field.
     2. Click **Send** — a verification code is emailed to you.
     3. Type the code in the **Verification code** field.
     4. Click **Verify**.
3. After verification, the resource content is displayed.
4. Click **Copy** to copy any displayed values.
5. Click **Download** (if download permission was granted) to download files.

> If the link is expired, revoked, or exhausted (max views reached), an appropriate message is shown instead.

---

## 9. Secure Files

### 9.1 Upload Files

1. Go to the Secure Files page (`/files`).
2. Click the **Upload** button.
3. The **Upload Files** dialog opens.
4. Either:
   - **Drag and drop** files onto the dashed drop zone, or
   - Click **Browse Files** to open the file picker and select files.
5. (Optional) Type a **Description** for the files.
6. Click **Upload**.
   - **Success:** "File uploaded." (or "N files uploaded." for multiple files)
   - **Error:** "Upload failed."
   - **No files selected:** "Select at least one file."

> **Limit:** Maximum 10 MB per file.

### 9.2 View and Edit a File

**Route:** `/files/:id` (click any file card or file name)

The file detail page shows:

- **Header:** File icon, name, size, MIME type, uploader name, archived badge.
- **Action buttons:** Download, Archive/Restore, Delete.
- **File Details card:**
  - **Checksum** — click **Copy** to copy the file hash.
  - **Created date.**
  - **Name** — click the field to edit, then click **Save**.
  - **Description** — click the textarea to edit, then click **Save**.
  - **Allow downloads** — toggle the switch to enable/disable downloads by others.
  - When any field is edited, **Save** and **Cancel** buttons appear.
- **Replace File card:** Click to select a new file to replace the current one.
  - **Success:** "File replaced."
- **Access Management panel** — see [Access Management](#6-access-management).
- **Secure Links panel** — see [Secure External Sharing](#8-secure-external-sharing).

### 9.3 Download / Archive / Delete Files

**Download:**
- Click the **Download** button on the file detail page, or
- Open the file card dropdown menu (three dots) on the file list and click **Download**.

**Archive:**
- Click **Archive** on the file detail page, or
- Use the file card dropdown menu and click **Archive**.
- **Success:** "File archived."
- To restore, open the archived file and click **Restore**.

**Delete (move to trash):**
- Click **Delete** on the file detail page, or
- Use the file card dropdown menu and click **Delete**.
- A browser confirmation dialog appears. Click **OK** to confirm.
- **Success:** "File moved to trash."

### 9.4 File Folders

**Create a folder:**
1. On the Secure Files page, click **New Folder**.
2. Type a **Folder Name**.
3. Click **Create** (or press Enter).
   - **Success:** "Folder created."

**Edit a folder:**
1. Hover over a folder in the left sidebar.
2. Click the **three dots** (MoreVertical) icon.
3. Click **Edit**.
4. Type a new name and click **Save**.

**Delete a folder:**
1. Hover over a folder, click the **three dots** icon.
2. Click **Delete**.
3. Confirm in the browser dialog.

**Filter by folder:** Click a folder name in the left sidebar to show only files in that folder. Click **All Files** to clear the filter.

### 9.5 File Trash

**Route:** `/files/trash`

1. On the Secure Files page, scroll to find the **Trash** link (or navigate to `/files/trash`).
2. The trash page shows all deleted files.
3. **Restore a file:** Click **Restore** on a trashed file.
   - **Success:** "File restored."
4. **Permanently delete a file:** Click the **trash icon** on a trashed file, then confirm.
   - **Success:** "File permanently deleted."
5. **Empty trash:** Click **Empty Trash**, then click **Delete All** in the confirmation card.
   - **Success:** "Trash emptied."
6. Click **Back to Files** to return to the file list.

---

## 10. Secure Notes

### 10.1 Create a Note

1. Go to the Secure Notes page (`/notes`).
2. Click the **New Note** button.
3. You are taken to a new note page in edit mode.
4. Type a **Title** (required).
5. Type the **Content** in the textarea.
6. Click **Markdown** or **HTML** to set the format.
7. Click **Create**.
   - **Success:** "Note created." You are redirected to the note detail page.
   - **Empty title:** "Title is required."
8. To cancel, click **Cancel** — you are returned to the notes list.

### 10.2 View and Edit a Note

**Route:** `/notes/:id` (click any note card)

**View mode:** Shows the rendered title, content, tags, and a copy button.

**Edit mode:**
1. Click the **Edit** button.
2. Modify the **Title** and **Content** fields.
3. Change the **Format** (Markdown or HTML) if needed.
4. Click **Save**.
   - **Success:** "Note saved." Returns to view mode.
5. To cancel without saving, click **Cancel**.

The note detail page also includes:
- **Access Management panel** — see [Access Management](#6-access-management).
- **Secure Links panel** — see [Secure External Sharing](#8-secure-external-sharing).

### 10.3 Pin / Delete Notes

**Pin a note:**
1. On the notes list, open the note's dropdown menu (three dots).
2. Click **Pin** (or **Unpin** to unpin).
   - Pinned notes appear in a separate "Pinned" section at the top of the list.
- Alternatively, open the note detail page and click the **Pin/Unpin** button in the header.

**Delete a note:**
1. Open the note's dropdown menu (or the note detail page).
2. Click **Delete**.
3. Confirm in the browser dialog.
   - **Success:** "Note moved to trash."

### 10.4 Note Folders

Note folders work identically to [File Folders](#94-file-folders):

1. Click **New Folder** on the Secure Notes page.
2. Type a name and click **Create**.
3. Filter by clicking a folder in the sidebar.
4. Edit or delete folders via the three-dot menu on hover.

### 10.5 Note Trash

**Route:** `/notes/trash`

Works identically to [File Trash](#95-file-trash):

1. View trashed notes.
2. Click **Restore** to restore a note.
3. Click the trash icon to permanently delete.
4. Click **Empty Trash** to delete all.

---

## 11. Teams

### 11.1 Create a Team

1. Go to the Teams page (`/admin/teams` — click **Teams** in the sidebar).
2. Click **Create Team**.
3. In the dialog, type a **Team Name**.
4. (Optional) Type a **Description**.
5. (Optional) Click the **Color** picker to choose a team color.
6. Click **Create**.
   - **Success:** "Team created." The team appears in the grid.

### 11.2 Edit / Delete a Team

**Edit:**
1. Find the team card.
2. Click the **pencil icon** (Edit) on the card.
3. Modify the name, description, or color.
4. Click **Save**.
   - **Success:** "Team updated."

**Delete:**
1. Find the team card.
2. Click the **trash icon** (Delete) on the card.
3. A confirmation dialog appears.
4. Click **Delete** to confirm, or **Cancel** to abort.
   - **Success:** "Team deleted."

### 11.3 Manage Team Members

1. On a team card, click **Manage members**.
2. You are taken to the Team Members page (`/admin/teams/:teamId/members`).

**Add a member:**
1. Click **Add Member**.
2. Type the **User ID** number in the dialog.
3. Select a **Role**: Member or Lead.
4. Click **Add**.
   - **Success:** "Member added."

**Change a member's role:**
1. Find the member row.
2. Click the **Role** dropdown.
3. Select **Member** or **Lead**.
   - **Success:** "Role updated."

**Remove a member:**
1. Find the member row.
2. Click the **trash icon** (Remove).
   - **Success:** "{name} removed from team."

3. Click **Back to teams** to return to the teams list.

> You can also click **View items** on a team card to navigate to that team's vault.

---

## 12. Admin: Workspace Management

> Admin and Owner roles only. Members do not see these features.

### 12.1 Workspace Settings

**Route:** `/admin/settings`

1. Click **Admin** in the sidebar, then click **Settings** on the admin dashboard, or navigate to `/admin/settings`.
2. The page shows the workspace **Name** and **Slug** fields, plus **Plan** and **Role** badges.
3. Edit the **Name** or **Slug** field. **Cancel** and **Save** buttons appear.
4. Click **Save** to save changes.
   - **Success:** "Tenant updated."
5. Click **Cancel** to revert.
6. **Quick Links:** Click **Manage Members** to go to the members list.
7. **Danger Zone** (owners only): Click **Delete Workspace**, then click the red **Delete** button in the confirmation. This permanently deletes the workspace and all its data.
   - **Success:** "Tenant deleted." You are redirected to the home page.

### 12.2 Members List

**Route:** `/admin/members`

1. Click **Admin** in the sidebar, then click **Members**, or navigate to `/admin/members`.
2. The page shows all workspace members.

**Search:**
- Click the **Search** field and type a name or email to filter the list.

**Filter by role:**
- Click **All**, **Owner**, **Admin**, or **Member** buttons.

**Filter by status:**
- Click **All**, **Active**, **Suspended**, or **Offboarded** buttons.

**View a member:**
- Click **Manage** on a member row (or **View** for offboarded members) to go to the member detail page.

### 12.3 Invite a Member

1. On the Members List page, click **Invite Member**.
2. The **Invite Member** dialog opens.
3. Type the invitee's **Email** address.
4. Click **Member** or **Admin** to select their role.
5. Click **Send Invitation**.
   - **Success:** "Invitation sent to {email}." The invitation appears in the Invitations panel.
   - **Error:** "Failed to send invitation."

### 12.4 Member Detail — Roles

**Route:** `/admin/members/:id`

1. On the Members List, click **Manage** on a member.
2. The Member Detail page shows the member's profile, role, status, and management actions.
3. In the **Role** card, click **Member** or **Admin** to change their role.
   - **Success:** "Role changed to member." (or "Role changed to admin.")
   - **Error:** "Failed to change role."

> Owners cannot have their role changed by other users.

### 12.5 Suspend / Restore a Member

1. Open the Member Detail page.
2. In the **Account Status** card:
   - If the member is active, click **Suspend Member**.
     - **Success:** The member is suspended and cannot log in.
   - If the member is suspended, click **Restore Member**.
     - **Success:** The member is restored and can log in again.

### 12.6 Emergency Revoke All Access

1. Open the Member Detail page.
2. In the **Emergency Revoke** card, click **Revoke All Access**.
3. A confirmation appears.
4. Click **Confirm Revoke All**.
   - **Success:** "{count} access grant(s) revoked." All access grants for this member are immediately revoked.
5. Click **Cancel** to abort.

### 12.7 Remove a Member

1. Open the Member Detail page.
2. In the **Remove Member** card, click **Remove Member**.
3. A confirmation appears.
4. Click **Confirm Remove**.
   - **Success:** "Member removed." You are redirected to the members list.
5. Click **Cancel** to abort.

### 12.8 Offboard an Employee

Offboarding performs a comprehensive cleanup: revokes all access grants, removes team memberships, ends workspace membership, revokes API tokens, and revokes secure links.

1. Open the Member Detail page.
2. In the **Offboard Employee** card, click **Offboard {name}**.
3. The **Offboard** dialog opens, showing a checklist of what will happen:
   - All access grants revoked
   - Removed from all teams
   - Tenant membership ended
   - Owned resources transferred or reassigned
   - API tokens revoked
   - Secure links revoked
4. (Optional) Type a **Reason** for the offboarding.
5. Check the **confirmation checkbox** ("I understand this action cannot be undone").
6. Click **Offboard Member** (enabled only after checking the box).
   - **Success:** "{name} has been offboarded." You are redirected to the members list.
7. Click **Cancel** to abort.

> Offboarded members appear in the members list with an "Offboarded" badge and reduced opacity. You can click **View** to see their detail page (read-only).

---

## 13. Settings

**Route:** `/settings` (click **Settings** in the sidebar or **Profile/Settings** in the User Menu)

### 13.1 Edit Profile

1. On the Settings page, find the **Profile** card.
2. Click the **Name** field and edit your name.
3. Click the **Email** field and edit your email.
4. **Cancel** and **Save** buttons appear when you make changes.
5. Click **Save**.
   - **Success:** "Profile updated." The navbar updates to show your new name.
6. Click **Cancel** to revert.

> Your email verification status is shown next to the email field (green "Verified" or yellow "Unverified").

### 13.2 Change Password

1. On the Settings page, find the **Password** card.
2. Click the **Current password** field and type your current password.
3. Click the **New password** field and type your new password.
   - A **strength meter** appears below, showing the password strength in real time.
4. Click the **Confirm password** field and re-type the new password.
5. (Optional) Click the **eye icon** on any field to show/hide the password.
6. Click **Change Password**.
   - **Success:** "Password changed." All fields are cleared.
   - **Mismatch:** An error appears if the new and confirm passwords do not match.
   - **Wrong current password:** An error appears.

### 13.3 Enable 2FA

1. On the Settings page, find the **Two-Factor Authentication** card.
2. Click **Enable 2FA**.
3. A **QR code** and **manual secret key** appear.
4. Open your authenticator app (Google Authenticator, Authy, etc.).
5. Either:
   - Scan the QR code with your app, or
   - Manually type the secret key into your app.
6. Type the **6-digit code** from your authenticator app into the TOTP input field.
7. Click **Confirm**.
   - **Success:** "2FA enabled. Save your recovery codes." Your recovery codes are displayed.
8. **Save your recovery codes** — see [Recovery Codes](#135-recovery-codes) below.
9. Click **Cancel** to abort the 2FA setup.

### 13.4 Disable 2FA

1. On the Settings page, in the **Two-Factor Authentication** card (when 2FA is enabled), click **Disable 2FA**.
2. A **password input** appears.
3. Type your current password.
4. Click **Confirm Disable**.
   - **Success:** "2FA disabled."
   - **Wrong password:** An error appears.

> Disabling 2FA may trigger the re-authentication modal (see [Re-Authentication Modal](#173-re-authentication-modal)).

### 13.5 Recovery Codes

After enabling 2FA, your recovery codes are displayed once. Save them in a secure location.

**View recovery codes:**
1. In the 2FA card (when enabled), click **View Recovery Codes**.
2. The codes are displayed.

**Download recovery codes:**
1. Click **Download**.
2. A file named `keyora-recovery-codes.txt` is downloaded to your computer.

> **Important:** Each recovery code can only be used once. Store them securely. If you lose both your authenticator device and your recovery codes, you will need to contact an administrator.

### 13.6 Manage Devices

1. On the Settings page, find the **Devices** card.
2. Click **Manage Devices**.
3. You are taken to the Devices page (`/settings/devices`).
4. See [Revoke a Device](#143-revoke-a-device) below.

---

## 14. Security Alerts

### 14.1 View Alerts

**Route:** `/security-alerts` (click **View all alerts** in the bell dropdown, or navigate directly)

The Security Alerts page shows all security alerts.

**Filter by severity:**
- Click **All**, **Info**, **Warning**, or **Critical** buttons to filter.

Each alert card shows:
- Severity icon (info, warning, or critical).
- Title and severity badge.
- Message text.
- Timestamp.
- Unread indicator (blue dot).

### 14.2 Mark / Dismiss Alerts

**Mark all as read:**
1. Click **Mark all read** at the top of the page.
   - **Success:** "All alerts marked as read."

**Mark a single alert as read:**
1. Find an unread alert (has a blue dot).
2. Click the **check icon** (Mark as read).
   - The blue dot disappears.

**Dismiss an alert:**
1. Find an alert.
2. Click the **X icon** (Dismiss).
3. A confirmation appears: "Dismiss this alert?"
4. Click **Confirm** to dismiss, or **Cancel** to keep it.
   - **Success:** "Alert dismissed."

### 14.3 Revoke a Device

**Route:** `/settings/devices`

1. On the Devices page, you see a list of all devices/sessions associated with your account.
2. Each device card shows:
   - Device type icon.
   - Browser and OS information.
   - IP address.
   - Last seen and first seen timestamps.
   - **"This device" badge** if it is your current session.
3. To revoke a device:
   - Click the **trash icon** (Revoke) on a device.
   - A confirmation appears.
   - Click **Confirm** to revoke, or **Cancel** to abort.
   - **Success:** "Device revoked."
4. The current device's revoke button is disabled (you cannot revoke your own current session).
5. Click **Back to Settings** to return.

---

## 15. Activity Logs

**Route:** `/activity` (click **Activity Logs** in the sidebar)

The Activity Logs page shows a chronological list of actions taken in the system.

**Tabs:**
- **Personal** — shows your own activity.
- **Company** — shows all workspace activity (admin/owner only).

**To switch tabs:** Click **Personal** or **Company**.

**To navigate:** Click **Prev** or **Next** at the bottom for pagination.

Each log entry shows:
- Action name (e.g. "vault.item.created", "access.grant.revoked").
- Subject type badge.
- User name and IP address.
- Timestamp.

> This page is read-only. No actions can be performed here.

---

## 16. Password Tools

**Route:** `/tools` (click **Tools** in the sidebar)

### 16.1 Password Generator

1. On the Tools page, click the **Generator** tab.
2. Adjust the settings:
   - **Length:** Drag the slider to set the desired password length (default: 20).
   - **Uppercase:** Toggle on/off to include A-Z.
   - **Lowercase:** Toggle on/off to include a-z.
   - **Numbers:** Toggle on/off to include 0-9.
   - **Symbols:** Toggle on/off to include special characters.
3. Click **Generate**.
   - A password appears in the text field.
   - The strength of the generated password is checked automatically.
4. Click the **eye icon** to show/hide the generated password.
5. Click **Copy** to copy the password to clipboard.
6. Click **Use Password** (if available) to use the generated password in a vault item form.

### 16.2 Password Strength Checker

1. On the Tools page, click the **Strength** tab.
2. Click the input field and type or paste a password.
3. The strength is evaluated and displayed:
   - A visual strength indicator (weak, fair, good, strong).
   - An estimated crack time.
   - Specific feedback on what could make the password stronger.

---

## 17. Auto-Lock & Session Management

### 17.1 Manual Lock

**Via User Menu:**
1. Click your **avatar** in the top-right corner.
2. Click **Lock App**.
3. The app immediately switches to the Lock Screen.

**Via Keyboard Shortcut:**
- Press **Cmd+L** (Mac) or **Ctrl+L** (Windows/Linux).
- The app immediately locks.

**Auto-Lock:**
- The app automatically locks after 15 minutes of inactivity (default).
- The timeout is configurable: 5, 15, 30, or 60 minutes.
- Any mouse movement, key press, scroll, or touch resets the inactivity timer.

### 17.2 Unlock the App

When the app is locked, the Lock Screen appears:

1. The screen shows "App Locked" and "Your session has been locked due to inactivity."
2. Your name and email are displayed.
3. Click the **Password** field and type your password.
4. Click **Unlock** (or press **Enter**).
   - **Success:** "Welcome back." The app unlocks and you return to where you were.
   - **Wrong password:** An error message appears: "Incorrect password."
5. To log out instead, click **Logout instead**.
   - You are redirected to the login page.

### 17.3 Re-Authentication Modal

Certain sensitive actions (disabling 2FA, emergency revoke, offboarding, deleting resources) require you to re-enter your password even when already logged in.

1. When you attempt a sensitive action, the **Re-authentication Required** modal appears.
2. Click the **Password** field and type your password.
3. Click **Re-authenticate** (or press **Enter**).
   - **Success:** "Re-authenticated successfully." The modal closes and your original action proceeds automatically.
   - **Wrong password:** An error message appears. The modal stays open so you can try again.
4. To cancel, click **Cancel**.
   - The modal closes and your original action is cancelled.

> If multiple sensitive actions are triggered at the same time, only one modal appears. After successful re-authentication, all pending actions proceed.

---

## 18. Recent Items & Expiring Access

### 18.1 Recent Activity

**Route:** `/recent`

This page shows two sections:

- **Recently Accessed:** Vault items, files, and notes you have recently viewed.
- **Recently Created:** Vault items, files, and notes you have recently created.

**To open an item:** Click any row to navigate to that resource's detail page.

### 18.2 Expiring Access

**Route:** `/expiring`

This page shows access grants and secure links that are about to expire.

**Access Grants section:**
- Each row shows the resource type, ID, and expiration time.
- Click **View** to navigate to the resource.
- Click **Request Extension** to go to the Access Requests page and request more time.

**Secure Links section:**
- Each row shows the resource type, ID, and expiration time.
- Click **View** to navigate to the resource.

> If nothing is expiring, an empty state is shown.

---

## Keyboard Shortcuts

| Shortcut | Action |
|---|---|
| **Cmd/Ctrl + K** | Open global search |
| **Cmd/Ctrl + L** | Lock the app |
| **Enter** (in lock screen) | Unlock the app |
| **Enter** (in re-auth modal) | Submit re-authentication |
| **Escape** | Close dialogs/modals |

---

## Error Messages

| Error | Meaning | What to do |
|---|---|---|
| "Session expired" | Your login session has ended | You are automatically redirected to the login page. Sign in again. |
| "You don't have permission to do this" | You lack the required role or permission | Contact an administrator. |
| "Resource not found" | The item does not exist or was deleted | Go back and try again. |
| "Too many requests. Try again in X seconds" | You have been rate limited | Wait the specified time before retrying. |
| "Something went wrong. Please try again." | A server error occurred | Try again. If it persists, contact support. |
| "Connection error. Check your network." | Your device is offline or the server is unreachable | Check your network connection. |
| "Re-authentication cancelled" | You cancelled the re-auth modal | The action was not performed. Try again and complete re-authentication. |

---

## Tips

- **Copy buttons** automatically clear your clipboard after 30 seconds for security.
- **Global search** (Cmd/Ctrl+K) searches across vault items, files, notes, people, and teams simultaneously.
- **Workspace switcher** changes the context for all pages — vault items, members, teams, and activity logs are scoped to the selected workspace.
- **Auto-lock** protects your account if you step away. Adjust the timeout in settings.
- **Recovery codes** are shown only once when enabling 2FA. Download and store them securely.
- **Offboarding** is irreversible — it revokes all access, removes team memberships, and ends workspace membership in one action.
- **Secure links** bypass authentication — only share them with trusted recipients and use password protection when possible.
