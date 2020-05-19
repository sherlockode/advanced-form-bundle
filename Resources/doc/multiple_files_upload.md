Multiple files upload form
==========================

Let's assume we have a product entity with several pictures. We have to create this kind of entities:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @Vich\Uploadable
 */
class Picture
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $imageName;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Project", inversedBy="files")
     */
    private $product;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Product $product
     *
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
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
}

```

```php
<?php

namespace App\Entity;

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
     * @ORM\ManyToMany(targetEntity="App\Entity\Picture", cascade={"persist"})
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
        $picture->setProduct($this);
    }
}

```

Then, we have to configure the mapping for this behavior.
You will need a few additional properties compared to single file upload:

```yaml
sherlockode_advanced_form:
    storages:
        product_picture:
            filesystem:
                path: '%kernel.project_dir%/var/uploads/picture'
    uploader_mappings:
        product_picture:
            class: App\Entity\Product
            multiple: true                        # this option declares the OneToMany relationships
            file_class: App\Entity\Picture        # you need to indicate the class holding the picture data
            file_property: imageFile              # property used in the picture object to hold the filename
            file_collection_property: pictures    # property in the Product targeting the picture collection
            handler: vich
            storage: product_picture
```

Let's create the form:

```php
<?php

namespace App\Form;

use Sherlockode\AdvancedFormBundle\Form\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ProductType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('pictures', FileType::class, [
                'label' => 'Drop files here',
                'mapping' => 'product_picture',
                'image_preview' => true,
                'upload_mode' => 'immediate',
            ]);
    }
}
```

Then the controller:

```php
<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends AbstractController
{
    public function indexAction(Request $request, EntityManagerInterface $em, Product $product = null)
    {
        if (!$product instanceof Product) {
            $product = new Product();
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($product);
            $em->flush();
            
            return $this->redirectToRoute('product_form', ['product' => $product->getId()]);
        }

        return $this->render('Product/form.html.twig', [
            'form' => $form->createView()
        ]);
    }
}

```

And to finish, let's create the view

```twig
{% extends "base.html.twig" %}

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
                    <button class="btn btn-success" type="submit">Update product</button>
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

Go to your product page and add as many pictures as you want.
