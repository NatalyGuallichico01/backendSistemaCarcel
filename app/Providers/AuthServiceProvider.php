<?php

namespace App\Providers;

use App\Models\Report;
use App\Models\User;
use App\Policies\ReportPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */


    // https://laravel.com/docs/9.x/authorization#registering-policies

    protected $policies = [Report::class => ReportPolicy::class];







    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('manage-directors', function (User $user)
        {
            return $user->role->name === 'admin';
        });


        Gate::define('manage-guards', function (User $user)
        {
            return $user->role->name === 'admin';
        });


        Gate::define('manage-prisoners', function (User $user)
        {
            return $user->role->name === 'admin';
        });

        Gate::define('manage-wards', function (User $user)
        {
           return $user->role->name === 'director';
       });


       Gate::define('manage-jails', function (User $user)
       {
           return $user->role->name === 'director';
       });


       Gate::define('manage-assignment', function (User $user)
       {
            return $user->role->name === 'director';
       });





    }
}
