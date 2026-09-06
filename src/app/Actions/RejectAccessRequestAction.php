<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\AccessRequestRejected;
use App\Models\AccessRequest;
use App\Models\User;
use Illuminate\Support\Carbon;

class RejectAccessRequestAction
{
    public function __invoke(User $rejecter, AccessRequest $request, ?string $reviewNote = null): AccessRequest
    {
        $request->update([
            'status' => 'rejected',
            'reviewed_by' => $rejecter->id,
            'reviewed_at' => Carbon::now(),
            'review_note' => $reviewNote,
        ]);

        $request->refresh();

        AccessRequestRejected::dispatch($request);

        return $request;
    }
}
