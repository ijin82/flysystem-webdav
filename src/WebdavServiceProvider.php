<?php

namespace Ijin82\Flysystem\Webdav;

use Storage;
use Sabre\DAV\Client;
use League\Flysystem\Filesystem;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Illuminate\Support\ServiceProvider;

class WebDAVAdapterExt extends WebDAVAdapter {

    public function __construct($client, $fsConfig)
    {
        $this->fsConfig = $fsConfig;
        parent::__construct($client);
    }

    public function getUrl($path)
    {
        if (!empty($this->fsConfig['uriPrefix'])) {
            // with this feature you can use symlink to folder
            return $this->fsConfig['baseUri'] . $this->fsConfig['uriPrefix'] . $path;
        } else {
            return $this->fsConfig['baseUri'] . $this->fsConfig['path_prefix'] . $path;
        }
    }

    public function deleteDir($dirname)
    {
        $location = $this->applyPathPrefix($this->encodePath($dirname));

        try {
            $this->client->request('DELETE', $location . '/');

            return true;
        } catch (NotFound $e) {
            return false;
        }
    }
}

class WebdavServiceProvider extends ServiceProvider
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
            $adapter = new WebDAVAdapterExt($client, $config);
            if (!empty($config['path_prefix'])) {
                $adapter->setPathPrefix($config['path_prefix']);
            }

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
