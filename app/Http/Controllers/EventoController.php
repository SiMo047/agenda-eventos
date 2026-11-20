<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Categoria;
use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;




class EventoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //el admin ve todos los eventos; el creador solo los suyos
        $eventos=Evento::when(!$this->esAdmin(), fn ($consulta) => $consulta->where('asistente_id', auth()->id()))
            ->orderBy('fecha')
            ->paginate(25);

        return view('eventos',['eventos'=>$eventos]);

    }

    public function index_welcome()
    {
        $eventos=Evento::paginate(25);
        return view('welcome',['eventos'=>$eventos]);

    }

    public function agenda()
    {

        $categorias= Categoria::all();
        $eventos=Evento::paginate(25);
        return view('web.agenda',compact('categorias','eventos'));

    }

    public function detalle_evento($id)
    {
        $evento=Evento::findOrFail($id);

        //la ficha es publica: si no hay sesion no hay inscripcion que buscar
        $inscripcion=null;
        if (auth()->check()) {
            $inscripcion=Inscripcion::where('asistente_id',auth()->id())->where('evento_id',$evento->id)->first();
        }

        $registrado=$inscripcion !== null;

        return view('web.detalle_evento',compact('evento', 'registrado','inscripcion'));
    }



    public function filtro(Request $request)
    {
            $categorias= Categoria::all();
            $categoria=$request->categoria;
            $fecha=$request->filtro;

            //si no se elige categoria se muestran todas
            $eventos = Evento::when($categoria, fn ($consulta) => $consulta->where('categoria_id', $categoria));

            if ($fecha === 'semana') {
                $eventos->whereBetween('fecha', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
            } elseif ($fecha === 'mes') {
                $eventos->whereYear('fecha', now()->year)->whereMonth('fecha', now()->month);
            }

            $eventos=$eventos->orderBy('fecha')->get();


            return view('web.filtrado',compact('eventos','categorias'));
    }

    public function explora()
    {
        $infos = collect([
            (object) [
                'nombre' => 'Playas Exóticas',
                'descripcion' => 'Disfruta de la belleza natural en nuestras playas exóticas , sumérgete en aguas cristalinas y arena dorada en nuestras playas paradisíacas ',
                'imagen'=>'playa.jpg',
            ],
            (object) [
                'nombre' => 'Servicios de Primera Clase',
                'descripcion' => 'Comodidad y conveniencia al alcance de tu mano con nuestros servicios de primera clase',
                'imagen'=>'servicios.jpg',
            ],
            (object) [
                'nombre' => 'Transporte Eficiente',
                'descripcion' => 'Con opciones de transporte público y alquiler de vehículos, llegar a tu destino nunca ha sido tan fácil.',
                'imagen'=>'transporte.jpg',
            ],
            (object) [
                'nombre' => 'Instalaciones Modernas',
                'descripcion' => 'Desde baños y duchas hasta áreas de picnic y zonas de juegos, nuestras instalaciones tienen todo lo que necesitas.',
                'imagen'=>'instalaciones.jpg',
            ],
        ]);


        return view('web.explora', compact('infos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categorias= Categoria::all();
        return view('forms.evento',compact('categorias'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $evento = new Evento($datos);

        //el creador es siempre el usuario que ha iniciado sesion
        $evento->asistente_id = auth()->id();
        $evento->save();

        $this->guardarImagen($request, $evento);

         return redirect()->route('eventos');
    }

    /**
     * Display the specified resource.
     */
    public function show(Evento $evento)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $evento=$this->eventoPermitido($id);
        $categorias= Categoria::all();

        return view('forms.editevento',compact( 'evento','categorias'));



    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $evento=$this->eventoPermitido($id);
        $datos = $this->validar($request);

        //el creador del evento no cambia al editarlo
        $evento->fill($datos);
        $evento->save();

        $this->guardarImagen($request, $evento);

        return redirect()->route('eventos');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $evento=$this->eventoPermitido($id);

        if ($evento->imagen) {
            Storage::delete('public/img/' . $evento->imagen);
        }

        $evento->delete();
        return redirect()->route('eventos');
    }

    //validacion de los datos del formulario (la misma para crear y editar)
    private function validar(Request $request): array
    {
        return $request->validate([
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
            'imagen'                   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'required'                     => 'Rellena el campo :attribute.',
            'max'                          => 'El campo :attribute es demasiado largo.',
            'integer'                      => 'El campo :attribute tiene que ser un número.',
            'min'                          => 'El campo :attribute tiene que ser al menos :min.',
            'date'                         => 'La fecha no es válida.',
            'hora.date_format'             => 'La hora no es válida.',
            'in'                           => 'El valor de :attribute no es válido.',
            'categoria_id.exists'          => 'La categoría elegida no existe.',
            'numMaxEntradasPorPersona.lte' => 'Las entradas por persona no pueden superar el aforo.',
            'imagen.image'                 => 'La imagen tiene que ser JPG, PNG o WEBP.',
            'imagen.mimes'                 => 'La imagen tiene que ser JPG, PNG o WEBP.',
            'imagen.max'                   => 'La imagen no puede pesar más de 4 MB.',
        ], [
            'nombre'                   => 'nombre',
            'fecha'                    => 'fecha',
            'hora'                     => 'hora',
            'descripcion'              => 'descripción',
            'ciudad'                   => 'ciudad',
            'direccion'                => 'dirección',
            'estado'                   => 'estado',
            'aforomax'                 => 'aforo máximo',
            'tipo'                     => 'tipo',
            'numMaxEntradasPorPersona' => 'entradas por persona',
            'categoria_id'             => 'categoría',
        ]);
    }

    //guarda la imagen subida (si hay) con su extension real y borra la anterior
    private function guardarImagen(Request $request, Evento $evento): void
    {
        if (!$request->hasFile('imagen')) {
            return;
        }

        if ($evento->imagen) {
            Storage::delete('public/img/' . $evento->imagen);
        }

        $imagen = $request->file('imagen');
        $nombreImagen = 'evento_' . $evento->id . '.' . $imagen->extension();
        $imagen->storeAs('public/img', $nombreImagen);

        $evento->imagen = $nombreImagen;
        $evento->save();
    }

    //el admin puede gestionar cualquier evento; el creador solo los suyos
    private function eventoPermitido($id): Evento
    {
        $evento=Evento::findOrFail($id);

        //igual que el middleware: si no tiene permiso vuelve a su lista
        if (!$this->esAdmin() && $evento->asistente_id != auth()->id()) {
            abort(redirect()->route('eventos'));
        }

        return $evento;
    }

    private function esAdmin(): bool
    {
        return auth()->user()->rol === 'Administrador';
    }
}
