<?php

namespace App\Http\Controllers\Secretaria\CU3Pagos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PagoController extends Controller
{
    public function index()
    {
        return Inertia::render('Secretaria/CU3Pagos/Index');
    }
}
