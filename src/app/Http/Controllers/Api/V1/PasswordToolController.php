<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tools\CheckPasswordStrengthRequest;
use App\Http\Requests\Tools\GeneratePasswordRequest;
use App\Services\PasswordGenerator;
use App\Services\PasswordStrengthChecker;
use Illuminate\Http\JsonResponse;

class PasswordToolController extends Controller
{
    public function __construct(
        private readonly PasswordGenerator $generator,
        private readonly PasswordStrengthChecker $checker,
    ) {}

    /**
     * Generate a password with customizable criteria.
     */
    public function generate(GeneratePasswordRequest $request): JsonResponse
    {
        $options = $request->validated();

        // Apply defaults for boolean options
        $options['uppercase'] = (bool) ($options['uppercase'] ?? true);
        $options['lowercase'] = (bool) ($options['lowercase'] ?? true);
        $options['numbers'] = (bool) ($options['numbers'] ?? true);
        $options['symbols'] = (bool) ($options['symbols'] ?? true);

        $password = $this->generator->generate($options);

        return response()->json([
            'data' => [
                'password' => $password,
                'options' => [
                    'length' => (int) ($options['length'] ?? 16),
                    'uppercase' => $options['uppercase'],
                    'lowercase' => $options['lowercase'],
                    'numbers' => $options['numbers'],
                    'symbols' => $options['symbols'],
                ],
            ],
        ]);
    }

    /**
     * Check password strength.
     */
    public function checkStrength(CheckPasswordStrengthRequest $request): JsonResponse
    {
        $password = $request->validated('password');

        $analysis = $this->checker->check($password);

        return response()->json([
            'data' => $analysis,
        ]);
    }
}
