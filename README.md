# ReplibyteBundle
ReplibyteBundle is a tool to seed your staging and development **MYSQL** database with a subset of your production data, 
using your current project locale doctrine schema.

# Bundle Usage Documentation
## Installation & configurations
## Basic usage

# Bundle Development
## Installation
```
composer install

```

## Quality

#### PHP CS FIXER + PRETTIER

Execute this command to add pre-commit file for automatic launch on commit

```bash
cp etc/configuration/pre-commit .git/hooks/pre-commit
```

## Run Test

Launch your [symfony server](https://symfony.com/doc/current/setup/symfony_server.html)
Make sure you have the good version of php locally. The .php-version will force to use this one when you use the symfony cli.

Then run : 
```
 symfony server:start -d
```


PHPUnit: 
```
    symfony php ./vendor/bin/phpunit tests/Unit/
```

test the commands:

Copy paste the tests/App/.env.dist to a local file  tests/App/.env and edit as expected.

```
    symfony php ./tests/App/console
```
