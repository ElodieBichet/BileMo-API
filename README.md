# BileMo-API

B2B API with Symfony 5. Work carried out as part of the training course "Application Developer - PHP / Symfony" on OpenClassrooms.

[![Maintainability](https://api.codeclimate.com/v1/badges/6e8952d5bd92eeb55267/maintainability)](https://codeclimate.com/github/ElodieBichet/BileMo-API/maintainability)

## Table of Contents
1.  __[Prerequisite and technologies](#prerequisite-and-technologies)__
  * [Server](#server)
  * [Framework and libraries](#framework-and-libraries)
2.  __[Installation](#installation)__
  * [Download or clone](#download-or-clone)
  * [Configure environment variables](#configure-environment-variables)
  * [Install the project](#install-the-project)
  * [Create the database](#create-the-database)
3.  __[Use](#use)__
  * [Customize default pagination](#customize-default-pagination)

---
## PREREQUISITE AND TECHNOLOGIES

### __Server__
You need a web server with PHP7 (> 7.2.5) and MySQL.  
Versions used in this project:
* Apache 2.4.46
* PHP 7.3.21
* MySQL 5.7.31

See more information on technical requirements in the [Symfony official documentation](https://symfony.com/doc/5.2/setup.html#technical-requirements).

### __Framework and libraries__
Framework: __Symfony ^5.3.3__
Dependencies manager: __Composer ^1.11.99.2__  

External libraries included via Composer (used in fixtures):
* FakerPHP/Faker ^1.14 ([more info](https://fakerphp.github.io/))

---
## INSTALLATION

### __Download or clone__
Download zip files or clone the project repository with github ([see GitHub documentation](https://docs.github.com/en/github/creating-cloning-and-archiving-repositories/cloning-a-repository)).

### __Configure environment variables__
You need to configure at least these lines in your environment file:
```env
...
###> doctrine/doctrine-bundle ###
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=5.7"
```

### __Install the project__
1.  If needed, install __Composer__ by following [the official instructions](https://getcomposer.org/download/).
2.  In your cmd, go to the directory where you want to install the project and install dependencies with composer:
```
$ cd some\directory
$ composer install
```
Dependencies should be installed in your project (check _vendor_ directory).  
  
To generate your own SSL keys for LexikJWTAuthentication bundle, see [the bundle documentation](https://github.com/lexik/LexikJWTAuthenticationBundle/blob/2.x/Resources/doc/index.md#generate-the-ssl-keys).  

### __Create the database__
If you are in a dev environment, you can create the database and fill it with fake contents with the following command:
```
$ composer prepare-database-dev
```

__Alternatively__, follow the following steps:
1. If the database does not exist, create it with the following command in the project directory:
```
$ php bin/console doctrine:database:create
```
2. Create database structure thanks to migrations:
```
$ php bin/console doctrine:migrations:migrate
```
3. Install fixtures to have fake contents:
```
$ php bin/console doctrine:fixtures:load
```
Your database should be updated with products, customers and users.

  ![DB Model](UML/DB-model.png)

---
## USE

### __Customize default pagination__
Users can add parameters in their GET requests to paginate the display of lists. If they don't, default settings are applied.  
You can modify these default settings in _config/services/pagination.yaml_.

```yaml
# config/services/pagination.yaml

# Default pagination configuration
parameters:
    products_per_page: 10
    products_orderby: name
    products_order: ASC
    users_per_page: 5
    users_orderby: lastName
    users_order: ASC
```

### __Access API documentation__
There are 2 available documentation formats:  
- Json documentation: /api/doc.json
- Html interactive documentation: /api/doc