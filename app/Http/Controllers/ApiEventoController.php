<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Http\Request;

class ApiEventoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $eventos = Evento::all();
        return response()->json($eventos);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $evento = Evento::findOrFail($id);
        return response()->json($evento);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre'                   => 'required|string|max:255',
            'fecha'                    => 'required|date',
            'hora'                     => 'required|date_format:H:i,H:i:s',
            'descripcion'              => 'required|string',
            'ciudad'                   => 'required|string|max:255',
            'direccion'                => 'required|string|max:255',
            'estado'                   => 'required|in:creado,cancelado,terminado',
            'aforomax'                 => 'required|integer|min:1',
            'tipo'                     => 'required|in:online,presencial',
            'numMaxEntradasPorPersona' => 'required|integer|min:1|lte:aforomax',
            'categoria_id'             => 'required|exists:categorias,id',
        ]);

        $evento = new Evento($datos);

        //el creador es el usuario del token
        $evento->asistente_id = $request->user()->id;
        $evento->save();

        return response()->json($evento, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $evento = Evento::findOrFail($id);

        //solo el admin o el creador del evento
        if ($request->user()->rol !== 'Administrador' && $evento->asistente_id != $request->user()->id) {
            return response()->json(['mensaje' => 'No tienes permiso para borrar este evento'], 403);
        }

        $evento->delete();

        return response()->json(['mensaje' => 'Evento eliminado']);
    }
}
