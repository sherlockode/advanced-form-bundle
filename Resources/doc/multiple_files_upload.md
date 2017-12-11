Multiple files upload form
==========================

Let's assume we have a product entity with several pictures. We have to create this kind of entities:

```php
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
class Picture
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
     * @Vich\UploadableField(mapping="media_image", fileNameProperty="imageName", size="imageSize")
     *
     * @var File
     */
    private $imageFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
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

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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

```php
<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Product
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
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Picture", cascade={"persist"})
     */
    private $pictures;

    public function __construct()
    {
        $this->pictures = new ArrayCollection();
    }

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getPictures()
    {
        return $this->pictures;
    }

    /**
     * @param Picture $picture
     */
    public function removePicture(Picture $picture)
    {
        $this->pictures->removeElement($picture);
    }

    /**
     * @param Picture $picture
     */
    public function addPicture(Picture $picture)
    {
        $this->pictures[] = $picture;
    }
}

```

Then, we have to configure the mapping between VichUploader and the bundle. Add in your config.yml file:

```yaml
sherlockode_advanced_form:
    uploader_mappings:
        - 
            id: picture                         # a name for the mapping, useful in forms configuration
            entity: AppBundle\Entity\Picture    # the mapped entity
            file_property: imageFile            # the name of the entity property which the annotation "@Vich\UploadableField"
```

Let's create the form:

```php
<?php

namespace AppBundle\Form;

use AppBundle\Entity\Product;
use Sherlockode\AdvancedFormBundle\Form\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ProductType
 */
class ProductType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class
            )
            ->add(
                'pictures',
                FileType::class,
                [
                    'label' => 'Drop files here',
                    'multiple' => true,
                    'mapping' => 'picture',
                    'js_image_callback' => true,
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
                'data_class' => Product::class
            ]
        );
    }
}

```

Then the controller:

``` php
<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use AppBundle\Form\ProductType;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    /**
     * @Route("/product/{product}", name="product_form")
     *
     * @param Request       $request
     * @param ObjectManager $om
     * @param Product|null  $product
     *
     * @return Response
     */
    public function indexAction(Request $request, ObjectManager $om, Product $product = null)
    {
        if (!$product instanceof Product) {
            $product = new Product();
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $om->persist($product);
            $om->flush();
            
            return $this->redirectToRoute('product_form', ['product' => $product->getId()]);
        }

        return $this->render(
            'AppBundle:Product:form.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }
}

```

And to finish, let's create the view

```twig
{% extends "::base.html.twig" %}

{% block body %}
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <h1>Multi pictures upload</h1>
                {{ form_start(form) }}
                {{ form_errors(form) }}
                <div class="form-row">
                    {{ form_row(form.name) }}
                </div>
                <div class="form-row">
                    {{ form_widget(form.pictures) }}
                </div>
                <div>
                    <button class="btn btn-success" type="submit">Edit product</button>
                </div>
                {{ form_end(form) }}
            </div>
        </div>
    </div>
{% endblock %}
{# Don't forget the assets #}
{% block stylesheets %}
    {{ parent() }}
    <link rel="stylesheet" href="{{ asset('bundles/sherlockodeadvancedform/css/ajax-uploader.css') }}" />
{% endblock %}
{% block javascripts %}
    {{ parent() }}
    <script src="{{ asset('bundles/sherlockodeadvancedform/js/ajax-uploader.js') }}"></script>
{% endblock %}

```

Go to [/product](#) and add many pictures as you want.
