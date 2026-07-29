<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('attendance');
    }

    public function down(): void
    {
        // Legacy table recreation is handled by original migrations if a full rollback is required.
    }
};
