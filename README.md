Sherlockode Advanced Form Bundle
================================

Powerful Symfony form components

## Prerequisites

This bundle requires Symfony 3.4+ and jQuery.

## Installation

### Step 1: Install SherlockodeAdvancedFormBundle

The best way to install this bundle is to rely on [Composer](https://getcomposer.org/):

```bash
$ composer require sherlockode/advanced-form-bundle
```

### Step 2: Enable the bundle

Enable the bundle in the kernel

```php
<?php
// config/bundles.php

return [
    // ...
    Sherlockode\AdvancedFormBundle\SherlockodeAdvancedFormBundle::class => ['all' => true],
];
```

### Step 3: Configure the bundle

Import the routing in `config/routes.yml`

```yaml
sherlockode_advanced_form:
    resource: "@SherlockodeAdvancedFormBundle/Resources/config/routing.yml"
```

### Step 4: Publish assets

You may use Webpack to import the JavaScript files or use the `assets` command.

```bash
$ php bin/console assets:install --symlink public
```

## Next steps

### Ajax uploader

[Create a single file upload form](Resources/doc/single_file_upload.md)

[Use the temporary upload mode](Resources/doc/temporary_upload.md)

[Multiple files upload form](Resources/doc/multiple_files_upload.md)

[Options overview](Resources/doc/options_overview.md)

### Dependent entity form type

[Overview](Resources/doc/dependent_entity.md)
