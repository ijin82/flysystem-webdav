# Flysystem Webdav service provider for Laravel 5 

## What is it for?
That is a ready-to-use service provider for [flysystem-webdav](https://github.com/thephpleague/flysystem-webdav)

## Why i need that?
You don't need that for sure, you can change service provider by yourself whatever you like (with installed [flysystem-webdav](https://github.com/thephpleague/flysystem-webdav))

## Okay, I'm quite lazy guy, how to install this solution?
Now you have to be patient. It takes a couple minutes.  
- This is nginx-related solution (if someone ready to test Apache or lighttpd or etc. - welcome, let's extend description), so we have to set up nginx at first
```bash
sudo apt-get install nginx nginx-common nginx-extras
```

- Set up nginx webdav host at first.  

### v1. Host for webdav server
```nginx
server {
  # ssl
  #listen 443;
  #ssl on;
  #ssl_certificate      /path/to/pem/file.pem;
  #ssl_certificate_key  /path/to/key/file.key;

  listen 80;
  server_name host.name.com;

  location / {
    limit_except GET {
      auth_basic "Auth";
      auth_basic_user_file /path/to/htpasswd/file/.htpasswd;
    }

    # root folder
    root /path/to/webdav/root;
    # max upload file size 
    client_max_body_size 100m;
    # chmod for uploaded files
    dav_access group:rw all:r;
    # methods for upload
    dav_methods PUT DELETE MKCOL COPY MOVE;
    dav_ext_methods PROPFIND OPTIONS;
    # create full file path on upload (no need to create folders)
    create_full_put_path on;
    # autoindex for webdav
    autoindex off;
    autoindex_exact_size off;
    autoindex_localtime on;
    charset utf-8;
  }
}
```

Here we have nginx config (v1) for webdav host without subfolder for upload. Create path whatever you like from root. For all http requests that is not `GET` type, service user have to be authorized ([limit_except](https://nginx.ru/en/docs/http/ngx_http_core_module.html#limit_except) directive). 
### REM
If you have never used `.htpasswd` before, you have to install apache utils 
```bash
sudo apt-get install apache2-utils
```
And then go to [manual page](https://httpd.apache.org/docs/2.4/programs/htpasswd.html)

### v2. Host for webdav server with subfolder
```nginx
server {
  # ssl
  #listen 443;
  #ssl on;
  #ssl_certificate      /path/to/pem/file.pem;
  #ssl_certificate_key  /path/to/key/file.key;

  listen 80;
  server_name host.name.com;

  location / {
    root /path/to/webdav/root/or/another/folder;
  }

  location /upload {
    limit_except GET {
      auth_basic "Auth";
      auth_basic_user_file /path/to/htpasswd/file/.htpasswd;
    }

    # root folder
    root /path/to/webdav/root;
    # max upload file size 
    client_max_body_size 100m;
    # chmod for uploaded files
    dav_access group:rw all:r;
    # methods for upload
    dav_methods PUT DELETE MKCOL COPY MOVE;
    dav_ext_methods PROPFIND OPTIONS;
    # create full file path on upload (no need to create folders)
    create_full_put_path on;
    # autoindex for webdav
    autoindex off;
    autoindex_exact_size off;
    autoindex_localtime on;
    charset utf-8;
  }
}
```

Here we have nginx config (v2) for webdav host with subfolder for upload. Create path whatever you like from subfolder (`upload` folder name is not needed, change that if you like). 

- Now we have to create folder for webdav service with same access rights as nginx have
```bash
mkdir /path/to/webdav/root
chown -R www-data:www-data /path/to/webdav/root
```

- Ok, now test your nginx config and restart when ready
```bash
sudo nginx -t
sudo nginx -s relaod
```

- We've done with nginx, let's get deal with PHP side. Let's install this module for your Laravel app.
```bash
composer require ijin82/flysystem-webdav
```

- Now we have to set up service provider. Open your `config/app.php` and add new provider to providers section.
```php
return [
    //...
    'providers' => [
        //...
        Ijin82\Flysystem\Webdav\WebdavServiceProvider::class,
        //...
    ],
    //...
];
```

- Let's configure new filesystem. Open your `config/filesystems.php` and add new fs config like this.
```php
return [
    //...
    'avatars' => [
        'driver' => 'webdav',
        'baseUri' => 'http://host.name.com',
        'path_prefix' => 'avatar/',
        'path_alias' => '',
        'userName' => 'webdav_user_login',
        'password' => 'webdav_user_password',
    ],
    //...
];
```

For sure, you have to use `env` hepler for config, here is just an example config without it, just fyi.  
Example description:
- `driver` - new webdav driver (check service provider source code)
- `baseUri` - your webdav host name
- `path_prefix` - files folder prefix. If you plan to upload files for nginx config `v2`, then you have to prefix your folder name with upload folder name, meaning `upload/avatar/` for `v2` nginx config, and simply `avatar/` for `v1` config, because this parameter using for file upload and in `v2` config we have to hit upload folder according to your nginx config.
- `path_alias` - **OPTIONAL** parameter, in case if you like to access your upladed files by short path, for example your original upload prefix is `upload/client/avatars/` and you want client-side file path looks like `/av/file1.jpg`. Then you have to create symlink from `upload/client/avatars/` to `av/` folder on your server (under your nginx root) and set up `path_alias` parameter as `av/`. By default, your `path_prefix` parameter will be user for uri generation.
- `userName` - user name from your `.htpasswd` used for file upload
- `password` - user password from your `.htpasswd` used for file upload

### Upload example
```php
public function avatarUpload(Request $request)
{
    //...
    $file = $request->file('avatar_file');
    //... check file type, build name, save name in DB, whatever you like
    $fileName = $file->getClientOriginalName(); // for example
    // https://laravel.com/docs/5.5/requests#storing-uploaded-files
    $file->storeAs('subfolder-name-or-empty', $fileName, ['disk' => 'avatars']);
    //...
    // save file name or logic to build that
    //...
}
```

### Get file url , blade example
```blade
<a href="{{ Storage::disk('avatars')->url($fileName) }}" target="_blank">.{{ $ext }}</a>
```

### Get file url , code example
```php
    //... fileName logic
    $fileUrl = Storage::disk('avatars')->url($fileName);
```

### Delete file example
```php
    Storage::disk('avatars')->delete($fileName);
```

### Delete folder example
```php
    // WARNING, all files inside that also will be deleted
    Storage::disk('avatars')->deleteDir('dir-name/or/path');
```

Check out original [flysystem docs](http://flysystem.thephpleague.com)

Feel free to send pull requests and bug reports.