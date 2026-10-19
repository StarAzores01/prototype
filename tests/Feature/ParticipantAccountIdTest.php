<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\Participant;
use App\Models\Training;
use App\Models\User;
use Tests\TestCase;

class ParticipantAccountIdTest extends TestCase
{
    use \Tests\Concerns\UsesDisposablePostgres;

    private Beneficiary $account;
    private Training $first;
    private Training $second;
    private Training $third;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beginDisposableDatabaseTransaction();
        $token = bin2hex(random_bytes(8));
        $ec = User::create([
            'username' => $token, 'email' => $token.'@example.test', 'id_number' => $token,
            'first_name' => 'Fixture', 'last_name' => 'Staff', 'position' => 'Instructor',
            'role' => 'extension_coordinator', 'password_hash' => 'unused', 'must_change_password' => false,
        ]);
        $this->account = Beneficiary::create([
            'username' => 'b_'.$token, 'email' => 'b_'.$token.'@example.test',
            'first_name' => 'Same', 'last_name' => 'Name', 'password_hash' => 'unused', 'is_active' => true,
        ]);
        foreach (['first', 'second', 'third'] as $name) {
            $this->$name = Training::create(['title' => $name, 'area' => 'Test', 'created_by' => $ec->id]);
        }
        $this->actingAs($ec, 'web');
    }

    private function enroll(Training $training, ?string $id): Participant
    {
        return Participant::create([
            'full_name' => 'Same Name', 'beneficiary_id' => $this->account->id,
            'training_id' => $training->id, 'id_number' => $id,
        ]);
    }

    public function test_enrollment_uses_oldest_nonempty_id_and_retains_separate_rows(): void
    {
        $oldest = $this->enroll($this->first, null);
        $canonical = $this->enroll($this->second, 'BF-CANONICAL');
        $this->post(route('ec.participants.store'), [
            'action' => 'enroll_existing', 'beneficiary_id' => $this->account->id,
            'training_id' => $this->third->id,
        ])->assertSessionHas('success');
        $rows = Participant::where('beneficiary_id', $this->account->id)->get();
        $this->assertCount(3, $rows);
        $this->assertSame(['BF-CANONICAL'], $rows->pluck('id_number')->unique()->values()->all());
        $this->assertSame($this->first->id, $oldest->fresh()->training_id);
        $this->assertSame($this->second->id, $canonical->fresh()->training_id);
        $this->post(route('ec.participants.store'), [
            'action' => 'enroll_existing', 'beneficiary_id' => $this->account->id,
            'training_id' => $this->third->id,
        ])->assertSessionHas('error');
        $this->assertSame(3, Participant::where('beneficiary_id', $this->account->id)->count());
    }

    public function test_edit_keeps_canonical_id_without_merging_same_named_accounts(): void
    {
        $this->enroll($this->first, 'BF-OLDEST');
        $newer = $this->enroll($this->second, 'BF-NEWER');
        $token = bin2hex(random_bytes(8));
        $other = Beneficiary::create([
            'username' => $token, 'email' => $token.'@example.test',
            'first_name' => 'Same', 'last_name' => 'Name', 'password_hash' => 'unused',
        ]);
        $unrelated = Participant::create([
            'full_name' => 'Same Name', 'beneficiary_id' => $other->id,
            'training_id' => $this->first->id, 'id_number' => 'BF-OTHER',
        ]);
        $this->post(route('ec.participants.store'), [
            'action' => 'edit', 'participant_id' => $newer->id, 'full_name' => 'Same Name',
            'training_id' => $this->second->id, 'id_number' => 'BF-REPLACEMENT',
        ])->assertSessionHas('success');
        $this->assertSame(['BF-OLDEST'], Participant::where('beneficiary_id', $this->account->id)
            ->pluck('id_number')->unique()->values()->all());
        $this->assertSame('BF-OTHER', $unrelated->fresh()->id_number);
        $this->assertSame($other->id, $unrelated->fresh()->beneficiary_id);
        $this->get(route('ec.participants'))->assertOk()->assertViewHas('groups', fn ($groups) => $groups->filter(fn ($rows) => in_array($rows->first()->beneficiary_id, [$this->account->id, $other->id], true))->count() === 2);
    }
}
