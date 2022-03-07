<?php

namespace App\Traits;

use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HasImage
{

    // Función para eliminar de la BDD
    private function deletePreviousImage(string $previous_image)
    {
        Storage::delete($previous_image);
    }



    // Función para guardar la imagen en la BDD
    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }


    // Función para guardar la imagen en la BDD
    public function storeImage(UploadedFile $new_image, string $directory = 'images')
    {
        $image = new Image(['path' => $new_image->store($directory, 'dropbox')]);
        
        $this->image()->save($image);
    }



    // Función para actualizar la imagen y eliminar la imagen anterior del directorio public
    public function updateImage(UploadedFile $new_image, string $directory = 'images')
    {
        $previous_image = $this->image;

        if ($previous_image)
        {
            $previous_image_path = $previous_image->path;

            $previous_image->path = $new_image->store($directory, 'dropbox');

            $previous_image->save();

            Storage::disk('public')->delete($previous_image_path);
        }
        else
        {
            $this->storeImage($new_image, $directory);
        }
    }

}


