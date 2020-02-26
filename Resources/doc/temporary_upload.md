Using the temporary upload mode
===============================

The temporary upload will let a user upload files without actually updating the entity from the form.
The files are uploaded in a temporary storage and associated to a token that is used to retrieve them at form submission.

Entity
------

The name of the file and the secret token are stored in the database, so you need to define an entity for this.
The entity must implement TemporaryUploadedFileInterface, you can use the provided class TemporaryUploadedFile and just
define the entity mapping there.

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sherlockode\AdvancedFormBundle\Model\TemporaryUploadedFile as BaseUploadedFile;

/**
 * Class UploadedFile
 *
 * @ORM\Entity
 * @ORM\Table(name="sherlockode_afb_uploaded_file")
 */
class UploadedFile extends BaseUploadedFile
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="file_key", type="string")
     */
    protected $key;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string")
     */
    protected $token;
}
```

Configuration
-------------

The name of the entity and the target directory for the file upload must be provided in the configuration:
```yaml
sherlockode_advanced_form:
    tmp_uploaded_file_class: App\Entity\UploadedFile
    tmp_uploaded_file_dir: '%kernel.project_dir%/var/uploads/sherlockode_afb_tmp'
```

Just change the upload_mode in the form definition to use the temporary mode:
```php
<?php 
use Sherlockode\AdvancedFormBundle\Form\Type\FileType;
//...
$builder
    ->add('imageFile', FileType::class, [
        //...
        'upload_mode' => 'temporary',
    ])
;
```

Remove files from temporary storage
-------------

Sometimes user uploads a file and never submit the entity form. To remove all non-used temporary files, you can run
this command:

```bash
$ php bin/console afb:cleanup:tmpfiles
```

Use the `older-than` option to only remove older files:

```bash
$ php bin/console afb:cleanup:tmpfiles --older-than=3d
```
