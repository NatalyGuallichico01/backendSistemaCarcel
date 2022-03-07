<?php

namespace App\Http\Controllers;

use App\Models\Ward;
use Illuminate\Http\Request;

class WardController extends Controller
{

    public function __construct()
    {
        $this->middleware('can:manage-wards');
    }


    // Función para mostrar la vista principal de todo los pabellones
    public function index()
    {
        // Obtener todos los pabellones
        $wards = Ward::query();

        if (request('search'))
        {
            // https://laravel.com/docs/8.x/queries#basic-where-clauses
            $wards = $wards->where('name', 'like', '%' . request('search') . '%');
        }

        $wards = $wards->orderBy('name', 'asc')
            ->paginate();

        // Mandar a la vista todos los pabellones
        return view('ward.index', compact('wards'));

    }


    // Función para mostrar la vista del formulario
    public function create()
    {
        return view('ward.create');
    }




    // Función para tomar los datos del formulario y guardar en la BDD
    public function store(Request $request)
    {

        // Validación de datos respectivos
        $request->validate([
            'name'=> ['required', 'string', 'min:3', 'max:45'],
            'location' => ['required', 'string', 'min:3', 'max:45'],
            'description' => ['nullable', 'string', 'min:5', 'max:255'],
        ]);


        // Guardar en la BDD los datos por medio de ELOQUENT
        $ward = Ward::create([
            "name" => $request['name'],
            "location" => $request['location'],
            "description" => $request['description']
        ]);


        // Se imprime el mensaje de exito
        return back()->with('status', 'Ward created successfully');
    }



    // Función para mostrar la vista y los datos de un solo pabellon
    public function show(Ward $ward)
    {
        return view('ward.show', ['ward' => $ward]);
    }




    // Función para mostrar la vista y los datos de un solo pabellon a través de un formulario
    public function edit(Ward $ward)
    {
        return view('ward.update', ['ward' => $ward]);
    }

    // Función para tomar los datos del formulario y actualizar en la BDD
    public function update(Request $request, Ward $ward)
    {

        // Validación de datos respectivos
        $request->validate([
            'name'=> ['required', 'string', 'min:3', 'max:45'],
            'location' => ['required', 'string', 'min:3', 'max:45'],
            'description' => ['nullable', 'string', 'min:5', 'max:255'],
        ]);

        // Se procede con la actualización de los datos por medio de Eloquent
        $ward->update([
            "name" => $request['name'],
            "location" => $request['location'],
            "description" => $request['description']
        ]);
        // Se imprime el mensaje de exito
        return back()->with('status', 'Ward updated successfully');
    }




    // Función para dar de baja a un pabellon en la BDD
    public function destroy(Ward $ward)
    {
        // Tomar el estado del pabellon
        $state = $ward->state;
        // Almacenar un mensaje para el estado
        $message = $state ? 'inactivated' : 'activated';

        // Validación para que no se de de baja si tiene asignado un guardia
        if ($this->verifyWardHasAssignedGuards($ward))
        {
            //https://laravel.com/docs/8.x/responses#redirecting-with-flashed-session-data
            return back()->with([
                'status' => "The ward $ward->name has assigned guard(s).",
                'color' => 'yellow'
            ]);
        }



        // Cambiar el estado del pabellon
        $ward->state = !$state;
        // Guardar los cambios
        $ward->save();
        // Se imprime el mensaje de exito
        return back()->with('status', "Ward $message successfully");
    }


    // Función para validar que un pabellon tiene o no un usuario asignado
    private function verifyWardHasAssignedGuards(Ward $ward)
    {
        return (bool)$ward->users->count();
    }




}
