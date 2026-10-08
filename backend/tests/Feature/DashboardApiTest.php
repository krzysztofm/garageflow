<?php

namespace Tests\Feature;

use App\Models\BusinessTrip;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use DatabaseMigrations;

    private Employee $admin;
    private Employee $manager;
    private Employee $employee;
    private Employee $outsider;
    private BusinessTrip $employeeTrip;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::flush();

        $this->admin = Employee::factory()
            ->for(User::factory()->state(['role' => 'admin']))
            ->create();

        $this->manager = Employee::factory()
            ->for(User::factory()->state(['role' => 'manager']))
            ->create();

        $this->employee = Employee::factory()->create([
            'manager_id' => $this->manager->user_id,
        ]);

        $this->outsider = Employee::factory()->create();

        foreach ([$this->employee, $this->outsider] as $employee) {
            LeaveRequest::factory()->for($employee)->create();

            LeaveRequest::factory()->for($employee)->create([
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
                'status' => 'approved',
                'reviewed_by' => $this->admin->user_id,
                'reviewed_at' => now(),
            ]);
        }

        $this->employeeTrip = BusinessTrip::factory()
            ->for($this->employee)
            ->create([
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
            ]);

        BusinessTrip::factory()->for($this->outsider)->create([
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);
    }

    public function test_manager_dashboard_is_scoped_and_counts_absence_once(): void
    {
        $this->actingAs($this->manager->user, 'web')
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.counts', [
                'employees' => 2,
                'pending_leaves' => 1,
                'planned_trips' => 1,
                'absent_today' => 1,
            ])
            ->assertJsonCount(1, 'data.absent_employees')
            ->assertJsonPath(
                'data.absent_employees.0.id',
                $this->employee->id
            );
    }

    public function test_cached_results_are_separate_for_each_user(): void
    {
        $service = app(DashboardService::class);

        $employee = $service->getFor($this->employee->user);
        $manager = $service->getFor($this->manager->user);
        $admin = $service->getFor($this->admin->user);

        $this->assertSame(1, $employee['counts']['employees']);
        $this->assertSame(2, $manager['counts']['employees']);
        $this->assertSame(4, $admin['counts']['employees']);

        $this->assertSame(1, $employee['counts']['pending_leaves']);
        $this->assertSame(1, $manager['counts']['pending_leaves']);
        $this->assertSame(2, $admin['counts']['pending_leaves']);

        $this->assertSame(
            [$this->employee->id],
            array_column($employee['absent_employees'], 'id'),
        );

        $this->assertEqualsCanonicalizing(
            [$this->employee->id, $this->outsider->id],
            array_column($admin['absent_employees'], 'id'),
        );
    }

    public function test_cached_dashboard_does_not_repeat_database_queries(): void
    {
        $service = app(DashboardService::class);
        $user = $this->manager->user;

        $first = $service->getFor($user);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $second = $service->getFor($user);

        $this->assertSame($first, $second);
        $this->assertSame([], DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function test_committed_change_invalidates_cached_dashboard(): void
    {
        $service = app(DashboardService::class);
        $user = $this->manager->user;

        $this->assertSame(
            1,
            $service->getFor($user)['counts']['planned_trips'],
        );

        DB::transaction(function () {
            $this->employeeTrip->update(['status' => 'cancelled']);
        });

        $this->assertSame(
            0,
            $service->getFor($user)['counts']['planned_trips'],
        );
    }

    public function test_rollback_preserves_data_and_cache_version(): void
    {
        $service = app(DashboardService::class);
        $user = $this->manager->user;

        $before = $service->getFor($user);
        $version = Cache::get('dashboard:version');

        try {
            DB::transaction(function () use ($version) {
                $this->employeeTrip->update(['status' => 'cancelled']);

                $this->assertSame(
                    $version,
                    Cache::get('dashboard:version'),
                );

                throw new RuntimeException('Rollback test');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Rollback test', $exception->getMessage());
        }

        $this->assertSame($version, Cache::get('dashboard:version'));
        $this->assertSame($before, $service->getFor($user));

        $this->assertDatabaseHas('business_trips', [
            'id' => $this->employeeTrip->id,
            'status' => 'planned',
        ]);
    }
}