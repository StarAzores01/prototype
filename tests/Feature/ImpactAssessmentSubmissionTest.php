<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\ImpactAssessmentForm;
use App\Models\Notification;
use App\Models\Participant;
use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImpactAssessmentSubmissionTest extends TestCase
{
    private ?Connection $testConnection = null;
    private Beneficiary $beneficiary;
    private Training $training;
    private Participant $participant;
    private ImpactAssessmentForm $form;

    protected function setUp(): void
    {
        parent::setUp();

        // No database traits: every guard runs BEFORE any write/transaction.
        $this->assertTrue(app()->environment('testing'), 'Refusing database writes outside testing.');
        $this->assertFalse(app()->configurationIsCached(), 'Refusing cached database configuration.');
        $this->assertSame('pgsql', config('database.default'));
        $this->assertSame('pgsql', config('database.connections.pgsql.driver'));
        $this->assertSame('pathrive_test', config('database.connections.pgsql.database'));
        $this->assertEmpty(config('database.connections.pgsql.url'), 'DB_URL must not override the test database.');

        $connection = DB::connection();
        $this->assertSame('pathrive_test', $connection->getDatabaseName());
        $this->assertSame('pgsql', $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $this->assertSame('pathrive_test', $connection->selectOne('SELECT current_database() AS name')->name);

        $connection->beginTransaction();
        $this->testConnection = $connection;
        $this->beforeApplicationDestroyed(function () {
            if ($this->testConnection?->transactionLevel() > 0) {
                $this->testConnection->rollBack(0);
            }
        });

        $token = bin2hex(random_bytes(8));
        $user = User::create([
            'username' => 'ia_'.$token, 'email' => $token.'@example.test',
            'id_number' => $token, 'first_name' => 'Test', 'last_name' => 'Evaluator',
            'position' => 'Instructor', 'role' => 'evaluator', 'password_hash' => 'unused-test-password',
        ]);
        $this->beneficiary = Beneficiary::create([
            'username' => 'ia_'.$token, 'email' => 'beneficiary_'.$token.'@example.test',
            'first_name' => 'Test', 'last_name' => 'Participant',
            'password_hash' => 'unused-test-password', 'is_active' => true,
        ]);
        $this->training = Training::create([
            'title' => 'Submission security fixture', 'area' => 'Test', 'created_by' => $user->id,
        ]);
        $this->participant = Participant::create([
            'training_id' => $this->training->id, 'beneficiary_id' => $this->beneficiary->id,
            'full_name' => 'Test Participant', 'is_active' => true,
        ]);
        $this->form = ImpactAssessmentForm::create([
            'training_id' => $this->training->id, 'created_by' => $user->id,
            'title' => 'Test survey', 'sent_at' => now()->subMinute(),
            'fields' => [
                ['label' => 'Required text', 'type' => 'text', 'required' => true],
                ['label' => 'Optional text', 'type' => 'textarea', 'required' => false],
                ['label' => 'Required choice', 'type' => 'radio', 'required' => true, 'options' => ['Yes', 'No']],
                ['label' => 'Optional choice', 'type' => 'select', 'required' => false, 'options' => ['0', 'Other']],
            ],
        ]);
        $this->actingAs($this->beneficiary, 'beneficiary');
    }

    private function submit(array $overrides = [])
    {
        return $this->from(route('beneficiary.impact_assessment', ['form' => $this->form->id]))
            ->post(route('beneficiary.impact_assessment.store'), array_replace([
                'action' => 'submit', 'form_id' => $this->form->id,
                'answer' => [0 => 'Useful training', 2 => 'Yes'],
            ], $overrides));
    }

    private function assertNoResponse(): void
    {
        $this->assertFalse($this->form->responses()->where('beneficiary_id', $this->beneficiary->id)->exists());
    }

    public function test_valid_submission_derives_training_and_keeps_numeric_question_identifiers(): void
    {
        $this->submit(['training_id' => ['malformed', 'untrusted'], 'answer' => [0 => 'Useful', 1 => 'Details', 2 => 'No', 3 => '0']])
            ->assertRedirect(route('beneficiary.impact_assessment'))->assertSessionHas('success');

        $response = $this->form->responses()->where('beneficiary_id', $this->beneficiary->id)->sole();
        $this->assertSame($this->training->id, $response->training_id);
        $this->assertSame(['Useful', 'Details', 'No', '0'], $response->responses);
    }

    public function test_optional_questions_can_be_omitted(): void
    {
        $this->submit()->assertSessionHas('success');
        $this->assertSame([0 => 'Useful training', 2 => 'Yes'], $this->form->responses()->sole()->responses);
    }

    public function test_another_existing_training_id_cannot_change_the_response_training(): void
    {
        $otherTraining = Training::create([
            'title' => 'Unrelated activity', 'area' => 'Test', 'created_by' => $this->form->created_by,
        ]);
        $this->submit(['training_id' => $otherTraining->id])->assertSessionHas('success');
        $this->assertSame($this->training->id, $this->form->responses()->sole()->training_id);
    }

    public function test_optional_empty_answers_are_allowed(): void
    {
        $this->submit(['answer' => [0 => 'Useful', 1 => '', 2 => 'Yes', 3 => '']])->assertSessionHas('success');
        $this->assertSame(1, $this->form->responses()->count());
    }

    #[DataProvider('invalidAnswers')]
    public function test_invalid_answers_are_rejected_without_writes(mixed $answers, string $error): void
    {
        $notification = Notification::create([
            'user_id' => $this->beneficiary->id, 'role' => 'beneficiary',
            'training_id' => $this->training->id, 'message' => 'Fixture', 'is_read' => false,
        ]);
        $this->submit(['answer' => $answers])->assertSessionHasErrors($error);
        $this->assertNoResponse();
        $this->assertFalse($notification->fresh()->is_read);
    }

    public static function invalidAnswers(): array
    {
        return [
            'scalar payload' => ['bad', 'answer'],
            'null payload' => [null, 'answer'],
            'missing required text' => [[2 => 'Yes'], 'answer.0'],
            'blank required text' => [[0 => '   ', 2 => 'Yes'], 'answer.0'],
            'missing required choice' => [[0 => 'Text'], 'answer.2'],
            'invalid choice' => [[0 => 'Text', 2 => 'Maybe'], 'answer.2'],
            'invalid optional choice' => [[0 => 'Text', 2 => 'Yes', 3 => 'Missing'], 'answer.3'],
            'array answer' => [[0 => ['nested'], 2 => 'Yes'], 'answer.0'],
            'integer answer' => [[0 => 123, 2 => 'Yes'], 'answer.0'],
            'boolean answer' => [[0 => true, 2 => 'Yes'], 'answer.0'],
            'nested choice' => [[0 => 'Text', 2 => ['Yes']], 'answer.2'],
            'oversized answer' => [[0 => str_repeat('a', 5001), 2 => 'Yes'], 'answer.0'],
            'unknown index' => [[0 => 'Text', 2 => 'Yes', 9 => 'Injected'], 'answer'],
            'alternate index' => [[0 => 'Text', 2 => 'Yes', '00' => 'Injected'], 'answer'],
            'dotted index' => [[0 => 'Text', 2 => 'Yes', '0.child' => 'Injected'], 'answer'],
            'negative index' => [[0 => 'Text', 2 => 'Yes', -1 => 'Injected'], 'answer'],
        ];
    }

    #[DataProvider('unauthorizedStates')]
    public function test_unpublished_or_unauthorized_forms_are_rejected(string $state): void
    {
        match ($state) {
            'unpublished' => $this->form->update(['sent_at' => null]),
            'future' => $this->form->update(['sent_at' => now()->addDay()]),
            'inactive enrollment' => $this->participant->update(['is_active' => false]),
            'unenrolled' => $this->participant->delete(),
            'inactive beneficiary' => $this->beneficiary->update(['is_active' => false]),
        };
        $this->submit()->assertForbidden();
        $this->assertNoResponse();
    }

    public static function unauthorizedStates(): array
    {
        return array_map(fn ($state) => [$state], ['unpublished', 'future', 'inactive enrollment', 'unenrolled', 'inactive beneficiary']);
    }

    public function test_missing_form_is_rejected(): void
    {
        $this->submit(['form_id' => 0])->assertForbidden();
        $this->assertNoResponse();
    }

    public function test_unauthenticated_submission_is_rejected(): void
    {
        auth('beneficiary')->logout();
        $this->submit()->assertRedirect(route('login'));
        $this->assertNoResponse();
    }

    public function test_duplicate_submission_preserves_the_original_response(): void
    {
        $this->submit()->assertSessionHas('success');
        $original = $this->form->responses()->sole()->getAttributes();
        $this->submit(['answer' => [0 => 'Replacement', 2 => 'No']])->assertSessionHas('error');
        $this->assertSame(1, $this->form->responses()->count());
        $this->assertSame($original, $this->form->responses()->sole()->getAttributes());
    }

    #[DataProvider('invalidDefinitions')]
    public function test_invalid_stored_definition_is_rejected(array $fields): void
    {
        $this->form->update(['fields' => $fields]);
        $this->submit(['answer' => [0 => 'Text']])->assertSessionHasErrors('answer');
        $this->assertNoResponse();
    }

    public static function invalidDefinitions(): array
    {
        return [
            'no questions' => [[]],
            'unknown type' => [[['type' => 'file']]],
            'missing type' => [[['label' => 'Question']]],
            'non-object question' => [['Text']],
            'empty choices' => [[['type' => 'radio', 'options' => []]]],
            'nested choices' => [[['type' => 'select', 'options' => [['nested']]]]],
            'non-numeric question id' => [['injected' => ['type' => 'text']]],
        ];
    }

    public function test_validation_errors_and_previous_answers_are_visible(): void
    {
        $this->submit(['answer' => [0 => 'Keep this answer']])->assertSessionHasErrors('answer.2');
        // Carry the session cookie as a browser does on the redirected GET.
        $this->withCookie(session()->getName(), session()->getId())
            ->get(route('beneficiary.impact_assessment', ['form' => $this->form->id]))
            ->assertOk()->assertSee('Keep this answer')->assertSee('role="alert"', false);
    }
}
