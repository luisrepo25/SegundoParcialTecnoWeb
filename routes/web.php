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
    \Illuminate\Support\Facades\Log::info('Dashboard route hit. User: ' . ($user ? $user->email : 'NONE') . ', Role: ' . ($user ? $user->role : 'NONE'));

    return match ($user?->role) {
        'admin' => redirect()->route('dashboard.admin'),
        'director' => redirect()->route('dashboard.director'),
        'secretary' => redirect()->route('dashboard.secretary'),
        'teacher' => redirect()->route('dashboard.teacher'),
        default => redirect()->route('dashboard.student'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/panel/admin', function () {
        return Inertia::render('Dashboard/Admin');
    })->middleware('role:admin')->name('dashboard.admin');

    // CU1 — Gestión de Usuarios (admin, director, secretaria)
    Route::middleware('role:admin,director,secretary')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::patch('/usuarios/{id}/toggle-activo', [UsuarioController::class, 'toggleActivo'])->name('usuarios.toggle-activo');
        Route::patch('/usuarios/{id}/password', [UsuarioController::class, 'cambiarPassword'])->name('usuarios.password');
    });

    Route::get('/panel/director', function () {
        return Inertia::render('Dashboard/Director');
    })->middleware('role:director')->name('dashboard.director');

    Route::get('/panel/secretaria', function () {
        return Inertia::render('Dashboard/Secretary');
    })->middleware('role:secretary')->name('dashboard.secretary');

    Route::get('/panel/docente', function () {
        return Inertia::render('Dashboard/Teacher');
    })->middleware('role:teacher')->name('dashboard.teacher');

    Route::get('/panel/estudiante', function () {
        return Inertia::render('Dashboard/Student');
    })->middleware('role:student')->name('dashboard.student');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
