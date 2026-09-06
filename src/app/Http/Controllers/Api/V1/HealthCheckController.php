<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Group('Health')]
class HealthCheckController extends Controller
{
    /**
     * Health check endpoint for load balancers and monitoring.
     *
     * No authentication required. Returns 200 if all checks pass,
     * 503 if any check fails.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $status = 'ok';
        $httpStatus = 200;

        foreach ($checks as $check) {
            if ($check['status'] !== 'ok') {
                $status = 'degraded';
                $httpStatus = 503;

                break;
            }
        }

        return response()->json([
            'status' => $status,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $httpStatus);
    }

    /**
     * @return array{status: string, detail?: string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'detail' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, detail?: string}
     */
    private function checkCache(): array
    {
        try {
            cache()->store()->put('health_check', true, 1);
            cache()->store()->forget('health_check');

            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'detail' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, detail?: string}
     */
    private function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');

            if (! $disk->exists('health-check')) {
                $disk->put('health-check', 'ok');
            }

            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'detail' => $e->getMessage()];
        }
    }
}
