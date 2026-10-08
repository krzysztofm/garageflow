<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class EmployeeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Employee::class);

        $user = $request->user();

        $query = Employee::query()->with(['user', 'manager']);

        if (! $user->isAdmin()) {
            $query->where(function (Builder $query) use ($user) {
                $query->where('user_id', $user->id);

                if ($user->isManager()) {
                    $query->orWhere('manager_id', $user->id);
                }
            });
        }

        return EmployeeResource::collection(
            $query->orderBy('id')->paginate(15)->withQueryString()
        );
    }

    public function show(Employee $employee): EmployeeResource
    {
        Gate::authorize('view', $employee);

        return new EmployeeResource(
            $employee->load(['user', 'manager'])
        );
    }

    public function update(
        UpdateEmployeeRequest $request,
        Employee $employee
    ): EmployeeResource {
        $employee->update($request->validated());

        return new EmployeeResource(
            $employee->load(['user', 'manager'])
        );
    }
}