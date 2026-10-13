<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\ImpactAssessment;
use App\Models\ImpactAssessmentForm;
use App\Models\ImpactAssessmentResponse;
use App\Models\Notification;
use App\Models\Participant;
use App\Models\Training;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EcImpactAssessmentFormCreationTest extends TestCase
{
    private User $ec;
    private User $evaluator;
    private Training $training;
    private Training $otherTraining;
    private Beneficiary $beneficiary;
    private ImpactAssessment $assessment;
    private int $formCount;
    private int $notificationCount;

    protected function setUp(): void
    {
        parent::setUp();

        // No database reset traits. Verify the effective connection before
        // starting any transaction or writing any fixture.
        $this->assertTrue(app()->environment('testing'));
        $this->assertFalse(app()->configurationIsCached());
        $this->assertSame('pgsql', config('database.default'));
        $this->assertSame('pgsql', config('database.connections.pgsql.driver'));
        $this->assertSame('pathrive_test', config('database.connections.pgsql.database'));
        $this->assertEmpty(config('database.connections.pgsql.url'));
        $connection = DB::connection();
        $this->assertSame('pathrive_test', $connection->getDatabaseName());
        $this->assertSame('pgsql', $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $this->assertSame('pathrive_test', $connection->selectOne('SELECT current_database() AS name')->name);
        $connection->beginTransaction();
        $this->beforeApplicationDestroyed(function () use ($connection) {
            if ($connection->transactionLevel() > 0) {
                $connection->rollBack(0);
            }
        });

        Storage::fake('local');
        $token = bin2hex(random_bytes(8));
        $this->evaluator = $this->staff('evaluator', $token);
        $this->ec = $this->staff('extension_coordinator', 'ec_'.$token);
        $this->training = Training::create([
            'title' => 'Linked activity '.$token, 'area' => 'Test', 'created_by' => $this->ec->id,
        ]);
        $this->otherTraining = Training::create([
            'title' => 'Other activity '.$token, 'area' => 'Test', 'created_by' => $this->ec->id,
        ]);
        $this->beneficiary = Beneficiary::create([
            'username' => 'bf_'.$token, 'first_name' => 'Test', 'last_name' => 'Participant',
            'email' => 'bf_'.$token.'@example.test', 'password_hash' => 'unused-test-password',
        ]);
        Participant::create([
            'full_name' => 'Test Participant', 'training_id' => $this->training->id,
            'beneficiary_id' => $this->beneficiary->id, 'is_active' => true,
        ]);
        $this->assessment = ImpactAssessment::create([
            'evaluator_id' => $this->evaluator->id, 'training_id' => $this->training->id,
            'title' => 'Uploaded assessment', 'description' => 'Keep these notes',
            'status' => 'Submitted', 'submitted_at' => now()->subDay(),
            'due_date' => now()->addWeek()->toDateString(), 'file_name' => 'ia_test.pdf',
            'original_name' => 'Original assessment.pdf', 'file_type' => 'pdf', 'file_size' => 12,
        ]);
        Storage::disk('local')->put('uploads/ia_test.pdf', 'Fixture file');
        $this->formCount = ImpactAssessmentForm::count();
        $this->notificationCount = Notification::count();
        $this->actingAs($this->ec, 'web');
    }

    private function staff(string $role, string $token): User
    {
        return User::create([
            'username' => $token, 'first_name' => 'Test', 'last_name' => 'Staff',
            'email' => $token.'@example.test', 'id_number' => $token,
            'position' => 'Instructor', 'role' => $role,
            'password_hash' => 'unused-test-password', 'must_change_password' => false,
        ]);
    }

    private function createForm(array $overrides = [])
    {
        return $this->from(route('ec.impact_assessment'))
            ->post(route('ec.impact_assessment.store'), array_replace([
                'action' => 'create_form', 'assessment_id' => $this->assessment->id,
                'form_title' => 'Participant survey', 'field_label' => ['Impact?', 'Choice?'],
                'field_type' => ['text', 'select'], 'field_required' => [0 => '1'],
                'field_options' => [1 => "0\nOther"],
            ], $overrides));
    }

    private function assertNoCreation(): void
    {
        $this->assertSame($this->formCount, ImpactAssessmentForm::count());
        $this->assertSame($this->notificationCount, Notification::count());
        $this->assertNull($this->assessment->fresh()->participant_form_id);
    }

    public function test_linked_creation_needs_no_posted_training_and_preserves_assessment_and_file(): void
    {
        $original = $this->assessment->fresh()->getAttributes();
        $this->createForm(['due_date' => now()->addMonth()->toDateString()])
            ->assertRedirect(route('ec.impact_assessment', ['tab' => 'surveys']))->assertSessionHas('success');
        $updated = $this->assessment->fresh();
        $form = $updated->participantForm;
        $this->assertNotNull($form);
        $this->assertSame($this->training->id, $form->training_id);
        $this->assertSame($this->ec->id, $form->created_by);
        $this->assertSame($this->assessment->due_date->toDateString(), $form->due_date->toDateString());
        $this->assertNotNull($form->sent_at);
        $this->assertSame(['0', 'Other'], $form->fields[1]['options']);
        $this->assertTrue($form->fields[0]['required']);
        $this->assertFalse($form->fields[1]['required']);
        $this->assertSame('Fixture file', Storage::disk('local')->get('uploads/ia_test.pdf'));
        foreach ($original as $key => $value) {
            if (! in_array($key, ['participant_form_id', 'updated_at'], true)) {
                $this->assertSame($value, $updated->getAttributes()[$key], $key.' must remain unchanged.');
            }
        }
        $this->assertSame($this->formCount + 1, ImpactAssessmentForm::count());
        $this->assertSame($this->notificationCount + 1, Notification::count());
        $this->assertTrue(Notification::where('role', 'beneficiary')->where('user_id', $this->beneficiary->id)
            ->where('training_id', $this->training->id)->exists());
    }

    public function test_matching_posted_training_is_compatible(): void
    {
        $this->createForm(['training_id' => $this->training->id])->assertSessionHas('success');
        $this->assertSame($this->training->id, $this->assessment->fresh()->participantForm->training_id);
    }

    public function test_cross_training_request_is_rejected(): void
    {
        $this->createForm(['training_id' => $this->otherTraining->id])->assertSessionHasErrors('training_id');
        $this->assertNoCreation();
    }

    #[DataProvider('ineligibleStates')]
    public function test_ineligible_assessment_is_rejected(string $state): void
    {
        match ($state) {
            'built form' => $this->assessment->update(['form_data' => [['label' => 'Question', 'type' => 'text']]]),
            'empty built form' => $this->assessment->update(['form_data' => []]),
            'no upload' => $this->assessment->update(['file_name' => null]),
            'missing file' => Storage::disk('local')->delete('uploads/ia_test.pdf'),
            'draft' => $this->assessment->update(['status' => 'Draft']),
            'no activity' => $this->assessment->update(['training_id' => null]),
        };
        $this->createForm()->assertSessionHasErrors('assessment_id');
        $this->assertNoCreation();
    }

    public static function ineligibleStates(): array
    {
        return array_map(fn ($state) => [$state], ['built form', 'empty built form', 'no upload', 'missing file', 'draft', 'no activity']);
    }

    public function test_reviewed_upload_is_eligible(): void
    {
        $this->assessment->update(['status' => 'Reviewed', 'ec_notes' => 'Keep feedback', 'reviewed_at' => now()]);
        $this->createForm()->assertSessionHas('success');
        $this->assertSame('Reviewed', $this->assessment->fresh()->status);
        $this->assertSame('Keep feedback', $this->assessment->fresh()->ec_notes);
    }

    public function test_nonexistent_assessment_is_not_treated_as_standalone(): void
    {
        $this->createForm(['assessment_id' => 0, 'training_id' => $this->training->id])->assertSessionHasErrors('assessment_id');
        $this->assertNoCreation();
    }

    public function test_repeated_creation_does_not_duplicate_or_overwrite_existing_form_and_response(): void
    {
        $this->createForm()->assertSessionHas('success');
        $linkedId = $this->assessment->fresh()->participant_form_id;
        $form = ImpactAssessmentForm::findOrFail($linkedId);
        $formAttributes = $form->getAttributes();
        $response = ImpactAssessmentResponse::create([
            'form_id' => $linkedId, 'training_id' => $this->training->id,
            'beneficiary_id' => $this->beneficiary->id, 'responses' => ['Original response'],
        ]);
        $originalResponse = $response->fresh()->getAttributes();
        $this->createForm(['form_title' => 'Replacement'])->assertSessionHasErrors('assessment_id');
        $this->assertSame($linkedId, $this->assessment->fresh()->participant_form_id);
        $this->assertSame($formAttributes, $form->fresh()->getAttributes());
        $this->assertSame($originalResponse, $response->fresh()->getAttributes());
        $this->assertSame($this->formCount + 1, ImpactAssessmentForm::count());
        $this->assertSame($this->notificationCount + 1, Notification::count());
    }

    public function test_stale_link_is_not_overwritten(): void
    {
        $this->assessment->update(['participant_form_id' => 2147483647]);
        $this->createForm()->assertSessionHasErrors('assessment_id');
        $this->assertSame(2147483647, $this->assessment->fresh()->participant_form_id);
        $this->assertSame($this->formCount, ImpactAssessmentForm::count());
    }

    #[DataProvider('invalidQuestions')]
    public function test_invalid_questions_are_rejected(array $payload, string $error): void
    {
        $this->createForm($payload)->assertSessionHasErrors($error);
        $this->assertNoCreation();
    }

    public static function invalidQuestions(): array
    {
        return [
            'no questions' => [['field_label' => []], 'field_label'],
            'blank question' => [['field_label' => ['   ']], 'field_label.0'],
            'nested label' => [['field_label' => [['nested']]], 'field_label.0'],
            'unsupported type' => [['field_type' => ['file', 'select']], 'field_type.0'],
            'nested type' => [['field_type' => [['text'], 'select']], 'field_type.0'],
            'nested options' => [['field_options' => [1 => ['nested']]], 'field_options.1'],
            'empty choices' => [['field_options' => [1 => "\n  "]], 'field_options.1'],
        ];
    }

    public function test_standalone_creation_still_requires_and_uses_selected_training(): void
    {
        $this->createForm(['assessment_id' => null])->assertSessionHasErrors('training_id');
        $this->assertNoCreation();
        $this->createForm(['assessment_id' => null, 'training_id' => $this->otherTraining->id])->assertSessionHas('success');
        $this->assertNull($this->assessment->fresh()->participant_form_id);
        $this->assertTrue(ImpactAssessmentForm::where('training_id', $this->otherTraining->id)->where('title', 'Participant survey')->exists());
    }

    public function test_non_ec_cannot_generate_forms(): void
    {
        $this->actingAs($this->evaluator, 'web');
        $this->createForm()->assertForbidden();
        $this->assertNoCreation();
    }

    public function test_notification_failure_rolls_back_form_and_assessment_link(): void
    {
        Event::listen('eloquent.creating: '.Notification::class, function () {
            throw new \RuntimeException('Simulated notification failure');
        });
        $this->withoutExceptionHandling();
        try {
            $this->createForm();
            $this->fail('The simulated failure must be raised.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated notification failure', $exception->getMessage());
        }
        $this->assertNoCreation();
    }

    public function test_evaluator_tab_contains_linked_activity_and_safe_modal_data(): void
    {
        $this->assessment->update(['title' => 'Upload "quoted" <script>title</script>']);
        $this->get(route('ec.impact_assessment'))->assertOk()
            ->assertSee('value="'.$this->training->id.'">'.$this->training->title.'</option>', false)
            ->assertSee('data-training-id="'.$this->training->id.'"', false)
            ->assertSee('data-title="Upload &quot;quoted&quot; &lt;script&gt;title&lt;/script&gt;"', false);
    }

    public function test_failed_creation_displays_errors_and_restores_modal_context(): void
    {
        $this->createForm(['field_options' => [1 => '']])->assertSessionHasErrors('field_options.1');
        $this->withCookie(session()->getName(), session()->getId())
            ->get(route('ec.impact_assessment'))->assertOk()
            ->assertSee('role="alert"', false)->assertSee('Please add at least one choice')
            ->assertViewHas('creationAssessment', fn ($assessment) => $assessment->id === $this->assessment->id)
            ->assertSee('Participant survey')->assertSee('Impact?');
        $this->assertNoCreation();
    }
}
