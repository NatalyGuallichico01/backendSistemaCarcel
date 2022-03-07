<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Http\Request;

class GuardWardController extends Controller
{


    public function __construct()
    {
        $this->middleware('can:manage-assignment');
    }




    // Determinar una regla que permita tener 2 guardias por pabellón
    private int $allowed_number_of_guards_per_ward = 2;



    // Función para mostrar la vista principal y realizar la asignación de guardias a pabellones
    public function index()
    {
        // Obtener el rol guardia
        $guard_role = Role::where('name', 'guard')->first();

        // Obtener todos los usuarios que sean guardias
        $guards = $guard_role -> users();

        if (request('search'))
        {
            $guards = $guards->where('username', 'like', '%' . request('search') . '%');
        }

        $guards = $guards
            ->orderBy('first_name', 'asc')
            ->orderBy('last_name', 'asc')
            ->paginate();


        // Función para cargar los pabellones que cumplan la condición del filter

        // https://laravel.com/docs/8.x/eloquent#cursors

        $wards = Ward::orderBy('name', 'asc')->cursor()->filter(function ($ward)
        {
           return $this -> allowed_number_of_guards_per_ward > $ward -> users -> count() && $ward -> state;
        });



        // Mandar a la vista los pabellones y guardias
        return view('assignment.guards-wards', [

            'guards' => $guards,

            'wards' => $wards->all()
        ]);
    }

    // Función para actualizar los guardias de los pabellones
    public function update(Request $request, User $user)
    {
        // Validación de datos respectivos
        $request->validate([
            //https://laravel.com/docs/8.x/validation#rule-exists
            'ward' => ['required', 'string', 'numeric', 'exists:wards,id']
        ]);

        // Se obtiene el usuario actual
        $guard = $user;

        // Función para validar que el guardia no se asigne al mismo pabellon
        if ($this->verifyItIsTheSameWard($guard->wards->first(), $request['ward']))
        {
            return back()->with([
                'status' => 'The guard is already in that ward.',
                'color' => 'yellow'
            ]);
        }

        // A new user and ward relationship is created.
        $guard->wards()->sync($request['ward']);

        // Se imprime el mensaje de exito
        return back()->with('status', 'Assignment updated successfully');
    }

    // Función para validar que el guardia no se asigne al mismo pabellon
    private function verifyItIsTheSameWard(Ward | null $ward , string $ward_id)
    {
        return !is_null($ward) && $ward->id === (int)$ward_id;
    }
    


}
