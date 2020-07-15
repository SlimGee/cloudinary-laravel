<?php

namespace CloudinaryLabs;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use CloudinaryLabs\Commands\BackupFilesCommand;
use CloudinaryLabs\Commands\DeleteFilesCommand;
use CloudinaryLabs\Commands\FetchFilesCommand;
use CloudinaryLabs\Commands\GenerateArchiveCommand;
use CloudinaryLabs\Commands\RenameFilesCommand;
use CloudinaryLabs\Commands\UploadFileCommand;


/**
 * Class CloudinaryServiceProvider
 * @package CloudinaryLabs
 */
class CloudinaryServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootMacros();
        $this->bootResources();
        $this->bootDirectives();
        $this->bootComponents();
        $this->bootCommands();
        $this->bootPublishing();
        $this->bootCloudinaryDriver();
    }

    /**
     * Boot the package macros that extends Laravel Uploaded File API.
     *
     * @return void
     */
    protected function bootMacros()
    {
        UploadedFile::macro(
            'storeOnCloudinary',
            function ($folder = null) {
                return resolve(CloudinaryEngine::class)->uploadFile($this->getRealPath(), ['folder' => $folder]);
            }
        );

        UploadedFile::macro(
            'storeOnCloudinaryAs',
            function ($folder = null, $publicId = null) {
                return resolve(CloudinaryEngine::class)->uploadFile(
                    $this->getRealPath(),
                    ['folder' => $folder, 'public_id' => $publicId]
                );
            }
        );
    }

    /**
     * Boot the package resources.
     *
     * @return void
     */
    protected function bootResources()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'cloudinary');
    }

    /**
     * Boot the package directives.
     *
     * @return void
     */
    protected function bootDirectives()
    {
        Blade::directive(
            'cloudinaryJS',
            function () {
                return "<?php echo view('cloudinary::js'); ?>";
            }
        );
    }

    /**
     * Boot the package components.
     *
     * @return void
     */
    protected function bootComponents()
    {
        Blade::component('cloudinary::components.button', 'cld-upload-button');
        Blade::component('cloudinary::components.image', 'cld-image');
        Blade::component('cloudinary::components.video', 'cld-video');
    }

    protected function bootCommands()
    {
        /**
         * Register Laravel Cloudinary Artisan commands
         */
        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    BackupFilesCommand::class,
                    UploadFileCommand::class,
                    FetchFilesCommand::class,
                    RenameFilesCommand::class,
                    GenerateArchiveCommand::class,
                    DeleteFilesCommand::class
                ]
            );
        }
    }

    /**
     * Boot the package's publishable resources.
     *
     * @return void
     */
    protected function bootPublishing()
    {
        if ($this->app->runningInConsole()) {
            $config = dirname(__DIR__) . '/config/cloudinary.php';

            $this->publishes(
                [
                    $config => $this->app->configPath('cloudinary.php'),
                ],
                'laravel-cloudinary-config'
            );

            $this->publishes(
                [
                    __DIR__.'/../database/migrations' => $this->app->databasePath('migrations'),
                ],
                'laravel-cloudinary-migration'
            );
        }
    }

    protected function bootCloudinaryDriver()
    {
        $this->app['config']['filesystems.disks.cloudinary'] = ['driver' => 'cloudinary'];

        Storage::extend(
            'cloudinary',
            function ($app, $config) {
                return new Filesystem(new CloudinaryAdapter(config('cloudinary.cloud_url')));
            }
        );
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        // Register the service the package provides.
        $this->app->singleton(
            CloudinaryEngine::class,
            function ($app) {
                return new CloudinaryEngine();
            }
        );
    }
}