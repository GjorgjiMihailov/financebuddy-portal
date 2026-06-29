<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GoogleDrive::class, function () {
            $client = new GoogleClient();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setAccessType('offline');
            $client->addScope(GoogleDrive::DRIVE);
            $client->fetchAccessTokenWithRefreshToken(config('services.google.refresh_token'));
            return new GoogleDrive($client);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerGoogleDrive();
    }

    protected function registerGoogleDrive(): void
    {
        Storage::extend('google', function ($app, $config) {
            $service = $app->make(GoogleDrive::class);
            $adapter = new GoogleDriveAdapter($service, $config['folder_id']);
            $driver  = new Filesystem($adapter);

            return new FilesystemAdapter($driver, $adapter, $config);
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
