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
                $books[$book->getId()] = $book->getTitle();
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
the second one is an array with children option list (value as the key and label for the dropdown as the value).

### Using AJAX

Alternatively, you can provide an URL to be called through AJAX when the first dropdown is changed, this URL
is expected to return the list of options for the second dropdown. This can be interesting if you don't want the
full mapping to be dumped in the HTML content of your page, when you have lots of data for instance.
The `ajax_url` option is designed to do so and has priority over the `mapping` option.

```php
$builder
    ->add('book', DependentEntityType::class, [
        'class' => Book::class,
        'dependOnElementName' => 'owner',
        'ajax_url' => $urlGenerator->generate('my_route'),
    ]);
```

In this case, the URL at `my_route` will be called every time a user changes the value of the first dropdown.
A special GET parameter `id` will be in the request in order for the controller to retrieve the Entity object.

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
        $options[$book->getId()] = $book->getName();
    }
    return new JsonReponse($options);
}
```
