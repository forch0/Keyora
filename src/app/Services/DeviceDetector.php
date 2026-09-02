<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DeviceDetector
{
    /**
     * Detect or record a device for the given user.
     *
     * Returns an array with device info and whether it was a new device.
     *
     * @return array{device: UserDevice, is_new: bool}
     */
    public function detect(Request $request, User $user): array
    {
        $userAgent = $request->userAgent() ?? 'Unknown';
        $ip = $request->ip() ?? '0.0.0.0';

        $browser = $this->parseBrowser($userAgent);
        $os = $this->parseOs($userAgent);
        $deviceType = $this->parseDeviceType($userAgent);
        $fingerprint = $this->fingerprint($browser, $os, $ip);

        $device = UserDevice::where('user_id', $user->id)
            ->where('device_fingerprint', $fingerprint)
            ->first();

        if ($device !== null) {
            $device->update([
                'last_seen_at' => Carbon::now(),
                'ip_address' => $ip,
            ]);
            $device->refresh();

            return ['device' => $device, 'is_new' => false];
        }

        $device = UserDevice::create([
            'user_id' => $user->id,
            'device_fingerprint' => $fingerprint,
            'browser' => $browser,
            'os' => $os,
            'device_type' => $deviceType,
            'ip_address' => $ip,
            'last_seen_at' => Carbon::now(),
            'first_seen_at' => Carbon::now(),
        ]);

        return ['device' => $device, 'is_new' => true];
    }

    private function parseBrowser(string $userAgent): string
    {
        if (str_contains($userAgent, 'Firefox')) {
            return 'Firefox';
        }

        if (str_contains($userAgent, 'Edg')) {
            return 'Edge';
        }

        if (str_contains($userAgent, 'Chrome')) {
            return 'Chrome';
        }

        if (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        }

        return 'Unknown';
    }

    private function parseOs(string $userAgent): string
    {
        if (str_contains($userAgent, 'Windows')) {
            return 'Windows';
        }

        if (str_contains($userAgent, 'Mac OS')) {
            return 'macOS';
        }

        if (str_contains($userAgent, 'Linux')) {
            return 'Linux';
        }

        if (str_contains($userAgent, 'Android')) {
            return 'Android';
        }

        if (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            return 'iOS';
        }

        return 'Unknown';
    }

    private function parseDeviceType(string $userAgent): string
    {
        if (str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android')) {
            return 'mobile';
        }

        if (str_contains($userAgent, 'iPad') || str_contains($userAgent, 'Tablet')) {
            return 'tablet';
        }

        return 'desktop';
    }

    private function fingerprint(string $browser, string $os, string $ip): string
    {
        // Use IP subnet (/24) for fingerprinting so same network doesn't trigger alerts
        $subnet = implode('.', array_slice(explode('.', $ip), 0, 3));

        return hash('sha256', "{$browser}|{$os}|{$subnet}");
    }
}
