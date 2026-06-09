## Installation for PHP API

This is the PHP based API. Uses Laravel 13 as the framework.


## Composer

Install the dependencies using Composer:

```
composer install
```

## Database

The environment variables are set in the `.env` file.

The default database is SQLite, which is located here after running migrations:
```
database/database.sqlite
```

If you want to use a MariaDB or MySQL database instead, update the `.env` file with the relevant connection details:
```
DB_CONNECTION=mariadb
DB_HOST=[host here]
DB_PORT=3306
DB_DATABASE=[database name here]
DB_USERNAME=[username here]
DB_PASSWORD=[password here]
```

Run the migrations:
```
php artisan migrate
```

If you are using SQLite, make sure the database is writable by the web server.  
For example - if the web server user is `www-data`:
```
chown www-data:www-data database
chown www-data:www-data database/database.sqlite
```

This user is already configured in the database:

username: `test@dvsa.gov.uk`  
password: `password`


## Storage

Make sure the storage directory is writable by the web server.  
For example - if the web server user is `www-data`:
```
chown -R www-data:www-data storage
```


## Worker Daemon

Install the background worker daemon.  
This assumes a Linux init.d system.
```
cp daemon/task.init /etc/init.d/task
cp daemon/task.php /usr/local/bin/task.php
cp daemon/task-child.php /usr/local/bin/task-child.php
chmod 755 /etc/init.d/task
chmod 755 /usr/local/bin/task.php
chmod 755 /usr/local/bin/task-child.php
/etc/init.d/task start
```

Finally set the correct URL to the TypeScript API in the `.env` file:
```
TYPESCRIPT_API_URL=http://demo-ts:3000/api
```


## Running the Demo

Please refer to the separate README.md file for instructions on how to run the demo application.

This covers both the PHP API and the TypeScript API.
