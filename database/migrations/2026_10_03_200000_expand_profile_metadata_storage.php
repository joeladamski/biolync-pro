<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->longText('image')->nullable()->change();
        });
    }

    public function down()
    {
        // Keep the wider column: shrinking it could destroy saved profile media.
    }
};
