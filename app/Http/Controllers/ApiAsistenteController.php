<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ApiAsistenteController extends Controller
{
    
    public function show($dni)
    {
        $asistente = User::where('dni', $dni)->firstOrFail();
        return response()->json($asistente);
    }
}
