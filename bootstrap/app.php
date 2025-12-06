<?php

require_once __DIR__ . '/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

class_alias('Maatwebsite\Excel\Facades\Excel', 'Excel');
$app->withFacades();

$app->withEloquent();

app('translator')->setLocale('id');

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/
$app->instance('path.config', app()->basePath() . DIRECTORY_SEPARATOR . 'config');

$app->configure('app');
$app->configure('cors');
$app->configure('auth');
$app->configure('jwt');
$app->configure('cache');
$app->configure('sentry');
$app->configure('production');
$app->configure('pos');
$app->configure('scout');
$app->configure('tinker');
$app->configure('queue');
$app->configure('inventory');
$app->configure('firebase');
$app->configure('mail');
$app->configure('management');

$app->alias('mail.manager', Illuminate\Mail\MailManager::class);
$app->alias('mail.manager', Illuminate\Contracts\Mail\Factory::class);

$app->alias('mailer', Illuminate\Mail\Mailer::class);
$app->alias('mailer', Illuminate\Contracts\Mail\Mailer::class);
$app->alias('mailer', Illuminate\Contracts\Mail\MailQueue::class);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    Fruitcake\Cors\HandleCors::class,
]);

$app->routeMiddleware([
    'activity'      => App\Http\Middleware\Activity::class,
    'api'           => App\Http\Middleware\Api::class,
    'auth'          => App\Http\Middleware\Authenticate::class,
    'log'           => App\Http\Middleware\Log::class,
    'permission'    => App\Http\Middleware\Permission::class,
    'xss'           => App\Http\Middleware\XSSProtection::class
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);
$app->register(Fruitcake\Cors\CorsServiceProvider::class);
$app->register(SwooleTW\Http\LumenServiceProvider::class);
$app->register(\Rap2hpoutre\LaravelLogViewer\LaravelLogViewerServiceProvider::class);
$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register('Sentry\Laravel\ServiceProvider');
$app->register('Sentry\Laravel\Tracing\ServiceProvider');
$app->register('Maatwebsite\Excel\ExcelServiceProvider');
$app->register(Laravel\Scout\ScoutServiceProvider::class);
$app->register(\Laravel\Tinker\TinkerServiceProvider::class);
$app->register(Illuminate\Mail\MailServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers\Api',
], function ($router) {
    require __DIR__ . '/../routes/web.php';

    # API V1
    $router->group([
        'namespace' => 'V1',
        'middleware' => ['api', 'xss', 'activity'],
        'prefix' => 'api/v1',
    ], function ($router) {
        # Auth
        require __DIR__ . '/../routes/api/v1/auth.php';

        # Management
        $router->group([
            'namespace' => 'Management',
            'prefix'    => 'management',
        ], function ($router) {
            require __DIR__ . '/../routes/api/v1/management.php';
        });

        # Inventory
        $router->group([
            'namespace' => 'Inventory',
            'prefix'    => 'inventory',
        ], function ($router) {
            require __DIR__ . '/../routes/api/v1/inventory.php';
        });

        # Pos
        $router->group([
            'namespace' => 'Pos',
            'prefix'    => 'pos',
        ], function ($router) {
            require __DIR__ . '/../routes/api/v1/pos.php';
        });

        # Distribution
        $router->group([
            'namespace' => 'Distribution',
            'prefix'    => 'distribution',
        ], function ($router) {
            require __DIR__ . '/../routes/api/v1/distribution.php';
        });

        # Production
        $router->group([
            'namespace' => 'Production',
            'prefix'    => 'production',
        ], function ($router) {
            require __DIR__ . '/../routes/api/v1/production.php';
        });

        # Purchasing
        $router->group([
            'namespace' => 'Purchasing',
            'prefix'    => 'purchasing',
        ], function ($router) {
            require __DIR__ . '/../routes/api/v1/purchasing.php';
        });

        # Reporting
        $router->group([
            'namespace' => 'Reporting',
            'prefix'    => 'reporting',
        ], function ($router) {
            require __DIR__ . '/../routes/api/v1/reporting.php';
        });
    });
});


return $app;
