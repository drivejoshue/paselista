<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_guardians', function (Blueprint $table): void {
            // Explicit opt-in: no silent exposure of student grades.
            $table->boolean('can_view_grades')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('student_guardians', function (Blueprint $table): void {
            $table->dropColumn('can_view_grades');
        });
    }
};
