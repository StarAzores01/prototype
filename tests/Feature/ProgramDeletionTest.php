<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\Document;
use App\Models\Evaluation;
use App\Models\ImpactAssessment;
use App\Models\ImpactAssessmentForm;
use App\Models\ImpactAssessmentResponse;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Training;
use App\Models\TrainingDoc;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProgramDeletionTest extends TestCase
{
    use \Tests\Concerns\UsesDisposablePostgres;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beginDisposableDatabaseTransaction();
    }

    private function staff(string $role): User
    {
        $token = bin2hex(random_bytes(8));
        return User::create([
            'username' => $token, 'email' => $token.'@example.test', 'id_number' => $token,
            'first_name' => 'Fixture', 'last_name' => 'Staff', 'position' => 'Instructor',
            'role' => $role, 'password_hash' => 'unused', 'must_change_password' => false,
        ]);
    }

    #[DataProvider('deletionRoles')]
    public function test_program_deletion_preserves_activity_and_all_child_records(string $route, string $role): void
    {
        $user = $this->staff($role);
        $evaluator = $this->staff('evaluator');
        $program = Program::create([
            'title' => 'Program fixture', 'area' => 'Test', 'timeline_start' => now(),
            'timeline_end' => now()->addWeek(), 'budget_allocated' => 100, 'created_by' => $user->id,
        ]);
        if ($role === 'trainer') {
            $program->teamMembers()->attach($user->id, ['member_role' => 'lead']);
        }
        $training = Training::create([
            'title' => 'Activity fixture', 'area' => 'Test',
            'program_id' => $program->id, 'created_by' => $user->id,
        ]);
        $token = bin2hex(random_bytes(8));
        $beneficiary = Beneficiary::create([
            'username' => $token, 'email' => $token.'@example.test', 'first_name' => 'Fixture',
            'last_name' => 'Beneficiary', 'password_hash' => 'unused',
        ]);
        $participant = Participant::create([
            'full_name' => 'Fixture Beneficiary', 'training_id' => $training->id,
            'beneficiary_id' => $beneficiary->id,
        ]);
        $attendance = Attendance::create([
            'training_id' => $training->id, 'participant_id' => $participant->id,
            'session_date' => now(), 'status' => 'Present', 'recorded_by' => $user->id,
        ]);
        $evaluation = Evaluation::create([
            'training_id' => $training->id, 'participant_id' => $participant->id, 'status' => 'Pending',
        ]);
        $assessment = ImpactAssessment::create([
            'training_id' => $training->id, 'evaluator_id' => $evaluator->id, 'title' => 'Assessment',
        ]);
        $form = ImpactAssessmentForm::create([
            'training_id' => $training->id, 'created_by' => $user->id, 'title' => 'Survey',
            'sent_at' => now(), 'fields' => [['label' => 'Impact', 'type' => 'text', 'required' => false]],
        ]);
        $response = ImpactAssessmentResponse::create([
            'form_id' => $form->id, 'training_id' => $training->id,
            'beneficiary_id' => $beneficiary->id, 'responses' => ['Useful'],
        ]);
        $document = Document::create([
            'training_id' => $training->id, 'activity_id' => $training->id, 'program_id' => $program->id,
            'uploaded_by' => $user->id, 'file_name' => 'fixture.pdf',
            'original_name' => 'fixture.pdf', 'file_type' => 'pdf', 'file_size' => 10,
        ]);
        $trainingDoc = TrainingDoc::create([
            'training_id' => $training->id, 'uploaded_by' => $user->id,
            'file_name' => 'photo.png', 'file_type' => 'png',
        ]);
        $children = [$participant, $attendance, $evaluation, $assessment, $form, $response, $trainingDoc];
        $before = array_map(fn ($model) => $model->fresh()->getAttributes(), $children);
        $this->actingAs($user, 'web')->post(route($route.'.programs.store'), [
            'action' => 'delete', 'program_id' => $program->id,
        ])->assertSessionHas('success');

        $this->assertNull($program->fresh());
        $this->assertNull($training->fresh()->program_id);
        foreach ($children as $index => $model) {
            $this->assertSame($before[$index], $model->fresh()?->getAttributes());
        }
        $this->assertSame($training->id, $document->fresh()->activity_id);
        $this->assertNull($document->fresh()->program_id);
        $this->assertSame('fixture.pdf', $document->fresh()->file_name);
    }

    public static function deletionRoles(): array
    {
        return [['ec', 'extension_coordinator'], ['trainer', 'trainer']];
    }

    public function test_trainer_member_cannot_delete_program(): void
    {
        $user = $this->staff('trainer');
        $program = Program::create([
            'title' => 'Protected program', 'area' => 'Test', 'timeline_start' => now(),
            'timeline_end' => now()->addWeek(), 'budget_allocated' => 100, 'created_by' => $user->id,
        ]);
        $program->teamMembers()->attach($user->id, ['member_role' => 'member']);
        $this->actingAs($user, 'web')->post(route('trainer.programs.store'), [
            'action' => 'delete', 'program_id' => $program->id,
        ])->assertForbidden();
        $this->assertNotNull($program->fresh());
    }
}
