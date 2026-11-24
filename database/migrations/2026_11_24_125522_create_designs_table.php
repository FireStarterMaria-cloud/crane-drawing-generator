<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designs', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->decimal('capacity_t', 5, 1);
            $t->unsignedInteger('span_mm');
            $t->unsignedInteger('lifting_height_mm');
            $t->unsignedInteger('runway_height_mm');
            $t->unsignedInteger('end_clearance_mm')->default(235);
            $t->unsignedSmallInteger('wheel_diameter_mm')->default(250);
            $t->unsignedInteger('wheelbase_mm')->nullable();      // null = auto
            $t->string('duty_group', 2)->default('A5');
            $t->unsignedSmallInteger('hoist_speed_mpm')->nullable();
            $t->unsignedSmallInteger('trolley_speed_mpm')->nullable();
            $t->unsignedSmallInteger('travel_speed_mpm')->nullable();
            $t->unsignedInteger('trolley_weight_kg')->nullable(); // null = estimate
            $t->unsignedInteger('crane_weight_kg')->nullable();   // null = estimate
            $t->unsignedSmallInteger('calc_version');
            $t->timestamps();
            $t->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designs');
    }
};