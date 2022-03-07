<?php

namespace App\Http\Controllers;

use App\Models\Jail;
use App\Models\Ward;
use Illuminate\Http\Request;

class JailController extends Controller
{

    public function __construct()
    {
        $this->middleware('can:manage-jails');
    }


    // Función para mostrar la vista principal de todas las cárceles
    public function index()
    {
        // Obtener todos las cárceles
        $jails = Jail::query();

        // Sección para el buscador
        // https://laravel.com/docs/8.x/queries#basic-where-clauses
        if (request('search')) {
            $jails = $jails->where('name', 'like', '%' . request('search') . '%');
        }

        $jails = $jails->orderBy('name', 'asc')
            ->paginate();


        // Mandar a la vista todas las cárceles
            return view('jail.index', compact('jails'));
    }



    // Función para mostrar la vista del formulario
    public function create()
    {
        // Obtener todos los pabellones que estan en estado activo
        $wards = Ward::where('state', true)->get();

        return view('jail.create', ['wards' => $wards]);
    }






    // Función para tomar los datos del formulario y guardar en la BDD
    public function store(Request $request)
    {
        // Validación de datos respectivos
        $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:45'],
            'code' => ['required', 'string', 'alpha_dash', 'min:5', 'max:45'],
            'type' => ['required', 'string'],
            'capacity' => ['required', 'string', 'numeric', 'digits:1', 'min:2', 'max:5'],
            'ward_id' => ['required', 'string', 'numeric', 'exists:wards,id'],
            'description' => ['nullable', 'string', 'min:5', 'max:255'],
        ]);


       // Guardar en la BDD los datos por medio de ELOQUENT
        $jail= Jail::create([
            "name" => $request['name'],
            "code" => $request['code'],
            "type" => $request['type'],
            "capacity" => $request['capacity'],
            "ward_id" => $request['ward_id'],
            "description" => $request['description']
        ]);

        // Se imprime el mensaje de exito
        return back()->with('status', 'Jail created successfully');
    }


    // Función para mostrar la vista y los datos de una sola cárcel
    public function show(Jail $jail)
    {
        return view('jail.show', ['jail' => $jail]);
    }




    // Función para mostrar la vista y los datos de una sola cárcel a través de un formulario
    public function edit(Jail $jail)
    {
        // Obtener todos los pabellones que estan en estado activo
        $wards = Ward::where('state', true)->get();

        return view('jail.update', [
            'jail' => $jail,

            'wards' => $wards,
        ]);
    }




    // Función para tomar los datos del formulario y actualizar en la BDD
    public function update(Request $request,  Jail $jail)
    {
        // Validación de datos respectivos
        $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:45'],
            'code' => ['required', 'string', 'alpha_dash', 'min:5', 'max:45'],
            'type' => ['required', 'string'],
            'capacity' => ['required', 'string', 'numeric', 'digits:1', 'min:2', 'max:5'],
            'ward_id' => ['required', 'string', 'numeric', 'exists:wards,id'],
            'description' => ['nullable', 'string', 'min:5', 'max:255'],
        ]);


        // Se procede con la actualización de los datos por medio de Eloquent
        $jail->update([
            'name' =>  $request['name'],
            'code' =>  $request['code'],
            'type' =>  $request['type'],
            'capacity' =>  $request['capacity'],
            'ward_id' =>  $request['ward_id'],
            'description' =>  $request['description'],
        ]);

        // Se imprime el mensaje de exito
        return back()->with('status', 'Jail updated successfully');
    }

    // Función para dar de baja a una cárcel en la BDD
    public function destroy(Jail $jail)
    {
        // Tomar el estado de la cárcel
        $state = $jail->state;


        // Almacenar un mensaje para el estado
        $message = $state ? 'inactivated' : 'activated';


        // Validación para que no se de de baja si tiene asignado un prisionero
        if ($this->verifyJailHasAssignedPrisoners($jail))
        {
            return back()->with([
                'status' => "The jail $jail->name has assigned prisoner(s).",
                'color' => 'yellow'
            ]);
        }


        // Cambiar el estado de la cárcel
        $jail->state = !$state;

        // Guardar los cambios
        $jail->save();

        // Se imprime el mensaje de exito
        return back()->with('status', "Jail $message successfully");
    }

    // Función para validar que una cárcel tiene o no un usuario prisionero asignado
    private function verifyJailHasAssignedPrisoners(Jail $jail): bool
    {
        return (bool)$jail->users->count();
    }


}
