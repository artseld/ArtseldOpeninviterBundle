ArtseldOpeninviterBundle
========================

The `ArtseldOpeninviterBundle` integrates the [OpenInviter](http://openinviter.com/)
PHP library with Symfony2. This means easy-to-implement invitation mechanism from many social networks and mail providers
in your Symfony2 application.

## Installation

Installation is quick and easy, 5 steps process

1. Download ArtseldOpeninviterBundle
2. Configure the Autoloader
3. Enable the bundle
4. Minimal configuration
5. Initialize assets

### Step 1: Download ArtseldOpeninviterBundle

Add the following entries to the deps in the root of your project file:

```
[ArtseldOpeninviterBundle]
    git=git://github.com/artseld/ArtseldOpeninviterBundle.git
    target=bundles/Artseld/OpeninviterBundle
```

Run the vendors script to download the bundle:

``` bash
$ php bin/vendors install
```

### Step 2: Configure the Autoloader

If it is the first Artseld bundle you install in your Symfony2 project,
you need to add the Artseld namespace to your autoloader:

``` php
<?php
// app/autoload.php

$loader->registerNamespaces(array(
    // ...
    'Artseld' => __DIR__.'/../vendor/bundles',
));
```

### Step 3: Enable the bundle

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Artseld\OpeninviterBundle\ArtseldOpeninviterBundle(),
    );
}
```

### Step 4: Minimal configuration

Add configuration into your application config.yml (recommended):

``` yaml
artseld_openinviter:
    username: "USERNAME"
    private_key: "API-KEY"
    plugins_cache_time: "1800"
    plugins_cache_file: "oi_plugins.php"
    cookie_path: "/tmp"
    local_debug: "on_error"
    remote_debug: ""
    hosted: ""
    proxies: []
    stats: ""
    stats_user: ""
    stats_password: ""
    update_files: "1"
    transport: "wget"
```

or add resource link to imports section in application config.yml:

``` yaml
# app/config/config.yml

imports:
    - { resource: '@ArtseldOpeninviterBundle/Resources/config/config.yml' }
```

Add your USERNAME and API-KEY and edit another configuration settings if necessary.

Finally, add route to application routing.yml (example):

``` yaml
# app/config/routing.yml

ArtseldOpeninviterBundle:
    resource: "@ArtseldOpeninviterBundle/Resources/config/routing.yml"
    prefix:   /open-inviter
```

You can use another url prefix.

### Step 5: Initialize assets

``` bash
$ php app/console assets:install web/
```

## Copyright

ArtseldOpeninviterBundle includes [OpenInviter](http://openinviter.com/) original code.
One or more classes of this bundle based on [OpenInviter](http://openinviter.com/) original code.
