<?php

namespace Tests\Feature;

use App\Models\BusinessTrip;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessTripApiTest extends TestCase
{
    use RefreshDatabase;

    private Employee $manager;
    private Employee $employee;
    private Employee $outsider;

    private BusinessTrip $managerTrip;
    private BusinessTrip $employeeTrip;
    private BusinessTrip $outsideTrip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = Employee::factory()
            ->for(User::factory()->state(['role' => 'manager']))
            ->create();

        $this->employee = Employee::factory()->create([
            'manager_id' => $this->manager->user_id,
        ]);

        $this->outsider = Employee::factory()->create();

        $this->managerTrip = BusinessTrip::factory()
            ->for($this->manager)->create();

        $this->employeeTrip = BusinessTrip::factory()
            ->for($this->employee)->create();

        $this->outsideTrip = BusinessTrip::factory()
            ->for($this->outsider)->create();
    }

    public function test_employee_creates_own_planned_trip(): void
    {
        $response = $this->actingAs($this->employee->user, 'web')
            ->postJson('/api/business-trips', [
                'start_date' => now()->addDays(7)->toDateString(),
                'end_date' => now()->addDays(9)->toDateString(),
                'destination' => 'Warsaw',
                'description' => 'Technical training',
                'employee_id' => $this->outsider->id,
                'status' => 'completed',
            ])
            ->assertCreated()
            ->assertJsonPath('data.employee_id', $this->employee->id)
            ->assertJsonPath('data.status', 'planned');

        $this->assertDatabaseHas('business_trips', [
            'id' => $response->json('data.id'),
            'employee_id' => $this->employee->id,
            'destination' => 'Warsaw',
            'status' => 'planned',
        ]);
    }

    public function test_employee_sees_and_updates_only_own_trip(): void
    {
        $this->actingAs($this->employee->user, 'web');

        $this->getJson('/api/business-trips')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $this->employeeTrip->id)
            ->assertJsonPath('data.0.can_update', true);

        $this->getJson("/api/business-trips/{$this->outsideTrip->id}")
            ->assertForbidden();

        $this->patchJson("/api/business-trips/{$this->outsideTrip->id}", [
            'status' => 'cancelled',
        ])->assertForbidden();

        $this->patchJson("/api/business-trips/{$this->employeeTrip->id}", [
            'status' => 'completed',
            'employee_id' => $this->outsider->id,
        ])->assertOk();

        $this->assertDatabaseHas('business_trips', [
            'id' => $this->employeeTrip->id,
            'employee_id' => $this->employee->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('business_trips', [
            'id' => $this->outsideTrip->id,
            'status' => 'planned',
        ]);
    }

    public function test_manager_sees_and_updates_only_own_and_team_trips(): void
    {
        $this->actingAs($this->manager->user, 'web');

        $response = $this->getJson('/api/business-trips')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->assertEqualsCanonicalizing(
            [$this->managerTrip->id, $this->employeeTrip->id],
            array_column($response->json('data'), 'id'),
        );

        $this->patchJson("/api/business-trips/{$this->employeeTrip->id}", [
            'status' => 'cancelled',
        ])->assertOk();

        $this->getJson('/api/business-trips?status=cancelled')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $this->employeeTrip->id);

        $this->patchJson("/api/business-trips/{$this->outsideTrip->id}", [
            'status' => 'cancelled',
        ])->assertForbidden();

        $this->assertDatabaseHas('business_trips', [
            'id' => $this->employeeTrip->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_admin_sees_and_updates_all_trips(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'web');

        $this->getJson('/api/business-trips')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);

        $this->patchJson("/api/business-trips/{$this->outsideTrip->id}", [
            'destination' => 'Berlin',
        ])->assertOk();

        $this->assertDatabaseHas('business_trips', [
            'id' => $this->outsideTrip->id,
            'destination' => 'Berlin',
        ]);
    }

    public function test_dates_are_validated_on_create_and_update(): void
    {
        $this->actingAs($this->employee->user, 'web');

        $this->postJson('/api/business-trips', [
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->subDays(2)->toDateString(),
            'destination' => 'Warsaw',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date', 'end_date']);

        $this->assertDatabaseCount('business_trips', 3);

        $this->patchJson("/api/business-trips/{$this->employeeTrip->id}", [
            'start_date' => now()->addDays(7)->toDateString(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);

        $this->patchJson("/api/business-trips/{$this->employeeTrip->id}", [
            'start_date' => now()->addDays(9)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);

        $this->assertDatabaseHas('business_trips', [
            'id' => $this->employeeTrip->id,
            'start_date' => $this->employeeTrip->start_date->format('Y-m-d'),
            'end_date' => $this->employeeTrip->end_date->format('Y-m-d'),
        ]);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->actingAs($this->employee->user, 'web')
            ->patchJson("/api/business-trips/{$this->employeeTrip->id}", [
                'status' => 'approved',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('business_trips', [
            'id' => $this->employeeTrip->id,
            'status' => 'planned',
        ]);
    }

    public function test_guest_cannot_access_trips(): void
    {
        $this->getJson('/api/business-trips')
            ->assertUnauthorized();
    }
}