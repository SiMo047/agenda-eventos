<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\User;
use Illuminate\Http\Request;

class ApiInscripcionController extends Controller
{
    /**
     * Inscripciones de un asistente (por su DNI).
     */
    public function index($dni)
    {
        $asistente = User::where('dni', $dni)->firstOrFail();

        $inscripciones = Inscripcion::where('asistente_id', $asistente->id)->get();

        return response()->json($inscripciones);
    }

    /**
     * Inscripcion de un asistente (por su DNI) en un evento concreto.
     */
    public function show($dni, $idEvento)
    {
        $asistente = User::where('dni', $dni)->firstOrFail();

        $inscripcion = Inscripcion::where('asistente_id', $asistente->id)
            ->where('evento_id', $idEvento)
            ->firstOrFail();

        return response()->json($inscripcion);
    }
}
