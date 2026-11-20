<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\Evento;
use Illuminate\Http\Request;

class InscripcionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //el admin ve todas; el creador solo las de sus eventos
        $inscripciones=Inscripcion::when(auth()->user()->rol !== 'Administrador', function ($consulta) {
                $consulta->whereHas('evento', fn ($evento) => $evento->where('asistente_id', auth()->id()));
            })
            ->latest()
            ->paginate(10);

        return view('inscripciones',compact( 'inscripciones',));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       $evento=Evento::findOrFail($request->evento_id);
       $usuario=auth()->user();

       $volver = redirect()->route('detalle_evento', $evento->id);

       //solo se puede apuntar a eventos que siguen abiertos
       if ($evento->estado !== 'creado') {
           return $volver->with('aviso_inscripcion', 'Este evento ya no admite inscripciones.');
       }

       //una inscripcion por persona y evento
       if (Inscripcion::where('asistente_id', $usuario->id)->where('evento_id', $evento->id)->exists()) {
           return $volver->with('aviso_inscripcion', 'Ya estás inscrito en este evento.');
       }

       $request->validate([
           'num_entradas' => 'required|integer|min:1|max:' . $evento->numMaxEntradasPorPersona,
       ], [
           'num_entradas.required' => 'Indica cuántas entradas quieres.',
           'num_entradas.integer'  => 'El número de entradas no es válido.',
           'num_entradas.min'      => 'Tienes que pedir al menos 1 entrada.',
           'num_entradas.max'      => 'Como máximo puedes pedir ' . $evento->numMaxEntradasPorPersona . ' entradas.',
       ]);

       //no se puede pasar del aforo del evento
       $libres = $evento->aforomax - $evento->entradasOcupadas();
       if ($request->num_entradas > $libres) {
           if ($libres <= 0) {
               $aviso = 'Aforo completo: no quedan entradas para este evento.';
           } elseif ($libres == 1) {
               $aviso = 'Solo queda 1 entrada para este evento.';
           } else {
               $aviso = 'Solo quedan ' . $libres . ' entradas para este evento.';
           }

           return $volver->withInput()->with('aviso_inscripcion', $aviso);
       }

       $inscripcion = new Inscripcion();
       $inscripcion->asistente_id = $usuario->id;
       $inscripcion->evento_id = $evento->id;
       $inscripcion->numEntradas = $request->num_entradas;
       $inscripcion->estado = 'recibida';
       $inscripcion->save();

       return $volver;

    }

    /**
     * Display the specified resource.
     */
    public function show(Inscripcion $inscripcion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Inscripcion $inscripcion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inscripcion $inscripcion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $inscripcion=Inscripcion::with('evento')->findOrFail($id);
        $usuario=auth()->user();

        //puede borrarla el propio asistente, el creador del evento o el admin
        $permitido = $usuario->rol === 'Administrador'
            || $inscripcion->asistente_id == $usuario->id
            || $inscripcion->evento->asistente_id == $usuario->id;

        if ($permitido) {
            $inscripcion->delete();
        }

        //el asistente vuelve a la ficha del evento; admin y creador a la lista
        if ($usuario->rol === 'Asistente') {
            return redirect()->route('detalle_evento', $inscripcion->evento_id);
        }

        return redirect()->route('inscripciones');
    }
}
