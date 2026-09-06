<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bulk\BulkArchiveRequest;
use App\Http\Requests\Bulk\BulkCreateRequest;
use App\Http\Requests\Bulk\BulkDeleteRequest;
use App\Http\Requests\Bulk\BulkMoveRequest;
use App\Http\Requests\Bulk\BulkShareRequest;
use App\Http\Requests\Bulk\BulkTagRequest;
use App\Models\PersonalVaultItem;
use App\Services\BulkOperationService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Bulk Operations')]
class BulkOperationController extends Controller
{
    public function __construct(
        private readonly BulkOperationService $bulkOperationService,
    ) {}

    /**
     * Bulk delete personal vault items.
     */
    public function bulkDeletePersonalVaultItems(BulkDeleteRequest $request): JsonResponse
    {
        $result = $this->bulkOperationService->bulkDelete(
            PersonalVaultItem::class,
            $request->input('ids'),
            $this->authenticatedUser($request),
        );

        return response()->json(['data' => $result]);
    }

    /**
     * Bulk move personal vault items to a folder.
     */
    public function bulkMovePersonalVaultItems(BulkMoveRequest $request): JsonResponse
    {
        $result = $this->bulkOperationService->bulkMove(
            PersonalVaultItem::class,
            $request->input('ids'),
            $request->input('folder_id'),
            $this->authenticatedUser($request),
        );

        return response()->json(['data' => $result]);
    }

    /**
     * Bulk archive personal vault items.
     */
    public function bulkArchivePersonalVaultItems(BulkArchiveRequest $request): JsonResponse
    {
        $result = $this->bulkOperationService->bulkArchive(
            PersonalVaultItem::class,
            $request->input('ids'),
            $this->authenticatedUser($request),
        );

        return response()->json(['data' => $result]);
    }

    /**
     * Bulk restore archived personal vault items.
     */
    public function bulkRestorePersonalVaultItems(BulkArchiveRequest $request): JsonResponse
    {
        $result = $this->bulkOperationService->bulkRestore(
            PersonalVaultItem::class,
            $request->input('ids'),
            $this->authenticatedUser($request),
        );

        return response()->json(['data' => $result]);
    }

    /**
     * Bulk tag personal vault items.
     */
    public function bulkTagPersonalVaultItems(BulkTagRequest $request): JsonResponse
    {
        $result = $this->bulkOperationService->bulkTag(
            PersonalVaultItem::class,
            $request->input('ids'),
            $request->input('tag_ids'),
            $request->input('action'),
            $this->authenticatedUser($request),
        );

        return response()->json(['data' => $result]);
    }

    /**
     * Bulk share personal vault items. Requires re-authentication.
     */
    public function bulkSharePersonalVaultItems(BulkShareRequest $request): JsonResponse
    {
        $result = $this->bulkOperationService->bulkShare(
            PersonalVaultItem::class,
            $request->input('ids'),
            $request->input('subject_type'),
            (int) $request->input('subject_id'),
            $request->input('permission'),
            $this->authenticatedUser($request),
            $request->input('expires_at'),
        );

        return response()->json(['data' => $result]);
    }

    /**
     * Bulk create personal vault items (max 50 per request).
     */
    public function bulkCreatePersonalVaultItems(BulkCreateRequest $request): JsonResponse
    {
        $result = $this->bulkOperationService->bulkCreatePersonalVaultItems(
            $request->input('items'),
            $this->authenticatedUser($request),
        );

        return response()->json(['data' => $result], 201);
    }
}
