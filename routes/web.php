<?php

use App\Http\Controllers\Propietario\CU1Usuarios\UsuarioController;
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

    // ── Panel Director ─────────────────────────────────────────────────────────
    Route::get('/panel/director', function () {
        return Inertia::render('Dashboard/Director');
    })->middleware('role:director')->name('dashboard.director');

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
