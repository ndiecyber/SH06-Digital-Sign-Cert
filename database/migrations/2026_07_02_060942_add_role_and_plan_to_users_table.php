<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * NOTE: Kolom 'role' dan 'plan' sudah ada di migration create_users_table.php
     * Migration ini dikosongkan untuk menghindari duplikasi kolom.
     */
    public function up(): void
    {
        // Tidak ada aksi yang diperlukan
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak ada aksi yang diperlukan
    }
};