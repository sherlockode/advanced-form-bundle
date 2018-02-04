Create a single file upload form
================================

To create a single file upload form, we have to set an entity with Vich uploader annotations

``` php
<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @param File|UploadedFile $image
     *
     * @return $this
     */
    public function setImageFile(File $image = null)
    {
        $this->imageFile = $image;

        if ($image) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * @return File|null
     */
    public function getImageFile()
    {
        return $this->imageFile;
    }

    /**
     * @param string $imageName
     *
     * @return $this
     */
    public function setImageName($imageName)
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * @param integer $imageSize
     *
     * @return $this
     */
    public function setImageSize($imageSize)
    {
        $this->imageSize = $imageSize;

        return $this;
    }

    /**
     * @return integer|null
     */
    public function getImageSize()
    {
        return $this->imageSize;
    }
}
```
Then, we have to configure the mapping between VichUploader and the bundle. Add in your config.yml file:
``` yaml
sherlockode_advanced_form:
    uploader_mappings:
        - 
            id: people                          # a name for the mapping, useful in forms configuration
            entity: AppBundle\Entity\People     # the mapped entity
            file_property: imageFile            # the name of the entity property which the annotation "@Vich\UploadableField"
```

Of course, you can add as many mapping as you need.

So, we can now create our form:

``` php
<?php

namespace AppBundle\Form;

use AppBundle\Entity\People;
use Sherlockode\AdvancedFormBundle\Form\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class PeopleType
 */
class PeopleType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'firstName',
                TextType::class
            )
            ->add(
                'lastName',
                TextType::class
            )
            ->add(
                'imageFile',
                FileType::class,
                [
                    'label' => 'Drop files here',
                    'mapping' => 'people', // the id of the mapping from config.yml
                    'image_preview' => true,
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => People::class
            ]
        );
    }
}

```
Then, the controller

``` php
<?php

namespace AppBundle\Controller;

use AppBundle\Entity\People;
use AppBundle\Form\PeopleType;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PeopleController extends Controller
{
    /**
     * @Route("/people/{people}", name="people_form")
     *
     * @param Request       $request
     * @param ObjectManager $om
     * @param People|null   $people
     *
     * @return Response
     */
    public function indexAction(Request $request, ObjectManager $om, People $people = null)
    {
        if (!$people instanceof People) {
            $people = new People();
        }

        $form = $this->createForm(SimpleImageType::class, $people);     
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $om->persist($people);
            $om->flush();
            
            return $this->redirectToRoute('people_form', ['people' => $people->getId()]);
        }

        return $this->render(
            'AppBundle:People:form.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }
}

```
And to finish, let's create the view
``` twig
{% extends "::base.html.twig" %}
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
