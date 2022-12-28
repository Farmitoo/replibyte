# ReplibyteBundle
ReplibyteBundle is a tool to seed your staging and development **MYSQL** database with a subset of your production data, 
using your current project locale doctrine schema.

# Bundle Usage Documentation
## Installation & configurations
With a docker running in your environment

```
make start
```

## Basic usage

# Bundle Development
## Development Installation (with Docker)
A fake App is an application set for the bundle development, using the bundle configuration. You will find fixtures for 2 databases.

Copy paste the tests/App/.env.dist to a local file tests/App/.env and edit as expected.
(Use the both database configuration set with docker app-local and app-distant. Take care to not use another database as local).

Start your docker and run:
```
make install
```

## Get Databases fixtures with tests\App
```
make load-fixtures
```

## Test replication execute command with tests\App
```
make execute
```

## Run Test Unit
PHPUnit: 
```
make test-unit
```

test the commands:
```
make php
./tests/App/console your-command
```

## Quality

#### PHP CS FIXER + PRETTIER

Execute this command to add pre-commit file for automatic launch on commit
```bash
cp etc/configuration/pre-commit .git/hooks/pre-commit
```

