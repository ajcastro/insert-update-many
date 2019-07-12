# Laravel Eloquent's Insert/Update Many

Laravel's batch insert or batch update for collection of eloquent models.
Perform single query for batch insert or update operations.
This updates the `created_at` and `updated_at` column of the models and the tables.
The method names `insertMany` and `updateMany` is based from the eloquent method name `saveMany`.
Both `insertMany` and `updateMany` can accept collection of models or just plain array data.

## Installation

```
composer require ajcastro/insert-update-many
```

## Usage

### Insert Many

Directly pass array or collection of models unlike Laravel's built-in `insert()` which only accept arrays.
This already sets the `created_at` and `updated_at` columns.

```php
$users = factory(User::class, 10)->make();
User::insertMany($users);
```

#### How it works

The passed collection of models is transformed to its array form, only including fillable attributes, and passed its array
form to Laravel's native `insert()` method.

### Update Many

Update array or collection of models. This perform a single update query for all the passed models.
Only the dirty or changed attributes will be included in the update.
This updates the `updated_at` column of the models and the tables.

```php
User::updateMany($users); // update many models using id as the default key
User::updateMany($users, 'id'); // same as above
User::updateMany($users, 'username'); // use username as key instead of id

```

#### Specifying which columns to be updated

```php
User::updateMany($users, 'id', ['email', 'first_name', 'last_name']);
```

#### How it works

This will produce a query like this:

```sql
UPDATE
   `users`
SET
   `email` =
   CASE
      WHEN
         `id` = '426'
      THEN
         'favian.russel@example.com'
      WHEN
         `id` = '427'
      THEN
         'opurdy@example.org'
      WHEN
         `id` = '428'
      THEN
         'kaylah.hyatt@example.com'
      ELSE
         `email`
   END
, `first_name` =
   CASE
      WHEN
         `id` = '426'
      THEN
         'Orie'
      WHEN
         `id` = '427'
      THEN
         'Hubert'
      WHEN
         `id` = '428'
      THEN
         'Mikayla'
      ELSE
         `first_name`
   END
, `last_name` =
   CASE
      WHEN
         `id` = '426'
      THEN
         'Weissnat'
      WHEN
         `id` = '427'
      THEN
         'Wiza'
      WHEN
         `id` = '428'
      THEN
         'Keeling'
      ELSE
         `last_name`
   END
WHERE
   `id` IN
   (
      426, 427, 428
   );
```
