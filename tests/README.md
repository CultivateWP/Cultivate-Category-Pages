# Integration regression

Use a disposable WordPress database with ACF Pro active. Skip the installed copy
of Category Pages so the test can load the checkout being reviewed:

```sh
wp --skip-themes --skip-plugins=cultivate-category-pages eval-file /path/to/Cultivate-Category-Pages/tests/field-keys.php
```

The test registers real ACF fields, saves a connected term through `update_field`,
inserts/reorders taxonomies, and reloads the saved selection. It also exercises
old positional references (including a custom taxonomy formerly at index zero),
checks that reading does not rewrite metadata, checks new saves, and verifies
that unrelated post types and custom keys are not changed.

Fixtures are deleted and the database transaction is rolled back. Use a disposable
database because WordPress hooks can have effects outside that transaction.
