<?php

namespace App\Http\Controllers\Secretaria\CU2Inscripciones;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InscripcionController extends Controller
{
    public function index()
    {
        return Inertia::render('Secretaria/CU2Inscripciones/Index');
    }
}
