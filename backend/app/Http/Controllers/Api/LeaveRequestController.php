<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\ReviewLeaveRequest;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LeaveRequestController extends Controller
{
    private const RELATIONS = [
        'employee.user',
        'employee.manager',
        'reviewer',
    ];

    public function __construct(
        private readonly LeaveService $leaveService
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', LeaveRequest::class);

        $filters = $request->validate([
            'status' => [
                'sometimes',
                'string',
                Rule::in(['pending', 'approved', 'rejected']),
            ],
        ]);

        $user = $request->user();

        $query = LeaveRequest::query()->with(self::RELATIONS);

        if (! $user->isAdmin()) {
            $query->whereHas('employee', function (Builder $employees) use ($user) {
                $employees->where(function (Builder $visible) use ($user) {
                    $visible->where('user_id', $user->id);

                    if ($user->isManager()) {
                        $visible->orWhere('manager_id', $user->id);
                    }
                });
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return LeaveRequestResource::collection(
            $query->orderByDesc('id')->paginate(15)->withQueryString()
        );
    }

    public function store(StoreLeaveRequest $request): JsonResponse
    {
        $leaveRequest = $this->leaveService->create(
            $request->user(),
            $request->validated()
        );

        return (new LeaveRequestResource(
            $leaveRequest->load(self::RELATIONS)
        ))->response()->setStatusCode(201);
    }

    public function show(LeaveRequest $leaveRequest): LeaveRequestResource
    {
        Gate::authorize('view', $leaveRequest);

        return new LeaveRequestResource(
            $leaveRequest->load(self::RELATIONS)
        );
    }

    public function review(
        ReviewLeaveRequest $request,
        LeaveRequest $leaveRequest
    ): LeaveRequestResource {
        $data = $request->validated();

        $reviewedRequest = $this->leaveService->review(
            $leaveRequest,
            $request->user(),
            $data['status']
        );

        return new LeaveRequestResource(
            $reviewedRequest->load(self::RELATIONS)
        );
    }
}