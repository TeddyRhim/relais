<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le jeton d'URL des sources n'est plus stocké en clair : il est chiffré (affichage de l'URL)
 * et retrouvé par son empreinte SHA-256. Les URL déjà distribuées restent valides.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->dropUnique(['token']);
        });

        Schema::table('sources', function (Blueprint $table) {
            $table->text('token')->change();
            $table->string('token_hash', 64)->nullable()->unique();
        });

        DB::table('sources')->orderBy('id')->each(function (object $source) {
            DB::table('sources')->where('id', $source->id)->update([
                'token_hash' => hash('sha256', $source->token),
                'token' => Crypt::encryptString($source->token),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('sources')->orderBy('id')->each(function (object $source) {
            DB::table('sources')->where('id', $source->id)->update([
                'token' => Crypt::decryptString($source->token),
            ]);
        });

        Schema::table('sources', function (Blueprint $table) {
            $table->dropUnique(['token_hash']);
            $table->dropColumn('token_hash');
        });

        Schema::table('sources', function (Blueprint $table) {
            $table->string('token', 64)->change();
            $table->unique('token');
        });
    }
};
