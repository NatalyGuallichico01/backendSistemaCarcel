<?php
 
namespace App\Providers;
 
use Storage;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;
 
class DropboxServiceProvider extends ServiceProvider
{
    // ...
    public function boot()
    {
        Storage::extend('dropbox', function ($app, $config) {
            $client = new DropboxClient(
                $config['authorization_token']
            );
 
            return new Filesystem(new DropboxAdapter($client));
        });
    }
}