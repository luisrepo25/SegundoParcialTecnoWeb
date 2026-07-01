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
    return redirect()->route('oferta.index');
});

// ── Oferta académica pública (sin autenticación) ───────────────────────────
Route::prefix('oferta')->name('oferta.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Public\OfertaController::class, 'index'])->name('index');
    Route::get('/carreras', [\App\Http\Controllers\Public\OfertaController::class, 'carreras'])->name('carreras');
    Route::get('/malla', [\App\Http\Controllers\Public\OfertaController::class, 'malla'])->name('malla');
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
        'propietario' => redirect()->route('dashboard.propietario'),
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
                'total_usuarios'       => Usuario::count(),
                'usuarios_activos'     => Usuario::whereRaw('activo IS TRUE')->count(),
                'total_estudiantes'    => Usuario::where('id_rol', 5)->count(),
                'total_profesores'     => Usuario::where('id_rol', 4)->count(),
                'total_aulas'          => Aula::count(),
                'total_carreras'       => \App\Models\Carrera::whereRaw('activo IS TRUE')->count(),
                'total_materias'       => \App\Models\Materia::whereRaw('activo IS TRUE')->count(),
                'total_horarios'       => \App\Models\Horario::count(),
                'inscripciones_activas'=> \Illuminate\Support\Facades\DB::table('inscripciones')->where('estado', 'activo')->count(),
                'grupos_activos'       => \Illuminate\Support\Facades\DB::table('grupos')->whereRaw('activo IS TRUE')->count(),
                'periodos_activos'     => \Illuminate\Support\Facades\DB::table('periodos_dictado')->whereRaw('activo IS TRUE')->count(),
                'cuotas_pendientes'    => \Illuminate\Support\Facades\DB::table('cuotas_carrera')->where('estado', 'pendiente')->count(),
            ],
        ]);
    })->middleware('role:propietario')->name('dashboard.propietario');

    // Perfil Propietario
    Route::middleware('role:propietario')->prefix('propietario')->name('propietario.')->group(function () {
        Route::get('/perfil',          [\App\Http\Controllers\Propietario\PerfilController::class, 'index'])           ->name('perfil');
        Route::put('/perfil',          [\App\Http\Controllers\Propietario\PerfilController::class, 'update'])          ->name('perfil.update');
        Route::put('/perfil/password', [\App\Http\Controllers\Propietario\PerfilController::class, 'cambiarPassword']) ->name('perfil.password');
    });

    // Configuración del sitio — propietario + director
    Route::middleware('role:propietario,director')->prefix('propietario')->name('propietario.')->group(function () {
        Route::get('/configuracion',  [\App\Http\Controllers\Propietario\ConfiguracionSitioController::class, 'index'])  ->name('configuracion.index');
        Route::post('/configuracion', [\App\Http\Controllers\Propietario\ConfiguracionSitioController::class, 'update']) ->name('configuracion.update');
    });

    // CU1 — Gestión de Usuarios — lectura para todos los roles admin
    Route::middleware('role:propietario,director,secretaria')->prefix('propietario')->name('propietario.')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    });

    // CU1 — store, update, password: propietario + director + secretaria
    Route::middleware('role:propietario,director,secretaria')->prefix('propietario')->name('propietario.')->group(function () {
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::patch('/usuarios/{id}/password', [UsuarioController::class, 'cambiarPassword'])->name('usuarios.password');
    });
    // CU1 — toggle-activo: propietario + director (secretaria no puede desactivar/reactivar)
    Route::middleware('role:propietario,director')->prefix('propietario')->name('propietario.')->group(function () {
        Route::patch('/usuarios/{id}/toggle-activo', [UsuarioController::class, 'toggleActivo'])->name('usuarios.toggle-activo');
    });

    // CU13 — Seguimiento Académico (propietario + director + secretaria)
    Route::middleware('role:propietario,director,secretaria')->prefix('propietario')->name('propietario.')->group(function () {
        Route::get('/seguimiento',                                    [\App\Http\Controllers\Propietario\CU13Seguimiento\SeguimientoController::class, 'index'])          ->name('seguimiento.index');
        Route::get('/seguimiento/{id}',                               [\App\Http\Controllers\Propietario\CU13Seguimiento\SeguimientoController::class, 'show'])           ->name('seguimiento.show');
        Route::post('/seguimiento/{id}/abandono',                     [\App\Http\Controllers\Propietario\CU13Seguimiento\SeguimientoController::class, 'registrarAbandono'])->name('seguimiento.abandono');
        Route::get('/seguimiento/{id}/recurso/{idMateria}',           [\App\Http\Controllers\Propietario\CU13Seguimiento\SeguimientoController::class, 'validarRecurso']) ->name('seguimiento.recurso');
    });

    // CU14 — Reportes y Estadísticas (propietario + director; auditoría solo propietario)
    Route::get('/propietario/reportes', [\App\Http\Controllers\Propietario\CU14Reportes\ReporteController::class, 'index'])
        ->middleware('role:propietario,director')
        ->name('propietario.reportes.index');

    // CU2 y CU11 — lectura para todos los roles admin
    Route::middleware('role:propietario,director,secretaria')->prefix('propietario')->name('propietario.')->group(function () {
        Route::get('/aulas',    [AulaController::class,    'index'])->name('aulas.index');
        Route::get('/horarios', [HorarioController::class, 'index'])->name('horarios.index');
    });

    // CU2 y CU11 — escritura propietario + director
    Route::middleware('role:propietario,director')->prefix('propietario')->name('propietario.')->group(function () {
        Route::post('/aulas', [AulaController::class, 'store'])->name('aulas.store');
        Route::put('/aulas/{id}', [AulaController::class, 'update'])->name('aulas.update');
        Route::patch('/aulas/{id}/toggle-activo', [AulaController::class, 'toggleActivo'])->name('aulas.toggle-activo');

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

    // CU3/4/5/8/9 — Lectura: propietario, director, secretaria
    Route::middleware('role:propietario,director,secretaria')->prefix('director')->name('director.')->group(function () {
        Route::get('/carreras', [\App\Http\Controllers\Director\CU4Carreras\CarreraController::class, 'index'])->name('carreras.index');
        Route::get('/carreras/{id}/materias', [\App\Http\Controllers\Director\CU3Materias\MateriaController::class, 'porCarrera'])->name('carreras.materias');
        Route::get('/materias', [\App\Http\Controllers\Director\CU3Materias\MateriaController::class, 'index'])->name('materias.index');
        Route::get('/periodos', [\App\Http\Controllers\Director\CU8Periodos\PeriodoController::class, 'index'])->name('periodos.index');
        Route::get('/grupos', [\App\Http\Controllers\Director\CU9Grupos\GrupoController::class, 'index'])->name('grupos.index');
        Route::get('/grupos/{id}/inscritos', [\App\Http\Controllers\Director\CU9Grupos\GrupoController::class, 'inscritos'])->name('grupos.inscritos');
    });

    // CU3/4/5/8/9 — Escritura: solo propietario y director
    Route::middleware('role:propietario,director')->prefix('director')->name('director.')->group(function () {
        // CU4 Carreras
        Route::post('/carreras', [\App\Http\Controllers\Director\CU4Carreras\CarreraController::class, 'store'])->name('carreras.store');
        Route::put('/carreras/{id}', [\App\Http\Controllers\Director\CU4Carreras\CarreraController::class, 'update'])->name('carreras.update');
        Route::patch('/carreras/{id}/toggle-activo', [\App\Http\Controllers\Director\CU4Carreras\CarreraController::class, 'toggleActivo'])->name('carreras.toggle-activo');

        // CU3 Materias
        Route::post('/materias', [\App\Http\Controllers\Director\CU3Materias\MateriaController::class, 'store'])->name('materias.store');
        Route::put('/materias/{id}', [\App\Http\Controllers\Director\CU3Materias\MateriaController::class, 'update'])->name('materias.update');
        Route::patch('/materias/{id}/toggle-activo', [\App\Http\Controllers\Director\CU3Materias\MateriaController::class, 'toggleActivo'])->name('materias.toggle-activo');

        // CU5 Malla Curricular
        Route::post('/carreras/{id}/niveles', [\App\Http\Controllers\Director\CU5Malla\MallaController::class, 'storeNivel'])->name('malla.nivel.store');
        Route::delete('/niveles/{id}', [\App\Http\Controllers\Director\CU5Malla\MallaController::class, 'destroyNivel'])->name('malla.nivel.destroy');
        Route::post('/malla', [\App\Http\Controllers\Director\CU5Malla\MallaController::class, 'storeMalla'])->name('malla.store');
        Route::delete('/malla/{id}', [\App\Http\Controllers\Director\CU5Malla\MallaController::class, 'destroyMalla'])->name('malla.destroy');
        Route::post('/carreras/{id}/nueva-materia', [\App\Http\Controllers\Director\CU5Malla\MallaController::class, 'storeMateriaNueva'])->name('malla.materia.store');

        // CU8 Períodos
        Route::post('/periodos', [\App\Http\Controllers\Director\CU8Periodos\PeriodoController::class, 'store'])->name('periodos.store');
        Route::post('/periodos/lote', [\App\Http\Controllers\Director\CU8Periodos\PeriodoController::class, 'storeLote'])->name('periodos.lote');
        Route::post('/periodos/siguiente-anio', [\App\Http\Controllers\Director\CU8Periodos\PeriodoController::class, 'clonarSiguienteAnio'])->name('periodos.siguiente-anio');
        Route::put('/periodos/{id}', [\App\Http\Controllers\Director\CU8Periodos\PeriodoController::class, 'update'])->name('periodos.update');
        Route::patch('/periodos/{id}/toggle', [\App\Http\Controllers\Director\CU8Periodos\PeriodoController::class, 'toggleActivo'])->name('periodos.toggle');
        Route::delete('/periodos/{id}', [\App\Http\Controllers\Director\CU8Periodos\PeriodoController::class, 'destroy'])->name('periodos.destroy');

        // CU9 Grupos
        Route::post('/grupos', [\App\Http\Controllers\Director\CU9Grupos\GrupoController::class, 'store'])->name('grupos.store');
        Route::post('/grupos/clonar', [\App\Http\Controllers\Director\CU9Grupos\GrupoController::class, 'clonar'])->name('grupos.clonar');
        Route::put('/grupos/{id}', [\App\Http\Controllers\Director\CU9Grupos\GrupoController::class, 'update'])->name('grupos.update');
        Route::patch('/grupos/{id}/toggle', [\App\Http\Controllers\Director\CU9Grupos\GrupoController::class, 'toggleActivo'])->name('grupos.toggle');
        Route::delete('/grupos/{id}', [\App\Http\Controllers\Director\CU9Grupos\GrupoController::class, 'destroy'])->name('grupos.destroy');
        Route::delete('/periodos/{id}/grupos', [\App\Http\Controllers\Director\CU9Grupos\GrupoController::class, 'destroyPeriodo'])->name('grupos.destroyPeriodo');

    });

    // CU12 — Notas de un grupo (propietario + director + secretaria)
    Route::middleware('role:propietario,director,secretaria')->prefix('director')->name('director.')->group(function () {
        Route::get('/grupos/{id}/notas', [\App\Http\Controllers\Director\GrupoDetalleController::class, 'show'])->name('grupos.notas');
    });

    // Perfil Director — solo director
    Route::middleware('role:director')->prefix('director')->name('director.')->group(function () {
        Route::get('/perfil',          [\App\Http\Controllers\Director\PerfilController::class, 'index'])           ->name('perfil');
        Route::put('/perfil',          [\App\Http\Controllers\Director\PerfilController::class, 'update'])          ->name('perfil.update');
        Route::put('/perfil/password', [\App\Http\Controllers\Director\PerfilController::class, 'cambiarPassword']) ->name('perfil.password');
    });

    // ── Panel Secretaria ───────────────────────────────────────────────────────
    Route::middleware('role:propietario,director,secretaria')->prefix('secretaria')->name('secretaria.')->group(function () {
        Route::get('/panel', function () {
            return Inertia::render('Dashboard/Secretaria');
        })->name('dashboard');

        // Cronogramas (CU10) — solo lectura para secretaria
        Route::get('/cronogramas', [\App\Http\Controllers\Secretaria\CU10Cronogramas\CronogramaController::class, 'index'])->name('cronogramas.index');

        Route::get('/inscripciones', [\App\Http\Controllers\Secretaria\CU6Inscripciones\InscripcionController::class, 'index'])->name('inscripciones.index');
        Route::post('/inscripciones/manual', [\App\Http\Controllers\Secretaria\CU6Inscripciones\InscripcionController::class, 'storeManual'])->name('inscripciones.manual');

        // CU7 — Gestión de Pagos (fase 1: lado admin)
        Route::get('/pagos',                                      [\App\Http\Controllers\Secretaria\CU7Pagos\PagoController::class, 'index'])             ->name('pagos.index');
        Route::get('/pagos/{id}',                                 [\App\Http\Controllers\Secretaria\CU7Pagos\PagoController::class, 'show'])              ->name('pagos.show');
        Route::post('/pagos/{id}/matricula',                      [\App\Http\Controllers\Secretaria\CU7Pagos\PagoController::class, 'registrarMatricula'])->name('pagos.matricula');
        Route::post('/pagos/{id}/carrera',                        [\App\Http\Controllers\Secretaria\CU7Pagos\PagoController::class, 'registrarCarrera'])  ->name('pagos.carrera');
        Route::post('/pagos/cuota/{idPago}/{numCuota}',           [\App\Http\Controllers\Secretaria\CU7Pagos\PagoController::class, 'pagarCuota'])        ->name('pagos.cuota');
    });

    // CU10 — Cronogramas escritura: propietario + director (secretaria solo puede ver)
    Route::middleware('role:propietario,director')->prefix('secretaria')->name('secretaria.')->group(function () {
        Route::post('/cronogramas',                     [\App\Http\Controllers\Secretaria\CU10Cronogramas\CronogramaController::class, 'store'])        ->name('cronogramas.store');
        Route::put('/cronogramas/{id}',                 [\App\Http\Controllers\Secretaria\CU10Cronogramas\CronogramaController::class, 'update'])       ->name('cronogramas.update');
        Route::patch('/cronogramas/{id}/toggle-activo', [\App\Http\Controllers\Secretaria\CU10Cronogramas\CronogramaController::class, 'toggleActivo']) ->name('cronogramas.toggle-activo');
        Route::delete('/cronogramas/{id}',              [\App\Http\Controllers\Secretaria\CU10Cronogramas\CronogramaController::class, 'destroy'])      ->name('cronogramas.destroy');
    });

    // Perfil Secretaria — solo secretaria
    Route::middleware('role:secretaria')->prefix('secretaria')->name('secretaria.')->group(function () {
        Route::get('/perfil',          [\App\Http\Controllers\Secretaria\PerfilController::class, 'index'])           ->name('perfil');
        Route::put('/perfil',          [\App\Http\Controllers\Secretaria\PerfilController::class, 'update'])          ->name('perfil.update');
        Route::put('/perfil/password', [\App\Http\Controllers\Secretaria\PerfilController::class, 'cambiarPassword']) ->name('perfil.password');
    });

    // ──────────────── Panel Docente ──────────────────────────────────────────────────────────────────────────
    Route::middleware('role:profesor')->prefix('profesor')->name('dashboard.')->group(function () {
        Route::get('/panel', [\App\Http\Controllers\Profesor\PanelController::class, 'index'])->name('profesor');
        Route::get('/grupos/{id_oferta}', [\App\Http\Controllers\Profesor\PanelController::class, 'grupoDetalle'])->name('profesor.grupo');
    });

    // CU12 — Evaluaciones y Notas (docente)
    Route::middleware('role:profesor')->prefix('profesor')->name('profesor.')->group(function () {
        Route::post('/evaluaciones',        [\App\Http\Controllers\Profesor\CU12Evaluaciones\EvaluacionController::class, 'store'])       ->name('evaluaciones.store');
        Route::post('/evaluaciones/masivo', [\App\Http\Controllers\Profesor\CU12Evaluaciones\EvaluacionController::class, 'storeMasivo']) ->name('evaluaciones.masivo');

        // Perfil Docente
        Route::get('/perfil',          [\App\Http\Controllers\Profesor\PerfilController::class, 'index'])           ->name('perfil');
        Route::put('/perfil',          [\App\Http\Controllers\Profesor\PerfilController::class, 'update'])          ->name('perfil.update');
        Route::put('/perfil/password', [\App\Http\Controllers\Profesor\PerfilController::class, 'cambiarPassword']) ->name('perfil.password');
    });

    // CU12 — Evaluaciones y Notas (admin: secretaria/director/propietario en nombre del docente)
    Route::middleware('role:propietario,director,secretaria')->prefix('admin')->name('admin.')->group(function () {
        Route::post('/evaluaciones',        [\App\Http\Controllers\Profesor\CU12Evaluaciones\EvaluacionController::class, 'store'])       ->name('evaluaciones.store');
        Route::post('/evaluaciones/masivo', [\App\Http\Controllers\Profesor\CU12Evaluaciones\EvaluacionController::class, 'storeMasivo']) ->name('evaluaciones.masivo');
    });

    // ── Panel Estudiante ───────────────────────────────────────────────────────
    Route::middleware('role:estudiante')->prefix('estudiante')->name('estudiante.')->group(function () {
        Route::get('/panel',                          [\App\Http\Controllers\Estudiante\PanelController::class, 'index'])             ->name('panel');
        Route::get('/materias',                       [\App\Http\Controllers\Estudiante\PanelController::class, 'materias'])          ->name('materias');
        Route::get('/malla',                          [\App\Http\Controllers\Estudiante\PanelController::class, 'malla'])             ->name('malla');
        Route::get('/notas',                          [\App\Http\Controllers\Estudiante\PanelController::class, 'notas'])             ->name('notas');
        Route::get('/pagos',                          [\App\Http\Controllers\Estudiante\PanelController::class, 'pagos'])             ->name('pagos');
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

