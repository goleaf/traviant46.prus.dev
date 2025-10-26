<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSitterAssignmentRequest;
use App\Http\Resources\Api\V1\SitterAssignmentResource;
use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SitterAssignmentController extends Controller
{
    public function index(Request $request): JsonResponse|Response
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = (int) min(max($perPage, 1), 100);

        $assignmentsQuery = $request->user()
            ->sitterAssignments()
            ->with('sitter')
            ->latest('updated_at');

        $paginator = $assignmentsQuery->paginate(perPage: $perPage)->withQueryString();

        $actingAsSitter = (bool) $request->session()->get('auth.acting_as_sitter', false);
        $actingSitterId = $request->session()->get('auth.sitter_id');

        $etag = $this->etagForAssignmentsResponse(
            $paginator->getCollection(),
            $actingAsSitter,
            $actingSitterId,
            (int) $paginator->currentPage(),
            (int) $paginator->total()
        );

        $ifNoneMatch = $this->normalizeIfNoneMatchHeader($request->headers->get('If-None-Match'));

        if ($ifNoneMatch !== null && $ifNoneMatch === $etag) {
            return response()->noContent(status: 304)->setEtag($etag);
        }

        $resource = SitterAssignmentResource::collection($paginator)
            ->additional([
                'acting_as_sitter' => $actingAsSitter,
                'acting_sitter_id' => $actingSitterId,
            ]);

        $response = $resource->response();
        $lastModified = $this->lastModifiedFromCollection($paginator->getCollection());
        $response->setEtag($etag);

        if ($lastModified !== null) {
            $response->setLastModified($lastModified);
        }

        return $response;
    }

    public function store(StoreSitterAssignmentRequest $request): JsonResponse
    {
        $user = $request->user();
        $sitter = User::query()
            ->where('username', $request->validated('sitter_username'))
            ->firstOrFail();

        if ($sitter->is($user)) {
            abort(
                422,
                __('You cannot assign yourself as a sitter.')
            );
        }

        $delegation = SitterDelegation::query()->firstOrNew([
            'owner_user_id' => $user->getKey(),
            'sitter_user_id' => $sitter->getKey(),
        ]);

        $delegation->permissions = $request->validated('permissions');
        $delegation->expires_at = $this->parseExpiresAt($request->validated('expires_at'));

        if (! $delegation->exists) {
            $delegation->created_by = $user->getKey();
        }

        $delegation->updated_by = $user->getKey();
        $delegation->save();

        $status = $delegation->wasRecentlyCreated ? 201 : 200;

        return SitterAssignmentResource::make($delegation->load('sitter'))
            ->response()
            ->setStatusCode($status);
    }

    public function destroy(Request $request, SitterDelegation $sitterAssignment): Response
    {
        if ($sitterAssignment->owner_user_id !== $request->user()->getKey()) {
            throw new NotFoundHttpException();
        }

        $request->user()->sitters()->detach($sitterAssignment->sitter_user_id);

        $sitterAssignment->delete();

        return response()->noContent();
    }

    private function parseExpiresAt(?string $expiresAt): ?Carbon
    {
        if ($expiresAt === null || $expiresAt === '') {
            return null;
        }

        return Carbon::parse($expiresAt);
    }

    private function etagForAssignmentsResponse(
        iterable $assignments,
        bool $actingAsSitter,
        mixed $actingSitterId,
        int $page,
        int $total
    ): string {
        $payload = [
            'acting_as_sitter' => $actingAsSitter,
            'acting_sitter_id' => $actingSitterId,
            'page' => $page,
            'total' => $total,
        ];

        foreach ($assignments as $assignment) {
            $payload[] = [
                'id' => $assignment->getKey(),
                'sitter_id' => $assignment->sitter_user_id,
                'permissions' => $assignment->permissions,
                'expires_at' => optional($assignment->expires_at)->toIso8601String(),
                'updated_at' => optional($assignment->updated_at)->toIso8601String(),
            ];
        }

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function normalizeIfNoneMatchHeader(?string $header): ?string
    {
        if ($header === null || $header === '') {
            return null;
        }

        $header = trim($header);

        if (str_starts_with($header, 'W/')) {
            $header = substr($header, 2);
        }

        return trim($header, "\" \t\n\r\0\x0B");
    }

    private function lastModifiedFromCollection(iterable $assignments): ?Carbon
    {
        $latest = null;

        foreach ($assignments as $assignment) {
            if ($assignment->updated_at instanceof Carbon) {
                $latest = $latest === null || $assignment->updated_at->greaterThan($latest)
                    ? $assignment->updated_at
                    : $latest;
            }
        }

        return $latest;
    }
}
