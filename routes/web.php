<?php

use App\Http\Controllers\Propietario\CU1Usuarios\UsuarioController;
use App\Http\Controllers\Propietario\CU2Aulas\AulaController;
use App\Http\Controllers\Propietario\CU11Horarios\HorarioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Estudiante\PerfilController;
use App\Models\Aula;
use App\Models\Usuario;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

// ── Oferta académica pública (sin autenticación) ───────────────────────────
Route::prefix('oferta')->name('oferta.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Public\OfertaController::class, 'index'])->name('index');
    // /pago/* ANTES de /{id} para evitar colisión de rutas
    Route::get('/pago/{transId}/estado', [\App\Http\Controllers\Public\OfertaController::class, 'estado'])->name('estado');
    Route::get('/pago/{transId}', [\App\Http\Controllers\Public\OfertaController::class, 'pago'])->name('pago');
    Route::get('/{id}', [\App\Http\Controllers\Public\OfertaController::class, 'show'])->where('id', '[0-9]+')->name('show');
    Route::get('/{id}/inscribirse', [\App\Http\Controllers\Public\OfertaController::class, 'formulario'])->where('id', '[0-9]+')->name('formulario');
    Route::post('/{id}/inscribirse', [\App\Http\Controllers\Public\OfertaController::class, 'registrar'])->where('id', '[0-9]+')->name('registrar');
});

// Callback PagoFácil (sin CSRF — excluido en bootstrap/app.php)
Route::post('/pagofacil/callback', [\App\Http\Controllers\Public\CallbackController::class, 'handle'])->name('pagofacil.callback');


