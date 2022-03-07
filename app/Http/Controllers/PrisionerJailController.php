<?php

namespace App\Http\Controllers;

use App\Models\Jail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class PrisionerJailController extends Controller
{

    public function __construct()
    {
        $this->middleware('can:manage-assignment');
    }



    // Función para mostrar la vista principal y realizar la asignación de prisioneros a cárceles
    public function index()
    {
        // Obtener el rol guardia
        $prisoner_role = Role::where('name', 'prisoner')->first();


        // Obtener todos los usuarios que sean prisioneros
        $prisoners = $prisoner_role->users();



        if (request('search')) {
            $prisoners = $prisoners->where('username', 'like', '%' . request('search') . '%');
        }

        $prisoners = $prisoners
            ->orderBy('first_name', 'asc')
            ->orderBy('last_name', 'asc')
            ->paginate();

        // Función para cargar los prisioneros que cumplan la condición del filter
        // https://laravel.com/docs/8.x/eloquent#cursors
        $jails = Jail::orderBy('name', 'asc')->cursor()->filter(function ($jail)
        {
            return $jail->capacity > $jail->users->count() && $jail->state;
        });


        // Mandar a la vista los prisioneros y cárceles
        return view('assignment.prisoners-jails', [

            'prisoners' => $prisoners,

            'jails' => $jails->all()

        ]);
    }



    // Función para actualizar los prisioneros de las cárceles
    public function update(Request $request, User $user)
    {
        // Validación de datos respectivos
        $request->validate([
            'jail' => ['required', 'string', 'numeric', 'exists:jails,id']
        ]);

        // Se obtiene el usuario actual
        $prisoner = $user;


        // Función para validar que el prisionero no se asigne a la misma cárcel
        if ($this->verifyItIsTheSameJail($prisoner->jails->first(), $request['jail']))
        {
            return back()->with([
                'status' => 'The prisoner is already in that jail.',
                'color' => 'yellow'
            ]);
        }

        //A new user and jail relationship is created.
        $prisoner->jails()->sync($request['jail']);


        // Se imprime el mensaje de exito
        return back()->with('status', 'Assignment updated successfully');
    }

// Función para validar que el prisionero no se asigne a la misma cárcel
    private function verifyItIsTheSameJail(Jail|null $jail, string $jail_id): bool
    {
        return !is_null($jail) && $jail->id === (int)$jail_id;
    }



}
