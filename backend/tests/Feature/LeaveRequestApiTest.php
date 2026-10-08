<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;

class LeaveRequestApiTest extends TestCase
{
    use RefreshDatabase;

    private Employee $manager;
    private Employee $employee;
    private Employee $outsider;

    protected function setUp(): void
    {
        parent::setUp();
        
        Notification::fake();

        $this->manager = Employee::factory()
            ->for(User::factory()->state(['role' => 'manager']))
            ->create();

        $this->employee = Employee::factory()->create([
            'manager_id' => $this->manager->user_id,
        ]);

        $this->outsider = Employee::factory()->create();
    }

    public function test_employee_creates_own_pending_request(): void
    {
        $response = $this->actingAs($this->employee->user, 'web')
            ->postJson('/api/leave-requests', [
                'start_date' => now()->addDays(7)->toDateString(),
                'end_date' => now()->addDays(9)->toDateString(),
                'reason' => 'Holiday',
                'employee_id' => $this->outsider->id,
                'status' => 'approved',
                'reviewed_by' => $this->manager->user_id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.employee_id', $this->employee->id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.reviewed_by', null);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $response->json('data.id'),
            'employee_id' => $this->employee->id,
            'status' => 'pending',
            'reviewed_by' => null,
        ]);
    }

    public function test_invalid_dates_are_rejected(): void
    {
        $this->actingAs($this->employee->user, 'web')
            ->postJson('/api/leave-requests', [
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->subDays(2)->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date', 'end_date']);

        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_employee_sees_only_own_requests_and_cannot_review(): void
    {
        $own = LeaveRequest::factory()->for($this->employee)->create();
        $other = LeaveRequest::factory()->for($this->outsider)->create();

        $this->actingAs($this->employee->user, 'web');

        $this->getJson('/api/leave-requests')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $own->id)
            ->assertJsonPath('data.0.can_review', false);

        $this->getJson("/api/leave-requests/{$other->id}")
            ->assertForbidden();

        $this->patchJson("/api/leave-requests/{$own->id}/review", [
            'status' => 'approved',
        ])->assertForbidden();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $own->id,
            'status' => 'pending',
        ]);
    }

    public function test_manager_sees_own_and_team_requests_with_status_filter(): void
    {
        $own = LeaveRequest::factory()->for($this->manager)->create();

        $team = LeaveRequest::factory()->for($this->employee)->create([
            'status' => 'approved',
            'reviewed_by' => $this->manager->user_id,
            'reviewed_at' => now(),
        ]);

        LeaveRequest::factory()->for($this->outsider)->create();

        $this->actingAs($this->manager->user, 'web');

        $response = $this->getJson('/api/leave-requests')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->assertEqualsCanonicalizing(
            [$own->id, $team->id],
            array_column($response->json('data'), 'id'),
        );

        $this->getJson('/api/leave-requests?status=pending')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $own->id);
    }

    public function test_manager_can_approve_or_reject_with_valid_status(): void
    {
        $this->actingAs($this->manager->user, 'web');

        foreach (['approved', 'rejected'] as $status) {
            $leave = LeaveRequest::factory()->for($this->employee)->create();

            $this->patchJson("/api/leave-requests/{$leave->id}/review", [
                'status' => 'pending',
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['status']);

            $this->patchJson("/api/leave-requests/{$leave->id}/review", [
                'status' => $status,
            ])
                ->assertOk()
                ->assertJsonPath('data.status', $status)
                ->assertJsonPath('data.reviewed_by', $this->manager->user_id)
                ->assertJsonPath('data.can_review', false);

            $this->assertDatabaseHas('leave_requests', [
                'id' => $leave->id,
                'status' => $status,
                'reviewed_by' => $this->manager->user_id,
            ]);

            $this->assertNotNull($leave->fresh()->reviewed_at);
        }
    }

    public function test_manager_cannot_review_own_or_outside_team_requests(): void
    {
        $this->actingAs($this->manager->user, 'web');

        foreach ([$this->manager, $this->outsider] as $employee) {
            $leave = LeaveRequest::factory()->for($employee)->create();

            $this->patchJson("/api/leave-requests/{$leave->id}/review", [
                'status' => 'approved',
            ])->assertForbidden();

            $this->assertDatabaseHas('leave_requests', [
                'id' => $leave->id,
                'status' => 'pending',
                'reviewed_by' => null,
            ]);
        }
    }

    public function test_admin_sees_all_but_can_review_only_others_requests(): void
    {
        $admin = Employee::factory()
            ->for(User::factory()->state(['role' => 'admin']))
            ->create();

        $own = LeaveRequest::factory()->for($admin)->create();
        $other = LeaveRequest::factory()->for($this->outsider)->create();

        $this->actingAs($admin->user, 'web');

        $this->getJson('/api/leave-requests')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->patchJson("/api/leave-requests/{$own->id}/review", [
            'status' => 'approved',
        ])->assertForbidden();

        $this->patchJson("/api/leave-requests/{$other->id}/review", [
            'status' => 'approved',
        ])->assertOk();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $own->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $other->id,
            'status' => 'approved',
            'reviewed_by' => $admin->user_id,
        ]);
    }

    public function test_reviewed_request_cannot_receive_another_decision(): void
    {
        $leave = LeaveRequest::factory()->for($this->employee)->create([
            'status' => 'approved',
            'reviewed_by' => $this->manager->user_id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($this->manager->user, 'web')
            ->patchJson("/api/leave-requests/{$leave->id}/review", [
                'status' => 'rejected',
            ])
            ->assertStatus(409);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'approved',
            'reviewed_by' => $this->manager->user_id,
        ]);
    }
}