<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Notifications\RegisteredUserNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DirectorController extends Controller
{


    public function __construct()
    {
        $this->middleware('can:manage-directors');

        $this->middleware('active.user')->only('edit', 'update');
        
        $this->middleware('verify.user.role:director')->except('index', 'create', 'store', 'search');
    }




    // Función para mostrar la vista principal de todo los directores
    public function index()
    {
        // Traer el rol director
        $director_role = Role::where('name', 'director')->first();
        // Obtener todos los usuarios que sean directores
        $directors = $director_role->users();

        if (request('search'))
        {
           $directors = $directors->where('username', 'like', '%' . request('search') . '%');
        }

        $directors = $directors->orderBy('first_name', 'asc')
            ->orderBy('last_name', 'asc')
            ->paginate(5);


        // Mandar a la vista los usuarios que sean directores
        return view('director.index', compact('directors'));
    }




    // Función para mostrar la vista del formulario
    public function create()
    {
        return view('director.create');
    }


    // Función para tomar los datos del formulario y guardar en la BDD
    public function store(Request $request)
    {

        // Validación de datos respectivos
        $request->validate([
        'first_name' => ['required', 'string', 'min:3', 'max:35'],
        'last_name' => ['required', 'string', 'min:3', 'max:35'],
        'username' => ['required', 'string', 'min:5', 'max:20', 'unique:users'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        // Validar que la fecha de cumplaeños sea mayor de 18 y menor a 70 años
        'birthdate' => ['required', 'string', 'date_format:d/m/Y',
            'after_or_equal:' . date('Y-m-d', strtotime('-70 years')),
            'before_or_equal:' . date('Y-m-d', strtotime('-18 years')),
        ],
        'personal_phone' => ['required', 'numeric', 'digits:10'],
        'home_phone' => ['required', 'numeric', 'digits:9'],
        'address' => ['required', 'string', 'min:5', 'max:50']
    ]);

        // Invocar a la función para generar una contraseña
        $password_generated = $this->generatePassword();
        // Traer el rol director
        $director_role = Role::where('name', 'director')->first();

        $director = $director_role->users()->create([
            'first_name' => $request['first_name'],

            'last_name' => $request['last_name'],

            'username' => $request['username'],

            'email' => $request['email'],

            'birthdate' => $this->changeDateFormat($request['birthdate']),

            'personal_phone' => $request['personal_phone'],

            'home_phone' => $request['home_phone'],

            'address' => $request['address'],

            'password' => Hash::make($password_generated),
        ]);

        // Se crear el avatar y se almacena en la BDD por medio de ELOQUENT y su relación
        $director->image()->create(['path' => $director->generateAvatarUrl()]);

        // Se procede a enviar una notificación al correo
        $director->notify(
            new RegisteredUserNotification(
                $director->getFullName(),
                $director_role->name,
                $password_generated
            )
        );
        // Se imprime el mensaje de exito
        return back()->with('status', 'Director created successfully');
    }


   // Función para mostrar la vista y los datos de un solo director
    public function show(User $user)
    {
        return view('director.show', ['director' => $user]);
    }



    // Función para mostrar la vista y los datos de un solo director a través de un formulario
    public function edit(User $user)
    {
        return view('director.update', ['director' => $user]);
    }



    // Función para tomar los datos del formulario y actualizar en la BDD
    public function update(Request $request, User $user)
    {

        // Obtener el model del usuario
        $userRequest = $request->user;

        // Validación de datos respectivos
        $request->validate([
            'first_name' => ['required', 'string', 'min:3', 'max:35'],
            'last_name' => ['required', 'string', 'min:3', 'max:35'],

            'username' => ['required', 'string', 'min:5', 'max:20',
                Rule::unique('users')->ignore($userRequest),
            ],


            'email' => ['required', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($userRequest),
            ],

            // Validar que la fecha de cumplaeños sea mayor de 18 y menor a 70 años
            'birthdate' => ['nullable', 'string', 'date_format:d/m/Y',
                'after_or_equal:' . date('Y-m-d', strtotime('-70 years')),
                'before_or_equal:' . date('Y-m-d', strtotime('-18 years')),
            ],
            'personal_phone' => ['required', 'numeric', 'digits:10'],
            'home_phone' => ['required', 'numeric', 'digits:9'],
            'address' => ['required', 'string', 'min:5', 'max:50'],
        ]);

        // Se obtiene el email antiguo del usuario
        $old_email = $user->email;
        // Se obtiene el modelo del usuario
        $director = $user;


        $director->update([
        'first_name' => $request['first_name'],
        'last_name' => $request['last_name'],
        'username' => $request['username'],
        'email' => $request['email'],
        'birthdate' => $this->changeDateFormat($request['birthdate']),
        'personal_phone' => $request['personal_phone'],
        'home_phone' => $request['home_phone'],
        'address' => $request['address'],
        ]);

        // Se procede con la actualización del avatar del usuario
        $director->updateUIAvatar($director->generateAvatarUrl());

        // Función para verificar si el usuario cambio el email
        $this->verifyEmailChange($director, $old_email);
        // Se imprime el mensaje de exito
        return back()->with('status', 'Director updated successfully');
    }

    // Función para dar de baja a un director en la BDD
    public function destroy(User $user)
    {
        // Tomar el modelo del usuario
        $director = $user;
        // Tomar el estado del director
        $state = $director->state;
        // Almacenar un mensaje para el estado
        $message = $state ? 'inactivated' : 'activated';
        // Cambiar el estado del usuario
        $director->state = !$state;
        // Guardar los cambios
        $director->save();
        // Se imprime el mensaje de exito
        return back()->with('status', "Director $message successfully");
    }



    // Función para generar una contraseña
    public function generatePassword()
    {
        $characters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*?";
        $length = 8;
        $count = mb_strlen($characters);
        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($characters, $index, 1);
        }
        return $result;
    }



    public function changeDateFormat(string $date, string $date_format='d/m/Y', string $expected_format = 'Y-m-d')
    {
        return Carbon::createFromFormat($date_format, $date)->format($expected_format);
    }



    private function verifyEmailChange(User $director, string $old_email)
    {
        if ($director->email !== $old_email)
        {
            $password_generated = $this->generatePassword();

            $director->password = Hash::make($password_generated);

            $director->save();

            $director->notify(
                new RegisteredUserNotification(
                    $director->getFullName(),
                    $director->role->name,
                    $password_generated
                )
            );
        }
    }


}
