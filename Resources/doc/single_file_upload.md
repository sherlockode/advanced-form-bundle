Create a single file upload form
================================

To create a single file upload form, we have to set an entity with Vich uploader annotations

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity
 * @Vich\Uploadable
 */
class People
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $firstName;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $lastName;

    /**
     * @Vich\UploadableField(mapping="media_image", fileNameProperty="imageName", size="imageSize")
     *
     * @var File
     */
    private $imageFile;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    private $imageName;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @var integer
     */
    private $imageSize;

    /**
     * @ORM\Column(type="datetime")
     *
     * @var \DateTime
     */
    private $updatedAt;

    public function getId()
    {
        return $this->id;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function setImageFile(File $image = null)
    {
        $this->imageFile = $image;

        if ($image) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getImageFile()
    {
        return $this->imageFile;
    }

    public function setImageName($imageName)
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getImageName()
    {
        return $this->imageName;
    }

    public function setImageSize($imageSize)
    {
        $this->imageSize = $imageSize;

        return $this;
    }

    public function getImageSize()
    {
        return $this->imageSize;
    }
}
```
Then, we have to configure the way the file will be uploaded. Add in your config.yml file:
```yaml
sherlockode_advanced_form:
    storages:
        picture:    # name of the storage
            filesystem:  # type of storage
                path: '%kernel.project_dir%/var/uploads/pictures'
    uploader_mappings:
        people:                                 # a name for the mapping, useful in forms configuration
            class: App\Entity\People            # the mapped entity
            file_property: imageFile            # the name of the entity property to use
            handler: property                   # the upload handler for this mapping
            storage: picture                    # the storage name
```

Of course, you can add as many storages and mappings as you need.

In case you want to use VichUploaderBundle on your entity, the file_property should indicate the entity attribute holding
the Vich UploadableField annotation. The handler to use is "vich" and you will not need a specific storage
(this is done in Vich's configuration).

We can now create our form:

```php
<?php

namespace AppBundle\Form;

use App\Entity\People;
use Sherlockode\AdvancedFormBundle\Form\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PeopleType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class)
            ->add('lastName', TextType::class)
            ->add('imageFile', FileType::class, [
                'label' => 'Drop files here',
                'mapping' => 'people', // the id of the mapping from config.yml
                'image_preview' => true,
                'upload_mode' => 'immediate',
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => People::class,
        ]);
    }
}
```
Notice the "upload_mode" option which let's you choose how the entity will be updated. In the example, we set the value
to "immediate", meaning that the entity will be updated with the new picture as soon as the AJAX request is done
(when drap & drop is over).

You can then define a simple controller using the form:

```php
<?php

namespace AppBundle\Controller;

use App\Entity\People;
use AppBundle\Form\PeopleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PeopleController extends Controller
{
    /**
     * @Route("/people/{people}", name="people_form")
     */
    public function indexAction(Request $request, EntityManagerInterface $em, People $people = null)
    {
        if (!$people instanceof People) {
            $people = new People();
        }

        $form = $this->createForm(SimpleImageType::class, $people);     
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($people);
            $em->flush();
            
            return $this->redirectToRoute('people_form', ['people' => $people->getId()]);
        }

        return $this->render('@App/People/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
```
And to finish, let's create the view including the CSS and JavaScript files
```twig
{% extends "base.html.twig" %}
{% block body %}
<div class="container">
    <div class="row">
        <div class="col-xs-12">
            <h1>People form</h1>
            {{ form_start(form) }}
                {{ form_errors(form) }}
                <div class="form-group">
                    {{ form_row(form.firstName) }}
                </div>
                <div class="form-group">
                    {{ form_row(form.lastName) }}
                </div>
                <div class="form-group">
                    {{ form_widget(form.imageFile) }}
                </div>
                <div>
                    <button class="btn btn-success" type="submit">Edit people</button>
                </div>
            {{ form_end(form) }}
        </div>
    </div>
</div>
{% endblock %}
{# don't forget the assets #}
{% block stylesheets %}
    {{ parent() }}
    <link rel="stylesheet" href="{{ asset('bundles/sherlockodeadvancedform/css/ajax-uploader.css') }}" />
{% endblock %}
{% block javascripts %}
    {{ parent() }}
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="{{ asset('bundles/sherlockodeadvancedform/js/ajax-uploader.js') }}"></script>
{% endblock %}
```
It's done ! Go to [/people](#) and enjoy it !
