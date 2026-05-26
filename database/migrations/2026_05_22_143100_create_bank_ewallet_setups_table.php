<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_ewallet_setups')) {
            return;
        }

        Schema::create('bank_ewallet_setups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('bank_account_name')->nullable();
            $table->string('account_type')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('esewa_wallet')->nullable();
            $table->string('khalti_wallet')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('bank_ewallet_setups')) {
            Schema::drop('bank_ewallet_setups');
        }
    }
};
