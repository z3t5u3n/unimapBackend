<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tb_testers')) {
            Schema::create('tb_testers', function (Blueprint $table) {
                $table->id('id_tester');
                $table->string('username', 100)->unique();
                $table->string('nombre', 150);
                $table->string('password', 255);
                $table->string('dispositivo', 255)->nullable();
                $table->string('observaciones', 255)->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamp('ultimo_acceso')->nullable();
                $table->timestamps();
            });

            // Insertar un tester inicial de prueba
            DB::table('tb_testers')->insert([
                'username' => 'tester@Unimap',
                'nombre' => 'Tester Principal',
                'password' => Hash::make('tester1234'),
                'dispositivo' => 'Entorno de Pruebas Móvil / PC',
                'observaciones' => 'Cuenta inicial de pruebas y calibración GPS',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_testers');
    }
};
