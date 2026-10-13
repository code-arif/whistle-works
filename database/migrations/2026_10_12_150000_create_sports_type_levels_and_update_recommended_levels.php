<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create master sports_type_levels table
        if (!Schema::hasTable('sports_type_levels')) {
            Schema::create('sports_type_levels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sports_type_id')->constrained('sports_types')->cascadeOnDelete();
                $table->string('name', 191);
                $table->unsignedInteger('sort_order')->default(0);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();

                $table->index(['sports_type_id', 'status']);
                $table->index(['sports_type_id', 'sort_order']);
            });
        }

        // 2. Modify recommended_levels table:
        // Change enum 'level' to VARCHAR(191) to support any dynamic sport level string safely
        if (Schema::hasTable('recommended_levels')) {
            try {
                DB::statement("ALTER TABLE `recommended_levels` MODIFY `level` VARCHAR(191) NOT NULL");
            } catch (\Throwable $e) {
                // Fallback for drivers that don't support raw ALTER TABLE (e.g. SQLite in unit tests)
                Schema::table('recommended_levels', function (Blueprint $table) {
                    $table->string('level', 191)->change();
                });
            }

            // Add nullable foreign key referencing sports_type_levels
            if (!Schema::hasColumn('recommended_levels', 'sports_type_level_id')) {
                Schema::table('recommended_levels', function (Blueprint $table) {
                    $table->foreignId('sports_type_level_id')
                        ->nullable()
                        ->after('evaluation_id')
                        ->constrained('sports_type_levels')
                        ->nullOnDelete();
                });
            }
        }

        // 3. Seed baseline evaluation levels for existing sports types so no sport is empty
        $this->seedInitialSportLevels();

        // 4. Backfill existing recommended_levels linking sports_type_level_id safely
        $this->backfillExistingRecommendedLevels();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('recommended_levels')) {
            Schema::table('recommended_levels', function (Blueprint $table) {
                if (Schema::hasColumn('recommended_levels', 'sports_type_level_id')) {
                    $table->dropForeign(['sports_type_level_id']);
                    $table->dropColumn('sports_type_level_id');
                }
            });
        }

        Schema::dropIfExists('sports_type_levels');
    }

    /**
     * Seed baseline levels for existing sports types.
     */
    protected function seedInitialSportLevels(): void
    {
        if (!Schema::hasTable('sports_types') || !Schema::hasTable('sports_type_levels')) {
            return;
        }

        $sports = DB::table('sports_types')->get();

        foreach ($sports as $sport) {
            // Check if levels already exist
            $exists = DB::table('sports_type_levels')->where('sports_type_id', $sport->id)->exists();
            if ($exists) {
                continue;
            }

            $isHsBasketball = stripos($sport->sports_name, 'HS Basketball') !== false;

            if ($isHsBasketball) {
                $levels = [
                    ['name' => 'Large School Varsity', 'sort_order' => 1],
                    ['name' => 'Mid Size School Varsity', 'sort_order' => 2],
                    ['name' => 'Small School Varsity', 'sort_order' => 3],
                    ['name' => 'Junior Varsity', 'sort_order' => 4],
                    ['name' => 'JH/ELEM', 'sort_order' => 5],
                ];
            } else {
                $levels = [
                    ['name' => 'NCAA D1', 'sort_order' => 1],
                    ['name' => 'NCAA D2', 'sort_order' => 2],
                    ['name' => 'NAIA', 'sort_order' => 3],
                    ['name' => 'JUCO', 'sort_order' => 4],
                    ['name' => 'HS', 'sort_order' => 5],
                    ['name' => 'JH/ELEM', 'sort_order' => 6],
                ];
            }

            $now = now();
            $records = array_map(function ($lvl) use ($sport, $now) {
                return [
                    'sports_type_id' => $sport->id,
                    'name'           => $lvl['name'],
                    'sort_order'     => $lvl['sort_order'],
                    'status'         => 'active',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }, $levels);

            DB::table('sports_type_levels')->insert($records);
        }
    }

    /**
     * Backfill existing evaluation levels linking sports_type_level_id.
     */
    protected function backfillExistingRecommendedLevels(): void
    {
        if (!Schema::hasTable('recommended_levels') || !Schema::hasTable('sports_type_levels')) {
            return;
        }

        try {
            DB::statement("
                UPDATE `recommended_levels` rl
                JOIN `referee_evaluations` re ON re.id = rl.evaluation_id
                JOIN `camps` c ON c.id = re.camp_id
                JOIN `sports_type_levels` stl ON stl.sports_type_id = c.sports_type_id AND stl.name = rl.level
                SET rl.sports_type_level_id = stl.id
                WHERE rl.sports_type_level_id IS NULL
            ");
        } catch (\Throwable $e) {
            // Log or ignore in testing environments without MySQL join updates
        }
    }
};
