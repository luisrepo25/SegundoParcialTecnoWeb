<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;
use App\Models\Profesor;

class DocentePruebaSeeder extends Seeder
{
    public function run(): void
    {
        $emailDocente = 'docente2@ejemplo.com';

        // 1. Crear el Usuario Docente
        $userDocente = Usuario::where('email', $emailDocente)->first();
        if (!$userDocente) {
            $userDocente = Usuario::create([
                'nombre' => 'Alberto',
                'apellido' => 'Einstein',
                'email' => $emailDocente,
                'password_hash' => Hash::make('docente123'),
                'dni' => '87654322',
                'telefono' => '71122334',
                'direccion' => 'Avenida Principal 123',
                'id_rol' => 4, // Profesor
                'activo' => true,
                'bloqueado' => false,
            ]);
        }

        $profesor = Profesor::where('id_usuario', $userDocente->id_usuario)->first();
        if (!$profesor) {
            $profesor = Profesor::create([
                'id_usuario' => $userDocente->id_usuario,
                'legajo_profesor' => 'DOC-002',
                'especialidad' => 'Ciencias Exactas',
                'titulo_maximo' => 'Doctorado en Física',
                'fecha_contratacion' => '2020-01-01',
                'sueldo_base' => 6000.00
            ]);
        }

        $idProfesor = $profesor->id_profesor;

        // 2. Crear datos base (Materia, Aula, Horario, Periodo) si no existen
        $materia = DB::table('materias')->where('codigo', 'SEED-DOC1')->first();
        $idMateria = $materia ? $materia->id_materia : DB::table('materias')->insertGetId([
            'nombre' => 'Materia de Prueba Docente',
            'codigo' => 'SEED-DOC1',
            'duracion_meses' => 6,
            'costo_mensual' => 250.00,
            'creditos' => 4,
            'id_materia_requisito' => null,
            'activo' => true
        ], 'id_materia');

        $aula = DB::table('aulas')->where('nombre', 'Laboratorio A2')->first();
        $idAula = $aula ? $aula->id_aula : DB::table('aulas')->insertGetId([
            'nombre' => 'Laboratorio A2',
            'capacidad' => 30,
            'tipo' => 'aula',
            'activo' => true
        ], 'id_aula');

        $periodo = DB::table('periodos_dictado')->where('nombre', 'Semestre 2 - 2026')->first();
        $idPeriodo = $periodo ? $periodo->id_periodo : DB::table('periodos_dictado')->insertGetId([
            'nombre' => 'Semestre 2 - 2026',
            'fecha_inicio' => '2026-07-01',
            'fecha_fin' => '2026-12-30',
            'activo' => true
        ], 'id_periodo');

        // Buscar horario libre para el profesor en este período
        $horariosOcupados = DB::table('grupos')
            ->where('id_profesor', $idProfesor)
            ->where('id_periodo', $idPeriodo)
            ->pluck('id_horario');

        $horario = DB::table('horarios')
            ->whereNotIn('id_horario', $horariosOcupados)
            ->where('activo', true)
            ->first();

        if (!$horario) {
            $idHorario = DB::table('horarios')->insertGetId([
                'dia_semana' => 'sabado',
                'hora_inicio' => '14:00:00',
                'hora_fin'   => '16:00:00',
                'activo'     => true,
            ], 'id_horario');
        } else {
            $idHorario = $horario->id_horario;
        }

        // 3. Crear el Grupo asignado al profesor
        $grupo = DB::table('grupos')->where('codigo_grupo', 'SEED-G1')->where('id_periodo', $idPeriodo)->first();
        $idGrupo = $grupo ? $grupo->id_oferta : DB::table('grupos')->insertGetId([
            'id_materia' => $idMateria,
            'id_aula' => $idAula,
            'id_periodo' => $idPeriodo,
            'id_profesor' => $idProfesor,
            'id_horario' => $idHorario,
            'vacantes_max' => 30,
            'vacantes_ocupadas' => 0,
            'activo' => true,
            'codigo_grupo' => 'SEED-G1'
        ], 'id_oferta');

        // 4. Crear 3 Estudiantes de Prueba e inscribirlos al grupo
        for ($i = 10; $i <= 12; $i++) {
            $userEstudiante = Usuario::where('email', "estudiante{$i}@ejemplo.com")->first();
            
            if (!$userEstudiante) {
                $userEstudiante = Usuario::create([
                    'nombre' => 'Estudiante ' . $i,
                    'apellido' => 'Prueba',
                    'email' => "estudiante{$i}@ejemplo.com",
                    'password_hash' => Hash::make('password123'),
                    'dni' => '1000000' . $i,
                    'id_rol' => 5, // Estudiante
                    'activo' => true,
                    'bloqueado' => false,
                ]);
            }

            $estudianteDB = DB::table('estudiantes')->where('id_usuario', $userEstudiante->id_usuario)->first();
            
            if (!$estudianteDB) {
                $idEstudianteDB = DB::table('estudiantes')->insertGetId([
                    'id_usuario' => $userEstudiante->id_usuario,
                    'legajo' => 'EST-00' . $i,
                    'fecha_inscripcion_inicial' => '2026-01-01'
                ], 'id_estudiante');
                
                DB::table('afiliaciones_estudiante')->insert([
                    'id_estudiante' => $idEstudianteDB,
                    'tipo_programa' => 'curso_libre',
                    'fecha_inicio' => '2026-01-01',
                    'estado' => 'activo'
                ]);
            } else {
                $idEstudianteDB = $estudianteDB->id_estudiante;
            }

            // Pagar matrícula única (requerida por trigger antes de inscribir)
            $matricula = DB::table('matricula_unica')->where('id_estudiante', $idEstudianteDB)->first();
            if (!$matricula) {
                DB::table('matricula_unica')->insert([
                    'id_estudiante' => $idEstudianteDB,
                    'fecha_pago'    => '2026-01-01',
                    'monto_pagado'  => 500.00,
                    'estado'        => 'pagado',
                ]);
            }

            // Inscribirlos
            $inscripcion = DB::table('inscripciones')
                ->where('id_estudiante', $idEstudianteDB)
                ->where('id_oferta', $idGrupo)
                ->first();
                
            if (!$inscripcion) {
                DB::table('inscripciones')->insert([
                    'id_estudiante' => $idEstudianteDB,
                    'id_oferta' => $idGrupo,
                    'estado' => 'activo',
                    'calificacion_final' => rand(50, 100),
                    'aprobado' => null
                ]);
            }
        }
    }
}
