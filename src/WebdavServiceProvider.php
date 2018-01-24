<?php

namespace Ijin82\Flysystem\Webdav;

use Storage;
use Sabre\DAV\Client;
use League\Flysystem\Filesystem;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Illuminate\Support\ServiceProvider;

class AzureBlobServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('webdav', function ($app, $config) {
            $client = new Client($config);
            $adapter = new WebDAVAdapter($client);

            return new Filesystem($adapter);
        });
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
