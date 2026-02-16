<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->string('to_email')->nullable()->after('user_agent');
            $table->string('mail_status', 20)->default('pending')->after('to_email');
            $table->timestamp('mail_sent_at')->nullable()->after('mail_status');
            $table->text('mail_error')->nullable()->after('mail_sent_at');
            $table->string('spam_reason', 50)->nullable()->after('mail_error');

            $table->index(['mail_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropIndex(['mail_status', 'created_at']);
            $table->dropColumn(['to_email', 'mail_status', 'mail_sent_at', 'mail_error', 'spam_reason']);
        });
    }
};
