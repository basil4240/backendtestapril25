<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    // ...existing code...
    
    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        // ...existing middleware...
        'tenant.isolation' => \App\Http\Middleware\EnsureTenantIsolation::class,
        'role.admin' => \App\Http\Middleware\AdminAccess::class,
        'role.manager' => \App\Http\Middleware\ManagerAccess::class,
        'role.employee' => \App\Http\Middleware\EmployeeAccess::class,
    ];
    
    // ...rest of the class...
}