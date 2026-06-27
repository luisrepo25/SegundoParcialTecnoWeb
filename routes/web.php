<?php

use App\Http\Controllers\Propietario\CU1Usuarios\UsuarioController;
use App\Http\Controllers\Propietario\CU2Aulas\AulaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    $user = auth()->user();

    return match ($user?->role) {
        'admin'     => redirect()->route('dashboard.admin'),
        'director'  => redirect()->route('dashboard.director'),
        'secretary' => redirect()->route('secretaria.dashboard'),
        'teacher'   => redirect()->route('dashboard.teacher'),
        default     => redirect()->route('dashboard.student'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {

    // ── Panel Propietario ──────────────────────────────────────────────────────
    Route::get('/panel/propietario', function () {
        return Inertia::render('Dashboard/Propietario');
    })->middleware('role:admin')->name('dashboard.admin');

    // CU1 — Gestión de Usuarios (propietario, director, secretaria)
    Route::middleware('role:admin,director,secretary')->prefix('propietario')->name('propietario.')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::patch('/usuarios/{id}/toggle-activo', [UsuarioController::class, 'toggleActivo'])->name('usuarios.toggle-activo');
        Route::patch('/usuarios/{id}/password', [UsuarioController::class, 'cambiarPassword'])->name('usuarios.password');
    });

    // CU2 — Gestión de Aulas (solo propietario)
    Route::middleware('role:admin')->prefix('propietario')->name('propietario.')->group(function () {
        Route::get('/aulas', [AulaController::class, 'index'])->name('aulas.index');
        Route::post('/aulas', [AulaController::class, 'store'])->name('aulas.store');
        Route::put('/aulas/{id}', [AulaController::class, 'update'])->name('aulas.update');
        Route::patch('/aulas/{id}/toggle-activo', [AulaController::class, 'toggleActivo'])->name('aulas.toggle-activo');
    });

    // ── Panel Director ─────────────────────────────────────────────────────────
    Route::get('/panel/director', function () {
        return Inertia::render('Dashboard/Director', [
            'totalCarreras'   => \App\Models\Carrera::count(),
            'carrerasActivas' => \App\Models\Carrera::where('activo', true)->count(),
            'totalMaterias'   => \App\Models\Materia::count(),
        ]);
    })->middleware('role:director')->name('dashboard.director');

    // CU4 — Gestión de Carreras + CU5 Materias (director)
    Route::middleware('role:director')->prefix('director')->name('director.')->group(function () {
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
    });

    // ── Panel Secretaria ───────────────────────────────────────────────────────
    Route::middleware('role:secretary')->prefix('secretaria')->name('secretaria.')->group(function () {
        Route::get('/panel', function () {
            return Inertia::render('Dashboard/Secretaria');
        })->name('dashboard');

        Route::get('/inscripciones', [\App\Http\Controllers\Secretaria\CU2Inscripciones\InscripcionController::class, 'index'])->name('inscripciones.index');
        Route::get('/pagos', [\App\Http\Controllers\Secretaria\CU3Pagos\PagoController::class, 'index'])->name('pagos.index');
    });

    // ── Panel Docente ──────────────────────────────────────────────────────────
    Route::get('/panel/docente', function () {
        return Inertia::render('Dashboard/Docente');
    })->middleware('role:teacher')->name('dashboard.teacher');

    // ── Panel Estudiante ───────────────────────────────────────────────────────
    Route::get('/panel/estudiante', function () {
        return Inertia::render('Dashboard/Estudiante');
    })->middleware('role:student')->name('dashboard.student');

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
