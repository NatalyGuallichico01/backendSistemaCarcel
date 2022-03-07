<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileAvatarController extends Controller
{

    // Función que se va a invocar en la vista para actualizar la imagen de perfil
    public function update(Request $request)
    {

        // Se procede a validar los datos
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,png,jpeg', 'max:512']
        ]);

        // Obtiene el usuario desde la petición
        $user = $request->user();

        // Se porcede a invocar la función del TRAIT
        $user->updateImage($request['image'], 'avatars');
        
       // Se imprime el mensaje de exito
        return back()->with('status', 'Avatar update successfully');
    }


}
