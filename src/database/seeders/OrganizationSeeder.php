<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AccessGrant;
use App\Models\ActivityLog;
use App\Models\SecurityAlert;
use App\Models\SecureFile;
use App\Models\SecureNote;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\VaultItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a realistic Group → Subsidiary → Department hierarchy.
 *
 * Structure:
 *   Acme Group (conceptual — no DB entity, Option 2)
 *   ├── Acme Corp (tenant/subsidiary)
 *   │   ├── Engineering (team/department)
 *   │   ├── Finance (team/department)
 *   │   └── Sales (team/department)
 *   ├── Acme Europe (tenant/subsidiary)
 *   │   ├── Engineering (team/department)
 *   │   └── Marketing (team/department)
 *   └── Acme Asia (tenant/subsidiary)
 *       └── Operations (team/department)
 *
 * Users:
 *   - 1 Group Admin (CISO) — admin in all 3 subsidiaries
 *   - 1 Owner per subsidiary
 *   - 1 Admin per subsidiary
 *   - 3-5 Members per subsidiary, assigned to departments
 *
 * All passwords are "password" for local development.
 */
class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Acme Group organization...');

        // ─── Create the Group Admin (CISO) ────────────────────────────────
        $groupAdmin = User::factory()->create([
            'name' => 'Sarah Chen',
            'email' => 'ciso@acme.com',
            'password' => Hash::make('password'),
        ]);
        $this->command->info("  Group Admin: ciso@acme.com");

        // ─── Define subsidiaries and their departments ────────────────────
        $subsidiaries = [
            [
                'name' => 'Acme Corp',
                'slug' => 'acme-corp',
                'owner' => [
                    'name' => 'Michael Torres',
                    'email' => 'michael.torres@acme.com',
                ],
                'admin' => [
                    'name' => 'Jessica Liu',
                    'email' => 'jessica.liu@acme.com',
                ],
                'departments' => ['Engineering', 'Finance', 'Sales'],
                'member_count' => 5,
            ],
            [
                'name' => 'Acme Europe',
                'slug' => 'acme-eu',
                'owner' => [
                    'name' => 'David O\'Connor',
                    'email' => 'david.oconnor@acme-eu.com',
                ],
                'admin' => [
                    'name' => 'Maria Garcia',
                    'email' => 'maria.garcia@acme-eu.com',
                ],
                'departments' => ['Engineering', 'Marketing'],
                'member_count' => 4,
            ],
            [
                'name' => 'Acme Asia',
                'slug' => 'acme-asia',
                'owner' => [
                    'name' => 'Hiroshi Tanaka',
                    'email' => 'hiroshi.tanaka@acme-asia.com',
                ],
                'admin' => [
                    'name' => 'Priya Patel',
                    'email' => 'priya.patel@acme-asia.com',
                ],
                'departments' => ['Operations'],
                'member_count' => 3,
            ],
        ];

        foreach ($subsidiaries as $sub) {
            $this->seedSubsidiary($sub, $groupAdmin);
        }

        $this->command->info('Organization seeding complete!');
        $this->command->info('');
        $this->command->info('Login credentials (password: "password"):');
        $this->command->info('  Group Admin:  ciso@acme.com');
        $this->command->info('  Acme Corp:    michael.torres@acme.com');
        $this->command->info('  Acme Europe:  david.oconnor@acme-eu.com');
        $this->command->info('  Acme Asia:    hiroshi.tanaka@acme-asia.com');
    }

    private function seedSubsidiary(array $sub, User $groupAdmin): void
    {
        $this->command->info("  Creating subsidiary: {$sub['name']}");

        // ─── Create the tenant (subsidiary) ──────────────────────────────
        $tenant = Tenant::create([
            'name' => $sub['name'],
            'slug' => $sub['slug'],
            'plan' => 'standard',
            'settings' => null,
            'trial_ends_at' => null,
        ]);

        // ─── Create owner and admin users ────────────────────────────────
        $owner = User::factory()->create([
            'name' => $sub['owner']['name'],
            'email' => $sub['owner']['email'],
            'password' => Hash::make('password'),
        ]);

        $admin = User::factory()->create([
            'name' => $sub['admin']['name'],
            'email' => $sub['admin']['email'],
            'password' => Hash::make('password'),
        ]);

        // Attach owner, admin, and group admin to the tenant
        $tenant->users()->attach($owner, [
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $tenant->users()->attach($admin, [
            'role' => 'admin',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        // Group admin is invited as admin to every subsidiary
        $tenant->users()->attach($groupAdmin, [
            'role' => 'admin',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->command->info("    Owner: {$owner->email}");
        $this->command->info("    Admin: {$admin->email}");

        // ─── Create departments (teams) ──────────────────────────────────
        $teams = [];
        foreach ($sub['departments'] as $deptName) {
            $team = Team::create([
                'tenant_id' => $tenant->id,
                'name' => $deptName,
                'description' => "{$deptName} department at {$sub['name']}",
                'color' => $this->teamColor($deptName),
                'created_by' => $owner->id,
            ]);
            $teams[$deptName] = $team;
            $this->command->info("    Department: {$deptName}");
        }

        // ─── Create regular members and assign to departments ────────────
        $members = [];
        $deptNames = array_keys($teams);
        for ($i = 0; $i < $sub['member_count']; $i++) {
            $member = User::factory()->create([
                'password' => Hash::make('password'),
            ]);

            $tenant->users()->attach($member, [
                'role' => 'member',
                'status' => 'active',
                'joined_at' => now()->subDays(rand(1, 365)),
            ]);

            // Assign to a random department
            $deptName = $deptNames[array_rand($deptNames)];
            $teams[$deptName]->members()->attach($member, [
                'role' => 'member',
                'joined_at' => now()->subDays(rand(1, 300)),
            ]);

            $members[] = $member;
        }

        // Add owner and admin to a department too
        $firstDept = $teams[$deptNames[0]];
        $firstDept->members()->attach($owner, [
            'role' => 'lead',
            'joined_at' => now()->subDays(400),
        ]);

        // ─── Create personal vault items for each member ─────────────────
        $allUsers = array_merge([$owner, $admin, $groupAdmin], $members);
        foreach ($allUsers as $user) {
            $this->seedPersonalVaultItems($tenant, $user, rand(3, 8));
        }

        // ─── Create org-wide shared vault items ──────────────────────────
        $this->seedSharedVaultItems($tenant, $owner, rand(3, 6));

        // ─── Create team-scoped vault items ──────────────────────────────
        foreach ($teams as $deptName => $team) {
            $this->seedTeamVaultItems($tenant, $team, $owner, rand(2, 5));
        }

        // ─── Create secure files ─────────────────────────────────────────
        $this->seedSecureFiles($tenant, $owner, $members, rand(3, 6));

        // ─── Create secure notes ─────────────────────────────────────────
        $this->seedSecureNotes($tenant, $owner, $members, rand(2, 5));

        // ─── Create access grants (cross-department sharing) ─────────────
        if (count($teams) >= 2) {
            $this->seedAccessGrants($tenant, $teams, $owner);
        }

        // ─── Create security alerts ──────────────────────────────────────
        $this->seedSecurityAlerts($tenant, $allUsers);

        // ─── Create activity logs ────────────────────────────────────────
        $this->seedActivityLogs($tenant, $allUsers);

        // ─── Create user devices ─────────────────────────────────────────
        foreach ($allUsers as $user) {
            $this->seedDevices($user);
        }
    }

    /**
     * Seed personal vault items for a user within a tenant.
     */
    private function seedPersonalVaultItems(Tenant $tenant, User $user, int $count): void
    {
        $itemNames = [
            'GitHub Account',
            'AWS Console Login',
            'Slack Workspace',
            'Google Workspace',
            'Stripe Dashboard',
            'DigitalOcean API',
            'PostgreSQL Server',
            'Redis Instance',
            'VPN Credentials',
            'SSH Key - Production',
            'Jira Admin',
            'Cloudflare Dashboard',
            'Datadog Monitor',
            'Sentry Dashboard',
            'Linear Account',
        ];

        $types = ['password', 'api_key', 'server', 'database', 'note'];

        for ($i = 0; $i < $count; $i++) {
            VaultItem::factory()->create([
                'tenant_id' => $tenant->id,
                'team_id' => null,
                'user_id' => $user->id,
                'name' => $itemNames[array_rand($itemNames)],
                'type' => $types[array_rand($types)],
                'favorite' => fake()->boolean(25),
            ]);
        }
    }

    /**
     * Seed org-wide shared vault items.
     */
    private function seedSharedVaultItems(Tenant $tenant, User $owner, int $count): void
    {
        $orgItems = [
            'Company WiFi Password',
            'Office Alarm Code',
            'Shared AWS Root Account',
            'Company Twitter/X Account',
            'Domain Registrar Login',
            'Shared Mailchimp Account',
            'Company LinkedIn Page',
            'Emergency Contact List',
        ];

        for ($i = 0; $i < $count; $i++) {
            VaultItem::factory()->create([
                'tenant_id' => $tenant->id,
                'team_id' => null,
                'user_id' => $owner->id,
                'name' => $orgItems[array_rand($orgItems)],
                'type' => 'password',
                'favorite' => false,
            ]);
        }
    }

    /**
     * Seed team-scoped vault items.
     */
    private function seedTeamVaultItems(Tenant $tenant, Team $team, User $owner, int $count): void
    {
        $teamItems = [
            'Engineering' => [
                'CI/CD Pipeline Token',
                'Production Database Password',
                'Staging Server SSH Key',
                'Docker Registry Login',
                'Kubernetes Admin Config',
            ],
            'Finance' => [
                'Banking Portal Login',
                'QuickBooks Admin',
                'Payroll System Access',
                'Tax Filing Credentials',
            ],
            'Sales' => [
                'Salesforce Admin Account',
                'HubSpot API Key',
                'Zoom Account Credentials',
                'Outreach.io Login',
            ],
            'Marketing' => [
                'Google Ads Account',
                'Facebook Business Manager',
                'Mailchimp API Key',
                'WordPress Admin',
            ],
            'Operations' => [
                'Inventory System Login',
                'Shipping Platform Access',
                'Supplier Portal Credentials',
            ],
        ];

        $items = $teamItems[$team->name] ?? ['Team Resource', 'Team Credential', 'Team Login'];

        for ($i = 0; $i < $count; $i++) {
            VaultItem::factory()->create([
                'tenant_id' => $tenant->id,
                'team_id' => $team->id,
                'user_id' => $owner->id,
                'name' => $items[array_rand($items)],
                'type' => fake()->randomElement(['password', 'api_key', 'server']),
                'favorite' => false,
            ]);
        }
    }

    /**
     * Seed secure files.
     */
    private function seedSecureFiles(Tenant $tenant, User $owner, array $members, int $count): void
    {
        $fileNames = [
            'Employee_Handbook.pdf',
            'Security_Policy.pdf',
            'Network_Diagram.png',
            'Compliance_Audit.pdf',
            'Incident_Response_Plan.pdf',
            'Vendor_Contracts.zip',
            'SSL_Certificates.zip',
            'Backup_Encryption_Keys.txt',
        ];

        for ($i = 0; $i < $count; $i++) {
            $uploader = fake()->randomElement(array_merge([$owner], $members));

            SecureFile::factory()->create([
                'tenant_id' => $tenant->id,
                'team_id' => null,
                'user_id' => $uploader->id,
                'name' => $fileNames[array_rand($fileNames)],
                'description' => fake()->optional(0.6)->sentence(),
            ]);
        }
    }

    /**
     * Seed secure notes.
     */
    private function seedSecureNotes(Tenant $tenant, User $owner, array $members, int $count): void
    {
        $noteTitles = [
            'Server Maintenance Log',
            'Onboarding Checklist',
            'Incident Post-Mortem',
            'Change Management Notes',
            'Security Audit Findings',
            'Access Review Notes',
        ];

        for ($i = 0; $i < $count; $i++) {
            $author = fake()->randomElement(array_merge([$owner], $members));

            SecureNote::factory()->create([
                'tenant_id' => $tenant->id,
                'team_id' => null,
                'user_id' => $author->id,
                'title' => $noteTitles[array_rand($noteTitles)],
                'content' => fake()->paragraphs(rand(1, 3), true),
                'is_pinned' => fake()->boolean(20),
            ]);
        }
    }

    /**
     * Seed access grants demonstrating cross-department sharing.
     */
    private function seedAccessGrants(Tenant $tenant, array $teams, User $owner): void
    {
        $deptNames = array_keys($teams);
        if (count($deptNames) < 2) {
            return;
        }

        // Grant the first team view access to a vault item owned by the second team's lead
        $teamA = $teams[$deptNames[0]];
        $teamB = $teams[$deptNames[1]];

        // Find a vault item in team A (bypass tenant scope since we're seeding)
        $item = VaultItem::withoutTenant()
            ->where('tenant_id', $tenant->id)
            ->where('team_id', $teamA->id)
            ->first();

        if (!$item) {
            return;
        }

        // Grant team B view access to team A's item
        AccessGrant::create([
            'tenant_id' => $tenant->id,
            'grantable_type' => 'team',
            'grantable_id' => $teamB->id,
            'subject_type' => 'vault_item',
            'subject_id' => $item->id,
            'permission' => 'view',
            'expires_at' => now()->addDays(30),
            'max_views' => null,
            'views_count' => 0,
            'starts_at' => now(),
            'start_on_first_view' => false,
            'first_viewed_at' => null,
            'warning_sent_at' => null,
            'granted_by' => $owner->id,
            'revoked_at' => null,
            'revoked_by' => null,
            'revoke_reason' => null,
        ]);

        $this->command->info("    Access grant: {$teamB->name} can view {$teamA->name}'s item");
    }

    /**
     * Seed security alerts for users in the tenant.
     */
    private function seedSecurityAlerts(Tenant $tenant, array $users): void
    {
        $alerts = [
            [
                'type' => 'suspicious_activity',
                'severity' => 'warning',
                'title' => 'Login from new location',
                'message' => 'A login was detected from a new IP address. If this was you, no action is needed.',
            ],
            [
                'type' => 'suspicious_activity',
                'severity' => 'critical',
                'title' => 'Failed login attempts',
                'message' => 'Multiple failed login attempts were detected on your account.',
            ],
            [
                'type' => 'expiring_access',
                'severity' => 'info',
                'title' => 'Access grant expiring soon',
                'message' => 'One of your access grants will expire within 24 hours.',
            ],
            [
                'type' => 'access_expired',
                'severity' => 'info',
                'title' => 'Access grant expired',
                'message' => 'An access grant has expired. Request an extension if needed.',
            ],
        ];

        // Give 1-3 alerts to random users
        $alertUsers = fake()->randomElements($users, min(3, count($users)));
        foreach ($alertUsers as $user) {
            $userAlerts = fake()->randomElements($alerts, rand(1, 2));
            foreach ($userAlerts as $alert) {
                SecurityAlert::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'type' => $alert['type'],
                    'severity' => $alert['severity'],
                    'title' => $alert['title'],
                    'message' => $alert['message'],
                    'properties' => null,
                    'read_at' => fake()->boolean(40) ? now()->subHours(rand(1, 48)) : null,
                    'dismissed_at' => null,
                ]);
            }
        }
    }

    /**
     * Seed activity logs for the tenant.
     */
    private function seedActivityLogs(Tenant $tenant, array $users): void
    {
        $actions = [
            'vault.item.created',
            'vault.item.viewed',
            'vault.item.updated',
            'vault.item.archived',
            'file.uploaded',
            'file.downloaded',
            'note.created',
            'note.updated',
            'access.grant.created',
            'access.grant.revoked',
            'team.member.added',
            'team.member.removed',
            'member.invited',
            'member.role_changed',
            'auth.login',
            'auth.logout',
        ];

        $logCount = rand(15, 30);
        for ($i = 0; $i < $logCount; $i++) {
            $user = fake()->randomElement($users);
            $action = fake()->randomElement($actions);

            ActivityLog::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => $action,
                'subject_type' => fake()->randomElement(['VaultItem', 'SecureFile', 'SecureNote', 'Team', 'User', null]),
                'subject_id' => rand(1, 20),
                'properties' => null,
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'created_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
            ]);
        }
    }

    /**
     * Seed user devices.
     */
    private function seedDevices(User $user): void
    {
        $browsers = ['Chrome 128', 'Firefox 129', 'Safari 17', 'Edge 128'];
        $oses = ['macOS 14', 'Windows 11', 'Ubuntu 24.04', 'iOS 17', 'Android 14'];
        $deviceTypes = ['desktop', 'desktop', 'desktop', 'mobile', 'tablet'];

        $deviceCount = rand(1, 3);
        for ($i = 0; $i < $deviceCount; $i++) {
            UserDevice::create([
                'user_id' => $user->id,
                'device_fingerprint' => fake()->uuid(),
                'browser' => fake()->randomElement($browsers),
                'os' => fake()->randomElement($oses),
                'device_type' => fake()->randomElement($deviceTypes),
                'ip_address' => fake()->ipv4(),
                'last_seen_at' => now()->subDays(rand(0, 14)),
                'first_seen_at' => now()->subDays(rand(14, 365)),
            ]);
        }
    }

    /**
     * Get a consistent color for a department name.
     */
    private function teamColor(string $name): string
    {
        $colors = [
            'Engineering' => '#3b82f6', // blue
            'Finance' => '#10b981',     // green
            'Sales' => '#f59e0b',       // amber
            'Marketing' => '#ec4899',   // pink
            'Operations' => '#8b5cf6',  // purple
        ];

        return $colors[$name] ?? '#6b7280';
    }
}
