Dependent Entity FormType
=========================

General setup
-------------

Add the JavaScript file on the pages you want to enable the form:

```html
<script type="text/javascript" src="{{ asset('bundles/sherlockodeadvancedform/js/dependent-entity.js') }}"></script>
```

Configuring the form
--------------------

Imagine you have a User entity which holds a collection of Book. You want a form in which you can select a user
in a dropdown, and the a second dropdown would display the list of books owned by the user. You would do it like this:

```php
$builder
    ->add('owner', EntityType::class, [
        'class' => User::class,
    ])
    ->add('book', DependentEntityType::class, [
        'class' => Book::class,
        'dependOnElementName' => 'owner',
        'mapping' => function (User $user) {
            $books = [];
            foreach ($user->getBooks() as $book) {
                $books[] = ['id' => $book->getId(), 'label' => $book->getTitle()];
            }
            
            return [$user->getId(), $books];
        },
    ])
;
```

The `dependOnElementName` option indicates which form field will be watched to decide the list of choices
we will display in the dependent field.

The `mapping` option provides, for each value of the parent field, the list of option made available.
The return value must be a two-entries array in which the first entry is the parent option value (here a User id) and
the second one is an array of children option data (associative array with `id` and `label` fields).

### Using a mapper

`mapping` may also be a string representing a `DependentMapperInterface`.
The value should match the `getName()` for the targeted `DependentMapperInterface`.

```php
class UserBookMapper implements DependentEntityMapperInterface
{
    public function getDependentResults($user)
    {
        $books = [];
        foreach ($user->getBooks() as $book) {
            $books[] = ['id' => $book->getId(), 'label' => $book->getTitle()];
        }

        return $books;
    }

    public function getMapping($user)
    {
        return [$user->getId(), $this->getDependentResults($user)];
    }

    public function getName()
    {
        return 'user_books';
    }

    public function getSubjectClass()
    {
        return User::class;
    }
}
```

```php
$builder
    ->add('owner', EntityType::class, [
        'class' => User::class,
    ])
    ->add('book', DependentEntityType::class, [
        'class' => Book::class,
        'dependOnElementName' => 'owner',
        'mapping' => 'user_books',
    ])
;
```


### Using AJAX

It's possible to configure a call through AJAX every time the first dropdown is changed, this call
is expected to return the list of options for the second dropdown. This can be interesting if you don't want the
full mapping to be dumped in the HTML content of your page, when you have lots of data for instance.

If you use a `DependentMapperInterface` as your mapping system, you can easily introduce ajax calls
by setting the `ajax_url` option to `true` in your form definition. No other action is needed.

```php
$builder
    ->add('book', DependentEntityType::class, [
        'class' => Book::class,
        'dependOnElementName' => 'owner',
        'mapping' => 'user_books',
        'ajax_url' => true,
    ]);
```

The `ajax_url` option also allows for setting a custom URL if you do not implement the `DependentMapperInterface` or do not want to use the default controller.

Take note that if `ajax_url` is set to a custom URL, the `mapping` option will have no effect.

```php
$builder
    ->add('book', DependentEntityType::class, [
        'class' => Book::class,
        'dependOnElementName' => 'owner',
        'ajax_url' => $urlGenerator->generate('my_route'),
        // no mapping option needed
    ]);
```

In this case, the URL at `my_route` will be called every time a user changes the value of the first dropdown.
A special GET parameter `id` will be added in the request in order for the controller to retrieve the Entity object.

With the User/Book example from above you would write a controller like this:

```php
/**
 * @Route("/user-books", name="my_route")
 */
public function userBooksAction(Request $request)
{
    $this->getDoctrine()->getRepository(User::class)->find($request->get('id'));
    $options = [];
    foreach ($user->getBooks() as $book) {
        $options[] = ['id' => $book->getId(), 'label' => $book->getName()];
    }
    return new JsonReponse($options);
}
```

The custom URL behavior is not recommended as there is no way for the Form to validate the consistency of the data
(in our example, a user could be associated to a book he doesn't own if the HTML form is altered).
Using a dedicated mapper allows for proper automatic form validation.

### Dynamic dropdown creation

If the dropdown that you want to be dependent from another is created dynamically with JavaScript (for instance, in a form collection or inside a popup),
you need to trigger a special event `dependent_entity_created` when it is added to the DOM in order for it to link to its parent.

```javascript
let popup = $('.my-popup-ajax');
popup.find('select.dependent-entity').trigger('dependent_entity_created');
```
