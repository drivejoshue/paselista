<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolCoreModuleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        DB::table('service_modules')->upsert([
            ['code'=>'core', 'name'=>'SchoolCore', 'description'=>'Administración institucional, familias, matrículas y ciclos.', 'category'=>'core', 'is_core'=>true, 'is_public'=>false, 'sort_order'=>0, 'status'=>'active', 'created_at'=>$now, 'updated_at'=>$now],
            ['code'=>'attendance', 'name'=>'PaseLista', 'description'=>'Control de asistencia, accesos y credenciales.', 'category'=>'operations', 'is_core'=>false, 'is_public'=>true, 'sort_order'=>10, 'status'=>'active', 'created_at'=>$now, 'updated_at'=>$now],
            ['code'=>'academic', 'name'=>'Academic', 'description'=>'Materias, docentes y calificaciones.', 'category'=>'academic', 'is_core'=>false, 'is_public'=>true, 'sort_order'=>20, 'status'=>'active', 'created_at'=>$now, 'updated_at'=>$now],
            ['code'=>'enrollment_plus', 'name'=>'Inscripciones Plus', 'description'=>'Flujos avanzados de admisión y reinscripción.', 'category'=>'academic', 'is_core'=>false, 'is_public'=>true, 'sort_order'=>30, 'status'=>'coming_soon', 'created_at'=>$now, 'updated_at'=>$now],
            ['code'=>'teacher_planning', 'name'=>'Planeación docente', 'description'=>'Materiales, guías y planeaciones.', 'category'=>'academic', 'is_core'=>false, 'is_public'=>true, 'sort_order'=>40, 'status'=>'coming_soon', 'created_at'=>$now, 'updated_at'=>$now],
        ], ['code'], ['name','description','category','is_core','is_public','sort_order','status','updated_at']);
    }
}
