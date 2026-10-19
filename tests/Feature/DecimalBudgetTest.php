<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Training;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DecimalBudgetTest extends TestCase
{
    use \Tests\Concerns\UsesDisposablePostgres;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beginDisposableDatabaseTransaction();
    }

    public static function roles(): array
    {
        return [['ec', 'extension_coordinator'], ['trainer', 'trainer']];
    }

    private function fixture(string $role): Program
    {
        $token = bin2hex(random_bytes(8));
        $user = User::create([
            'username' => $token, 'email' => $token.'@example.test', 'id_number' => $token,
            'first_name' => 'Budget', 'last_name' => 'Fixture', 'position' => 'Instructor',
            'role' => $role, 'password_hash' => 'unused', 'must_change_password' => false,
        ]);
        $program = Program::create([
            'title' => 'Budget fixture '.$token, 'area' => 'Test', 'timeline_start' => now(),
            'timeline_end' => now()->addWeek(), 'budget_allocated' => 1000, 'created_by' => $user->id,
        ]);
        $program->teamMembers()->attach($user->id, ['member_role' => 'lead']);
        $this->actingAs($user, 'web');
        return $program;
    }

    private function item(string $quantity, string $cost = '100'): array
    {
        return ['category' => 'Food', 'description' => 'Fixture', 'quantity' => $quantity, 'unit_cost' => $cost];
    }

    private function createPayload(Program $program, string $quantity, string $cost = '100'): array
    {
        return [
            'action' => 'create', 'program_id' => $program->id, 'title' => 'Decimal activity',
            'area' => 'Test', 'budget_items' => [$this->item($quantity, $cost)],
        ];
    }

    #[DataProvider('roles')]
    public function test_fractional_creation_editing_and_existing_values_survive(string $route, string $role): void
    {
        $program = $this->fixture($role);
        $this->post(route($route.'.trainings.store'), $this->createPayload($program, '1.25'))
            ->assertSessionHasNoErrors()->assertRedirect();
        $training = Training::where('program_id', $program->id)->sole();
        $this->assertSame('1.25', $training->budgetItems()->sole()->quantity);
        $this->assertSame('125.00', $training->budgetItems()->sole()->total);
        $this->assertEquals(125, $training->budget_used);
        $this->get(route($route.'.trainings', ['view' => $training->id]))
            ->assertOk()->assertSee('value="1.25"', false);

        // Re-saving an existing fractional row must not truncate the loaded value.
        $item = $this->item('1.25');
        $item['description'] = 'Updated description';
        $this->post(route($route.'.trainings.store'), [
            'action' => 'update_budget', 'training_id' => $training->id, 'items' => [$item],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('1.25', $training->budgetItems()->sole()->quantity);
        $this->assertSame('125.00', $training->budgetItems()->sole()->total);

        $this->post(route($route.'.trainings.store'), [
            'action' => 'update_budget', 'training_id' => $training->id, 'items' => [$this->item('0.5')],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('0.50', $training->budgetItems()->sole()->quantity);
        $this->assertSame('50.00', $training->budgetItems()->sole()->total);
        $this->assertEquals(50, $training->fresh()->budget_used);
    }

    public static function invalidQuantities(): array
    {
        $cases = [];
        foreach (self::roles() as [$route, $role]) {
            foreach (['0', '-1', '0.001'] as $quantity) {
                $cases[] = [$route, $role, $quantity];
            }
        }
        return $cases;
    }

    #[DataProvider('invalidQuantities')]
    public function test_creation_and_editing_reject_nonpositive_or_below_minimum_quantities(
        string $route, string $role, string $quantity
    ): void {
        $program = $this->fixture($role);
        $this->post(route($route.'.trainings.store'), $this->createPayload($program, $quantity))
            ->assertSessionHasErrors('budget_items.0.quantity');
        $this->assertFalse(Training::where('program_id', $program->id)->exists());
        $this->post(route($route.'.trainings.store'), $this->createPayload($program, '1.25'))
            ->assertSessionHasNoErrors();
        $training = Training::where('program_id', $program->id)->sole();
        $this->post(route($route.'.trainings.store'), [
            'action' => 'update_budget', 'training_id' => $training->id, 'items' => [$this->item($quantity)],
        ])->assertSessionHasErrors('items.0.quantity');
        $this->assertSame('1.25', $training->budgetItems()->sole()->quantity);
        $this->assertEquals(125, $training->fresh()->budget_used);
    }

    #[DataProvider('roles')]
    public function test_minimum_quantity_and_monetary_rounding(string $route, string $role): void
    {
        $program = $this->fixture($role);
        $this->post(route($route.'.trainings.store'), $this->createPayload($program, '0.01'))
            ->assertSessionHasNoErrors();
        $training = Training::where('program_id', $program->id)->sole();
        $this->assertSame('0.01', $training->budgetItems()->sole()->quantity);
        $this->assertSame('1.00', $training->budgetItems()->sole()->total);
        $this->post(route($route.'.trainings.store'), [
            'action' => 'update_budget', 'training_id' => $training->id,
            'items' => [$this->item('1.25', '0.05')],
        ])->assertSessionHasNoErrors();
        $this->assertSame('0.06', $training->budgetItems()->sole()->total);
        $this->assertEquals(0.06, $training->fresh()->budget_used);
    }
}