Route::get('/dashboard', function () {
    $user = auth()->user();

    return match ($user?->role) {
        'propietario' => Inertia::render('Dashboard/Propietario'),
        'director'    => Inertia::render('Dashboard/Director'),
        'secretaria'  => Inertia::render('Dashboard/Secretaria'),
        'profesor'    => Inertia::render('Dashboard/Docente'),
        default       => Inertia::render('Dashboard/Estudiante'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {

    // ── Panel Propietario ──────────────────────────────────────────────────────
    Route::get('/panel/propietario', function () {
        return Inertia::render('Dashboard/Propietario', [
            'nombre' => auth()->user()->nombre ?? '',
            'stats'  => [
                'total_usuarios'   => Usuario::count(),
                'usuarios_activos' => Usuario::whereRaw('activo IS TRUE')->count(),
                'total_aulas'      => Aula::count(),
                'total_carreras'   => \App\Models\Carrera::count(),
                'total_materias'   => \App\Models\Materia::count(),
                'total_horarios'   => \App\Models\Horario::count(),
            ],
        ]);
    })->middleware('role:propietario')->name('dashboard.propietario');

    // CU1 — Gestión de Usuarios (propietario, director, secretaria)
    Route::middleware('role:propietario,director,secretaria')->prefix('propietario')->name('propietario.')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::patch('/usuarios/{id}/toggle-activo', [UsuarioController::class, 'toggleActivo'])->name('usuarios.toggle-activo');
        Route::patch('/usuarios/{id}/password', [UsuarioController::class, 'cambiarPassword'])->name('usuarios.password');
    });

    // CU13 — Seguimiento Académico (propietario + director)
    Route::middleware('role:propietario,director')->prefix('propietario')->name('propietario.')->group(function () {
        Route::get('/seguimiento',                                    [\App\Http\Controllers\Propietario\CU13Seguimiento\SeguimientoController::class, 'index'])          ->name('seguimiento.index');
        Route::get('/seguimiento/{id}',                               [\App\Http\Controllers\Propietario\CU13Seguimiento\SeguimientoController::class, 'show'])           ->name('seguimiento.show');
        Route::post('/seguimiento/{id}/abandono',                     [\App\Http\Controllers\Propietario\CU13Seguimiento\SeguimientoController::class, 'registrarAbandono'])->name('seguimiento.abandono');
        Route::get('/seguimiento/{id}/recurso/{idMateria}',           [\App\Http\Controllers\Propietario\CU13Seguimiento\SeguimientoController::class, 'validarRecurso']) ->name('seguimiento.recurso');
    });

    // CU14 — Reportes y Estadísticas (propietario + director; auditoría solo propietario)
    Route::get('/propietario/reportes', [\App\Http\Controllers\Propietario\CU14Reportes\ReporteController::class, 'index'])
        ->middleware('role:propietario,director')
        ->name('propietario.reportes.index');

    // CU2 y CU11 — todos los roles admin
    Route::middleware('role:propietario,director,secretaria')->prefix('propietario')->name('propietario.')->group(function () {
        // CU2 — Gestión de Aulas
        Route::get('/aulas', [AulaController::class, 'index'])->name('aulas.index');
        Route::post('/aulas', [AulaController::class, 'store'])->name('aulas.store');
        Route::put('/aulas/{id}', [AulaController::class, 'update'])->name('aulas.update');
        Route::patch('/aulas/{id}/toggle-activo', [AulaController::class, 'toggleActivo'])->name('aulas.toggle-activo');

        // CU11 — Gestión de Horarios
        Route::get('/horarios', [HorarioController::class, 'index'])->name('horarios.index');
        Route::post('/horarios', [HorarioController::class, 'store'])->name('horarios.store');
        Route::put('/horarios/{id}', [HorarioController::class, 'update'])->name('horarios.update');
        Route::patch('/horarios/{id}/toggle-activo', [HorarioController::class, 'toggleActivo'])->name('horarios.toggle-activo');
    });

    // ── Panel Director ─────────────────────────────────────────────────────────
    Route::get('/panel/director', function () {
        return Inertia::render('Dashboard/Director', [
            'totalCarreras'   => \App\Models\Carrera::count(),
            'carrerasActivas' => \App\Models\Carrera::whereRaw('activo IS TRUE')->count(),
            'totalMaterias'   => \App\Models\Materia::count(),
        ]);
    })->middleware('role:director')->name('dashboard.director');

    // CU4 — Gestión de Carreras + CU5 Materias + CU6 Malla — todos los roles admin
    Route::middleware('role:propietario,director,secretaria')->prefix('director')->name('director.')->group(function () {
        // CU4 Carreras
        Route::get('/carreras', [\App\Http\Controllers\Director\CU4Carreras\CarreraController::class, 'index'])->name('carreras.index');
        Route::post('/carreras', [\App\Http\Controllers\Director\CU4Carreras\CarreraController::class, 'store'])->name('carreras.store');
        Route::put('/carreras/{id}', [\App\Http\Controllers\Director\CU4Carreras\CarreraController::class, 'update'])->name('carreras.update');
        Route::patch('/carreras/{id}/toggle-activo', [\App\Http\Controllers\Director\CU4Carreras\CarreraController::class, 'toggleActivo'])->name('carreras.toggle-activo');
        Route::get('/carreras/{id}/materias', [\App\Http\Controllers\Director\CU5Materias\MateriaController::class, 'porCarrera'])->name('carreras.materias');

        // CU5 Materias
        Route::get('/materias', [\App\Http\Controllers\Director\CU5Materias\MateriaController::class, 'index'])->name('materias.index');
        Route::post('/materias', [\App\Http\Controllers\Director\CU5Materias\MateriaController::class, 'store'])->name('materias.store');
        Route::put('/materias/{id}', [\App\Http\Controllers\Director\CU5Materias\MateriaController::class, 'update'])->name('materias.update');
        Route::patch('/materias/{id}/toggle-activo', [\App\Http\Controllers\Director\CU5Materias\MateriaController::class, 'toggleActivo'])->name('materias.toggle-activo');

        // CU6 Malla Curricular
        Route::post('/carreras/{id}/niveles', [\App\Http\Controllers\Director\CU6Malla\MallaController::class, 'storeNivel'])->name('malla.nivel.store');
        Route::delete('/niveles/{id}', [\App\Http\Controllers\Director\CU6Malla\MallaController::class, 'destroyNivel'])->name('malla.nivel.destroy');
        Route::post('/malla', [\App\Http\Controllers\Director\CU6Malla\MallaController::class, 'storeMalla'])->name('malla.store');
        Route::delete('/malla/{id}', [\App\Http\Controllers\Director\CU6Malla\MallaController::class, 'destroyMalla'])->name('malla.destroy');
        Route::post('/carreras/{id}/nueva-materia', [\App\Http\Controllers\Director\CU6Malla\MallaController::class, 'storeMateriaNueva'])->name('malla.materia.store');

        // CU8 — Períodos Académicos
        Route::get('/periodos', [\App\Http\Controllers\Director\CU8Periodos\PeriodoController::class, 'index'])->name('periodos.index');
        Route::post('/periodos', [\App\Http\Controllers\Director\CU8Periodos\PeriodoController::class, 'store'])->name('periodos.store');
        Route::post('/periodos/lote', [\App\Http\Controllers\Director\CU8Periodos\PeriodoController::class, 'storeLote'])->name('periodos.lote');
        Route::post('/periodos/siguiente-anio', [\App\Http\Controllers\Director\CU8Periodos\PeriodoController::class, 'clonarSiguienteAnio'])->name('periodos.siguiente-anio');
        Route::put('/periodos/{id}', [\App\Http\Controllers\Director\CU8Periodos\PeriodoController::class, 'update'])->name('periodos.update');
        Route::patch('/periodos/{id}/toggle', [\App\Http\Controllers\Director\CU8Periodos\PeriodoController::class, 'toggleActivo'])->name('periodos.toggle');
        Route::delete('/periodos/{id}', [\App\Http\Controllers\Director\CU8Periodos\PeriodoController::class, 'destroy'])->name('periodos.destroy');

        // CU9 — Gestión de Grupos / Oferta Académica
        Route::get('/grupos', [\App\Http\Controllers\Director\CU9Grupos\GrupoController::class, 'index'])->name('grupos.index');
        Route::post('/grupos', [\App\Http\Controllers\Director\CU9Grupos\GrupoController::class, 'store'])->name('grupos.store');
        Route::post('/grupos/clonar', [\App\Http\Controllers\Director\CU9Grupos\GrupoController::class, 'clonar'])->name('grupos.clonar');
        Route::put('/grupos/{id}', [\App\Http\Controllers\Director\CU9Grupos\GrupoController::class, 'update'])->name('grupos.update');
        Route::patch('/grupos/{id}/toggle', [\App\Http\Controllers\Director\CU9Grupos\GrupoController::class, 'toggleActivo'])->name('grupos.toggle');
        Route::delete('/grupos/{id}', [\App\Http\Controllers\Director\CU9Grupos\GrupoController::class, 'destroy'])->name('grupos.destroy');
    });

    // ── Panel Secretaria ───────────────────────────────────────────────────────
    Route::middleware('role:propietario,director,secretaria')->prefix('secretaria')->name('secretaria.')->group(function () {
        Route::get('/panel', function () {
            return Inertia::render('Dashboard/Secretaria');
        })->name('dashboard');

        // Cronogramas (CU10)
        Route::get('/cronogramas', [\App\Http\Controllers\Secretaria\CU10Cronogramas\CronogramaController::class, 'index'])->name('cronogramas.index');
        Route::put('/cronogramas/{id}', [\App\Http\Controllers\Secretaria\CU10Cronogramas\CronogramaController::class, 'update'])->name('cronogramas.update');
        Route::patch('/cronogramas/{id}/toggle-activo', [\App\Http\Controllers\Secretaria\CU10Cronogramas\CronogramaController::class, 'toggleActivo'])->name('cronogramas.toggle-activo');

        Route::get('/perfil',           [\App\Http\Controllers\Secretaria\PerfilController::class, 'index'])           ->name('perfil');
        Route::put('/perfil',           [\App\Http\Controllers\Secretaria\PerfilController::class, 'update'])          ->name('perfil.update');
        Route::put('/perfil/password',  [\App\Http\Controllers\Secretaria\PerfilController::class, 'cambiarPassword']) ->name('perfil.password');

        Route::get('/inscripciones', [\App\Http\Controllers\Secretaria\CU2Inscripciones\InscripcionController::class, 'index'])->name('inscripciones.index');
        Route::post('/inscripciones/manual', [\App\Http\Controllers\Secretaria\CU2Inscripciones\InscripcionController::class, 'storeManual'])->name('inscripciones.manual');

        // CU7 — Gestión de Pagos (fase 1: lado admin)
        Route::get('/pagos',                                      [\App\Http\Controllers\Secretaria\CU3Pagos\PagoController::class, 'index'])             ->name('pagos.index');
        Route::get('/pagos/{id}',                                 [\App\Http\Controllers\Secretaria\CU3Pagos\PagoController::class, 'show'])              ->name('pagos.show');
        Route::post('/pagos/{id}/matricula',                      [\App\Http\Controllers\Secretaria\CU3Pagos\PagoController::class, 'registrarMatricula'])->name('pagos.matricula');
        Route::post('/pagos/{id}/carrera',                        [\App\Http\Controllers\Secretaria\CU3Pagos\PagoController::class, 'registrarCarrera'])  ->name('pagos.carrera');
        Route::post('/pagos/cuota/{idPago}/{numCuota}',           [\App\Http\Controllers\Secretaria\CU3Pagos\PagoController::class, 'pagarCuota'])        ->name('pagos.cuota');
    });

    // ──────────────── Panel Docente ──────────────────────────────────────────────────────────────────────────
    Route::middleware('role:profesor')->prefix('profesor')->name('dashboard.')->group(function () {
        Route::get('/panel', [\App\Http\Controllers\Profesor\PanelController::class, 'index'])->name('profesor');
        Route::get('/grupos/{id_oferta}', [\App\Http\Controllers\Profesor\PanelController::class, 'grupoDetalle'])->name('profesor.grupo');
    });

    // ── Panel Estudiante ───────────────────────────────────────────────────────
    Route::middleware('role:estudiante')->prefix('estudiante')->name('estudiante.')->group(function () {
        Route::get('/panel',                          [\App\Http\Controllers\Estudiante\PanelController::class, 'index'])             ->name('panel');
        // Plan de pago de carrera
        Route::post('/plan/{tipo}',                   [\App\Http\Controllers\Estudiante\PanelController::class, 'elegirPlan'])        ->name('plan')->where('tipo', 'contado|credito|materia');
        Route::get('/pago-carrera/{transId}',         [\App\Http\Controllers\Estudiante\PanelController::class, 'pagoCarrera'])       ->name('pago.carrera');
        Route::get('/pago-carrera/{transId}/estado',  [\App\Http\Controllers\Estudiante\PanelController::class, 'estadoPlan'])        ->name('pago.carrera.estado');
        // Inscripción de materias
        Route::post('/inscribir/{idOferta}',          [\App\Http\Controllers\Estudiante\PanelController::class, 'inscribir'])         ->name('inscribir');
        Route::get('/pago/{transId}',                 [\App\Http\Controllers\Estudiante\PanelController::class, 'pagoInscripcion'])   ->name('pago');
        Route::get('/pago/{transId}/estado',          [\App\Http\Controllers\Estudiante\PanelController::class, 'estadoInscripcion'])->name('pago.estado');
        // Perfil
        Route::get('/perfil',             [PerfilController::class, 'index'])           ->name('perfil');
        Route::put('/perfil',             [PerfilController::class, 'update'])          ->name('perfil.update');
        Route::put('/perfil/password',    [PerfilController::class, 'cambiarPassword']) ->name('perfil.password');
    });

    Route::get('/panel/estudiante', function () {
        return redirect()->route('estudiante.panel');
    })->middleware('role:estudiante')->name('dashboard.estudiante');

});

Route::middleware('auth')->group(function () {
    // Perfil general (propietario, director, secretaria, docente — estudiante va a su propia ruta)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Foto de perfil (todos los roles)
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');

    // Currículum Vitae (solo docentes)
    Route::post('/profile/cv', [ProfileController::class, 'updateCv'])->name('profile.cv.update');
    Route::delete('/profile/cv', [ProfileController::class, 'deleteCv'])->name('profile.cv.delete');

    // Forzar cambio de contraseña
    Route::get('/cambiar-password-inicial', [\App\Http\Controllers\Auth\ForcePasswordChangeController::class, 'show'])->name('password.change.show');
    Route::post('/cambiar-password-inicial', [\App\Http\Controllers\Auth\ForcePasswordChangeController::class, 'update'])->name('password.change.update');
});

require __DIR__.'/auth.php';
