# php-migration
> Simple database migration tool for php projects

### Setup

- Download `migration.php` and `env.migration.php` files to your project root directory

- Update `env.migration.php` file with your database details

- Run the following command to setup migrations for your project. This will create a table in your database and a folder to all the migration files

```shell
$ php migration.php setup
```


### Usage

- Run the following command to create a new migration

```shell
$ php migration.php create [migration-name]
```

- Open the new migration file created in your migrations folder, and add your sql query as shown below:
```php
...

//Only add one sql statement per query.
$query = "YOUR SQL QUERY GOES HERE";

$result = $this->query($query);

...
```

- Run the following command to run the migration

```shell
$ php migration.php run
```

---

## License

[![License](http://img.shields.io/:license-mit-blue.svg?style=flat-square)](http://badges.mit-license.org)

- **[MIT license](http://opensource.org/licenses/mit-license.php)**
