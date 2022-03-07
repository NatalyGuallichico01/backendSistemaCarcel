<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{

    // https://laravel.com/docs/9.x/authorization#authorizing-resource-controllers
    public function __construct()
    {
        $this->authorizeResource(Report::class, 'report');
    }




    // Función para mostrar la vista principal de todos los reportes
    public function index()
    {
        // Obtener el usuario que inicio sesión
        $guard = Auth::user();

        // Obtener todos los reportes asociados al usuario
        $reports = $guard->reports();

        if (request('search'))
        {
            // https://laravel.com/docs/8.x/queries#basic-where-clauses
            $reports = $reports->where('title', 'like', '%' . request('search') . '%');
        }

        $reports = $reports->orderBy('title', 'asc')
            ->paginate();

        // Mandar a la vista todos los reportes
        return view('report.index', ['reports' => $reports]);
    }





    // Función para mostrar la vista del formulario
    public function create()
    {
        return view('report.create');
    }




    // Función para tomar los datos del formulario y guardar en la BDD
    public function store(Request $request)
    {
        // Validación de datos respectivos
        $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:45'],
            'description' => ['required', 'string', 'min:5', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,png,jpeg', 'max:512'], //max image size is 512 kb
        ]);




        // Obtener el usuario que inicio sesión
        $guard = Auth::user();


        // Obtener un nuevo modelo Reporte
        $report = new Report();

        $report->title = $request['title'];

        $report->description = $request['description'];

        // Guardar en la BDD los datos por medio de ELOQUENT y su relación
        $guard->reports()->save($report);

        // Se procede asignar la imagen al reporte
        if ($request->has('image'))
        {
            $report->storeImage($request['image'], 'reports', 'images');
        }

        // Se imprime el mensaje de exito
        return back()->with('status', 'Report created successfully');
    }



    // Función para mostrar la vista y los datos de un solo director
    public function show(Report $report)
    {
        return view('report.show', ['report' => $report]);
    }





    // Función para mostrar la vista y los datos de un solo director a través de un formulario
    public function edit(Report $report)
    {
        return view('report.update', ['report' => $report]);
    }



    // Función para tomar los datos del formulario y actualizar en la BDD
    public function update(Request $request, Report $report)
    {
        // Validación de datos respectivos
        $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:45'],
            'description' => ['required', 'string', 'min:5', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,png,jpeg', 'max:512'], //max image size is 512 kb
        ]);



        // Se procede con la actualización de los datos
        $report->title = $request['title'];

        $report->description = $request['description'];

        $report->save();

        // Se procede con la actualización de la imagen
        if ($request->has('image'))
        {
            $report->updateImage($request['image'], 'reports', 'images');
        }

        // Se imprime el mensaje de exito
        return back()->with('status', 'Report updated successfully');
    }

    // Función para dar de baja a un reporte
    public function destroy(Report $report)
    {
        // Tomar el estado del reporte
        $state = $report->state;

        // Almacenar un mensaje para el estado
        $message = $state ? 'inactivated' : 'activated';

        // Cambiar el estado del usuario
        $report->state = !$state;


        // Guardar los cambios
        $report->save();


        // Se imprime el mensaje de exito
        return back()->with('status', "Report $message successfully");
    }


}
