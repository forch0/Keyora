<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\AccessSharedLinkAction;
use App\Actions\ConfirmLinkEmailVerificationAction;
use App\Actions\SendLinkEmailVerificationAction;
use App\Actions\VerifyLinkAccessAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SecureLinks\EmailConfirmRequest;
use App\Http\Requests\SecureLinks\EmailVerifyRequest;
use App\Http\Requests\SecureLinks\VerifyLinkRequest;
use App\Http\Resources\V1\PublicLinkResource;
use App\Models\SecureLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicLinkController extends Controller
{
    public function __construct(
        private readonly VerifyLinkAccessAction $verifyAccess,
        private readonly SendLinkEmailVerificationAction $sendEmailVerification,
        private readonly ConfirmLinkEmailVerificationAction $confirmEmailVerification,
        private readonly AccessSharedLinkAction $accessLink,
    ) {}

    /**
     * Get link info — what verification is required (no resource content).
     */
    public function show(string $uuid): JsonResponse
    {
        $link = $this->resolveLink($uuid);

        return (new PublicLinkResource($link))->response();
    }

    /**
     * Verify password and/or OTP — returns access token.
     */
    public function verify(VerifyLinkRequest $request, string $uuid): JsonResponse
    {
        $link = $this->resolveLink($uuid);
        $this->ensureNotRevokedOrExpired($link);

        $result = ($this->verifyAccess)($link, $request->validated());

        return response()->json([
            'data' => [
                'access_token' => $result['token'],
            ],
        ]);
    }

    /**
     * Send email verification code.
     */
    public function sendEmailVerification(EmailVerifyRequest $request, string $uuid): JsonResponse
    {
        $link = $this->resolveLink($uuid);
        $this->ensureNotRevokedOrExpired($link);

        ($this->sendEmailVerification)($link, $request->string('email')->toString());

        return response()->json([
            'data' => [
                'message' => 'Verification code sent.',
            ],
        ]);
    }

    /**
     * Confirm email verification code — returns access token.
     */
    public function confirmEmailVerification(EmailConfirmRequest $request, string $uuid): JsonResponse
    {
        $link = $this->resolveLink($uuid);
        $this->ensureNotRevokedOrExpired($link);

        $result = ($this->confirmEmailVerification)($link, $request->string('code')->toString());

        return response()->json([
            'data' => [
                'access_token' => $result['token'],
            ],
        ]);
    }

    /**
     * Access the shared resource — requires valid signed URL token.
     */
    public function resource(Request $request, string $uuid): JsonResponse
    {
        // Validate the signed URL
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired access token.');
        }

        $link = $this->resolveLink($uuid);

        $context = [
            'ip_address' => $request->ip() ?? '0.0.0.0',
            'user_agent' => $request->userAgent() ?? '',
            'email' => $link->recipient_email,
        ];

        $result = ($this->accessLink)($link, $context);

        $resource = $result['resource'];

        // Return resource content (decrypted where applicable)
        return response()->json([
            'data' => [
                'resource_type' => $resource::class,
                'resource_id' => $resource->getKey(),
                'name' => $resource->getAttribute('title')
                    ?? $resource->getAttribute('name'),
                'content' => $this->getResourceContent($resource),
                'permission' => $result['link']->permission,
                'download_enabled' => $result['link']->download_enabled,
            ],
        ]);
    }

    private function resolveLink(string $uuid): SecureLink
    {
        $link = SecureLink::where('uuid', $uuid)->first();

        if ($link === null) {
            abort(404, 'Link not found.');
        }

        return $link;
    }

    private function ensureNotRevokedOrExpired(SecureLink $link): void
    {
        if ($link->isRevoked()) {
            abort(410, 'This link has been revoked.');
        }

        if ($link->isExpired()) {
            abort(410, 'This link has expired.');
        }
    }

    private function getResourceContent(mixed $resource): mixed
    {
        // Return the relevant content based on resource type
        return $resource->getAttribute('content')
            ?? $resource->getAttribute('password')
            ?? $resource->getAttribute('body')
            ?? null;
    }
}
