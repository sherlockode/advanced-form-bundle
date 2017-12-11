Sherlockode Advanced Form Bundle
================================

Powerful Symfony form components

## Prerequisites

This version of the bundle requires Symfony 3.3+, VichUploaderBundle 1.6+, and jQuery.

## Installation

### Step 1: Install SherlockodeAdvancedFormBundle

The best way to install this bundle is to rely on [Composer](https://getcomposer.org/):

``` bash
$ composer require sherlockode/advanced-form-bundle
```

### Step 2: Enable the bundle

Enable the bundle in the kernel

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Sherlockode\AdvancedFormBundle\SherlockodeAdvancedFormBundle(),
    );
}
```

### Step 3: Configure the bundle

Import the routing in app/config/routing.yml

``` yaml
sherlockode_advanced_form:
    resource: '@SherlockodeAdvancedFormBundle/Controller/'
    type: annotation
```

### Step 4: Publish assets

```bash
$ php bin/console assets:install --symlink web
```

## Next steps

### Ajax uploader

[Create a single file upload form](Resources/doc/single_file_upload.md)

[Multiple files upload form](Resources/doc/multiple_files_upload.md)

[Options overview](Resources/doc/options_overview.md)
