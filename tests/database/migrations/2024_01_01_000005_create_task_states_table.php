<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_states', function (Blueprint $table) {
            $table->foreignId('task_id')->primary();
            $table->string('state_value');
        });
    }
};
