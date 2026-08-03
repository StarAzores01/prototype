<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use App\Models\Document;
use App\Models\EvaluatorWhitelist;
use App\Models\Notification;
use App\Models\SkillsUtilization;
use App\Models\TrainerWhitelist;
use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Sample data for local/dev verification only — CONTINUE.md's original SQL
 * dump wasn't available in this environment, so this seeds just enough for
 * every built EC page to render with real rows instead of empty states.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $ec = User::create([
            'username'      => 'ec.admin',
            'first_name'    => 'Maria',
            'last_name'     => 'Santos',
            'email'         => 'ec@pathrive.test',
            'id_number'     => 'EC-2026-0001',
            'position'      => 'Professor',
            'role'          => 'extension_coordinator',
            'password_hash' => Hash::make('password123'),
            'is_active'     => true,
        ]);

        $trainer = User::create([
            'username'      => 'jdelacruz',
            'first_name'    => 'Juan',
            'last_name'     => 'Dela Cruz',
            'email'         => 'trainer@pathrive.test',
            'id_number'     => 'PL-2026-0001',
            'position'      => 'Instructor',
            'role'          => 'trainer',
            'password_hash' => Hash::make('password123'),
            'is_active'     => true,
        ]);

        $evaluator = User::create([
            'username'      => 'rgarcia',
            'first_name'    => 'Rosa',
            'last_name'     => 'Garcia',
            'email'         => 'evaluator@pathrive.test',
            'id_number'     => 'EV-2026-0001',
            'position'      => 'Assistant Professor',
            'role'          => 'evaluator',
            'password_hash' => Hash::make('password123'),
            'is_active'     => true,
        ]);

        TrainerWhitelist::create([
            'first_name'     => 'Juan',
            'last_name'      => 'Dela Cruz',
            'specialization' => 'Computer Technology',
            'id_number'      => 'PL-2026-0001',
            'is_registered'  => true,
        ]);
        TrainerWhitelist::create([
            'first_name'     => 'Pedro',
            'last_name'      => 'Ramos',
            'specialization' => 'Electronics Technology',
            'id_number'      => 'PL-2026-0002',
            'is_registered'  => false,
        ]);

        EvaluatorWhitelist::create([
            'first_name'    => 'Rosa',
            'last_name'     => 'Garcia',
            'department'    => 'College of Industrial Technology',
            'id_number'     => 'EV-2026-0001',
            'is_registered' => true,
        ]);
        EvaluatorWhitelist::create([
            'first_name'    => 'Ana',
            'last_name'     => 'Reyes',
            'department'    => 'College of Engineering',
            'id_number'     => 'EV-2026-0002',
            'is_registered' => false,
        ]);

        $training = Training::create([
            'title'                => 'Basic Pastry Making',
            'area'                 => 'Culinary Technology',
            'description'          => 'Introductory pastry-making training for community beneficiaries.',
            'date_start'           => now()->addDays(7),
            'date_end'             => now()->addDays(9),
            'status'               => 'Approved',
            'trainer_id'           => $trainer->id,
            'target_participants'  => 30,
            'budget_allocated'     => 50000,
            'budget_used'          => 0,
            'created_by'           => $ec->id,
        ]);

        SkillsUtilization::create([
            'training_id'       => $training->id,
            'personal_use_pct'  => 72,
            'income_gen_pct'    => 55,
            'employment_pct'    => 38,
            'nc2_cert_pct'      => 20,
        ]);

        Document::create([
            'file_name'     => 'sample-training-plan.pdf',
            'original_name' => 'Training Plan.pdf',
            'file_type'     => 'pdf',
            'file_size'     => 102400,
            'training_id'   => $training->id,
            'uploaded_by'   => $ec->id,
            'visibility'    => 'public',
        ]);

        ContactMessage::create([
            'name'    => 'Juan Publico',
            'email'   => 'juan.publico@example.com',
            'subject' => 'Question about the Basic Pastry Making training',
            'message' => "Hi, I'd like to know if this training is open to walk-in applicants.",
            'is_read' => false,
        ]);

        Notification::create([
            'role'    => 'all',
            'message' => 'New training "Basic Pastry Making" was created.',
            'link'    => '/ec/trainings.php',
            'is_read' => false,
        ]);
    }
}
