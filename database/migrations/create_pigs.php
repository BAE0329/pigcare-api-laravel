<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    public function up(): void
    {
        Schema::create('pigs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->integer('pigs_id');
            $table->string('pig_breed');
            $table->string('pig_name')->nullable();
            $table->double('weight',8, 2);
            $table->string('gender');
            $table->string('pig_stage');
            $table->date('date_of_birth');
            $table->date('date_of_entry');
            $table->string('pig_group');
            $table->string('pig_obtained')->nullable();
            $table->integer('tag_number')->nullable();
            $table->integer('litter_number')->nullable();
            $table->string('mothers_tag')->nullable();
            $table->string('fathers_tag')->nullable();
            $table->string('owner_of_pigs')->nullable();
            $table->string('pigs_status')->nullable();
            $table->string('status_date')->nullable();
            $table->string('pig_status')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('pigs');
    }
};