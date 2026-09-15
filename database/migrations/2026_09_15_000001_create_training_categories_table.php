<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the old 'training-categories' PageContent pseudo-page (image-only,
 * keyed by a hardcoded PHP array in every consuming Blade view) with a real,
 * EC-manageable table — see App\Models\TrainingCategory and
 * Ec\TrainingCategoryController ("Trainings" tab under Manage Public Site
 * Content). Feeds the landing page's Courses Offered cards and the public
 * Trainings page's category photos, same two consumers as before.
 *
 * `activity_name` is the category itself (e.g. "Culinary Technology" — what
 * was previously called "Category Name"). `project_name` is a new field: a
 * representative specific project/training under that category (e.g. "Basic
 * Pastry Making"), free text, EC-entered. There is deliberately no stored
 * "program" column — the admin table's "Program Name" column is always
 * looked up live via TrainingCategory::programName() against the real
 * Program.area column, so it reflects actual Program data automatically
 * instead of a number an EC would otherwise have to keep in sync by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_categories', function (Blueprint $table) {
            $table->id();
            $table->string('activity_name', 100)->unique();
            $table->string('project_name', 150)->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->enum('status', ['active', 'draft'])->default('draft');
            $table->timestamps();
        });

        // Seed the 8 categories every public page already hardcodes today,
        // carrying over their current descriptions (from landing.blade.php)
        // as a starting point EC can edit, and copying any image already
        // uploaded under the old PageContent keys so nothing already
        // uploaded by EC is lost in the switch. All 8 start 'active' since
        // they're already live on the public site today.
        $imageKeyByArea = [
            'Culinary Technology'            => 'culinary_image',
            'Computer Technology'            => 'computer_image',
            'Automotive Technology'          => 'automotive_image',
            'Electronics Technology'         => 'electronics_image',
            'Apparel and Fashion Technology' => 'apparel_image',
            'Mechanical Technology'          => 'mechanical_image',
            'Print Media Technology'         => 'printmedia_image',
            'Information Technology'         => 'it_image',
        ];

        $descriptions = [
            'Culinary Technology'            => 'Professional food preparation, bakery, pastry arts, and kitchen management for employment or entrepreneurship.',
            'Computer Technology'            => 'Digital literacy, basic programming, office productivity tools, and computer hardware servicing.',
            'Automotive Technology'          => 'Engine diagnostics, brake systems, preventive maintenance, and electrical repair for vehicles.',
            'Electronics Technology'         => 'Solar panel installation, consumer electronics repair, and electrical wiring for homes and small businesses.',
            'Apparel and Fashion Technology' => 'Dressmaking, pattern-making, garment construction, and fashion design for local industry.',
            'Mechanical Technology'          => 'Machining, welding, metal fabrication, and mechanical systems maintenance for industrial applications.',
            'Print Media Technology'         => 'Graphic design, desktop publishing, digital printing, and print production for media and business.',
            'Information Technology'         => 'Web development, systems analysis, network fundamentals, and IT support for the digital economy.',
        ];

        $now = now();
        $rows = [];
        foreach ($imageKeyByArea as $area => $imageKey) {
            $image = DB::table('page_contents')
                ->where('page_key', 'training-categories')
                ->where('section_key', $imageKey)
                ->value('content_value');

            $rows[] = [
                'activity_name' => $area,
                'project_name'  => null,
                'description'   => $descriptions[$area],
                'image'         => $image ?: null,
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        DB::table('training_categories')->insert($rows);

        // The old image rows are now fully superseded by the columns above.
        DB::table('page_contents')->where('page_key', 'training-categories')->delete();
    }

    public function down(): void
    {
        Schema::dropIfExists('training_categories');
    }
};
