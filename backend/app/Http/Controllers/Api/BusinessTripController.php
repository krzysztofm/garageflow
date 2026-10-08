<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trip\StoreBusinessTripRequest;
use App\Http\Requests\Trip\UpdateBusinessTripRequest;
use App\Http\Resources\BusinessTripResource;
use App\Models\BusinessTrip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BusinessTripController extends Controller
{
    private const RELATIONS = [
        'employee.user',
        'employee.manager',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', BusinessTrip::class);

        $filters = $request->validate([
            'status' => [
                'sometimes',
                'string',
                Rule::in(['planned', 'completed', 'cancelled']),
            ],
        ]);

        $user = $request->user();

        $query = BusinessTrip::query()->with(self::RELATIONS);

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

        return BusinessTripResource::collection(
            $query->orderByDesc('id')->paginate(15)->withQueryString()
        );
    }

    public function store(StoreBusinessTripRequest $request): JsonResponse
    {
        $employee = $request->user()->employee()->firstOrFail();

        $businessTrip = $employee->businessTrips()->make(
            $request->validated()
        );

        $businessTrip->status = 'planned';
        $businessTrip->save();

        return (new BusinessTripResource(
            $businessTrip->load(self::RELATIONS)
        ))->response()->setStatusCode(201);
    }

    public function show(BusinessTrip $businessTrip): BusinessTripResource
    {
        Gate::authorize('view', $businessTrip);

        return new BusinessTripResource(
            $businessTrip->load(self::RELATIONS)
        );
    }

    public function update(
        UpdateBusinessTripRequest $request,
        BusinessTrip $businessTrip
    ): BusinessTripResource {
        $businessTrip->update($request->validated());

        return new BusinessTripResource(
            $businessTrip->load(self::RELATIONS)
        );
    }
}