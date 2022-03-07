<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Image extends Model
{

    protected $fillable = [
        'path',
    ];


    use HasFactory;

    // Relación polimórfica uno a uno
    // Una imagen le pertenece a un usuario, reporte, pabellón y cárcel.
    public function imageable()
    {
        return $this->morphTo();
    }

    //Esta función permite obtener la url de la imagen
    public function getUrl()
    {
        return Str::startsWith($this->path, 'https://')

            ? $this->path
            
            : Storage::url($this->path);
    }


}
