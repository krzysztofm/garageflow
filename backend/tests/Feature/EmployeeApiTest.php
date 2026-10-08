<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    private Employee $admin;
    private Employee $manager;
    private Employee $employee;
    private Employee $outsider;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_employee_sees_only_own_profile(): void
    {
        $this->assertVisibleProfiles(
            $this->employee->user,
            [$this->employee->id],
        );
    }

    public function test_manager_sees_own_profile_and_team(): void
    {
        $this->assertVisibleProfiles(
            $this->manager->user,
            [$this->manager->id, $this->employee->id],
        );
    }

    public function test_admin_sees_all_profiles(): void
    {
        $this->assertVisibleProfiles(
            $this->admin->user,
            [
                $this->admin->id,
                $this->manager->id,
                $this->employee->id,
                $this->outsider->id,
            ],
        );
    }

    public function test_employee_cannot_view_others_or_edit_own_profile(): void
    {
        $this->actingAs($this->employee->user, 'web');

        $this->getJson("/api/employees/{$this->outsider->id}")
            ->assertForbidden();

        $this->patchJson("/api/employees/{$this->employee->id}", [
            'position' => 'Workshop Manager',
        ])->assertForbidden();

        $this->assertDatabaseHas('employees', [
            'id' => $this->employee->id,
            'position' => $this->employee->position,
        ]);
    }

    public function test_manager_cannot_access_employee_outside_team(): void
    {
        $this->actingAs($this->manager->user, 'web');

        $this->getJson("/api/employees/{$this->outsider->id}")
            ->assertForbidden();

        $this->patchJson("/api/employees/{$this->outsider->id}", [
            'department' => 'Office',
        ])->assertForbidden();

        $this->assertDatabaseHas('employees', [
            'id' => $this->outsider->id,
            'department' => $this->outsider->department,
        ]);
    }

    public function test_manager_can_update_team_profile_with_valid_data(): void
    {
        $this->actingAs($this->manager->user, 'web');

        $this->patchJson("/api/employees/{$this->employee->id}", [
            'position' => 'Senior Mechanic',
        ])
            ->assertOk()
            ->assertJsonPath('data.position', 'Senior Mechanic');

        $this->assertDatabaseHas('employees', [
            'id' => $this->employee->id,
            'position' => 'Senior Mechanic',
        ]);

        $this->patchJson("/api/employees/{$this->employee->id}", [
            'position' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['position']);

        $this->assertDatabaseHas('employees', [
            'id' => $this->employee->id,
            'position' => 'Senior Mechanic',
        ]);
    }

    public function test_admin_can_update_any_profile(): void
    {
        $this->actingAs($this->admin->user, 'web');

        $this->patchJson("/api/employees/{$this->outsider->id}", [
            'department' => 'Office',
        ])->assertOk();

        $this->assertDatabaseHas('employees', [
            'id' => $this->outsider->id,
            'department' => 'Office',
        ]);
    }

    private function assertVisibleProfiles(User $user, array $ids): void
    {
        $response = $this->actingAs($user, 'web')
            ->getJson('/api/employees')
            ->assertOk()
            ->assertJsonPath('meta.total', count($ids));

        $this->assertEqualsCanonicalizing(
            $ids,
            array_column($response->json('data'), 'id'),
        );
    }
}