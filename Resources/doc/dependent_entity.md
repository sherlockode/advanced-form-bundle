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
            foreach ($u->getBooks() as $book) {
                $books[$book->getId()] = $book->getTitle();
            }
            return [$user->getId(), $books];
        },
    ])
;
```

The "dependOnElementName" option indicates which form field will be watched to decide the list of choices
we will display in the dependent field.

The mapping option provides, for each value of the parent field, the list of option made available.
The return value must be a two-entries array in which the first entry is the parent option value (here a User id) and
the second one is an array with children option list (value as the key and label for the dropdown as the value) .
