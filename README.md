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
    git=git://github.com/genemu/ArtseldOpeninviterBundle.git
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

Add resource link to imports section in application config.yml:

``` yaml
# app/config/config.yml

imports:
    - { resource: '@ArtseldOpeninviterBundle/Resources/config/config.yml' }
```

### Step 5: Initialize assets

``` bash
$ php app/console assets:install web/

## Copyright

ArtseldOpeninviterBundle includes [OpenInviter](http://openinviter.com/) original code.
One or more classes of this bundle based on [OpenInviter](http://openinviter.com/) original code.
