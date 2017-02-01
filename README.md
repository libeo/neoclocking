# Neo Clocking

## Installation

 ```bash
  $ ./tools/build.sh
  $ curl --data '' http://localhost:9200/main
  $ php artisan neoclocking:buildSearchIndex
 ```

## Unit test

 ```bash
 $ vendor/bin/phpunit
 ```
 
## Test behat

 ```bash
 $ php artisan db:seed
 $ php artisan neoclocking:buildSearchIndex
 $ java -jar selenium-server-standalone-2.52.0.jar # wget http://selenium-release.storage.googleapis.com/2.52/selenium-server-standalone-2.52.0.jar
 $ vendor/bin/behat
 ```
 
## Phpcs

 ```bash
 $ ./tools/code_sniffing/code_sniffing.sh
 ```
 
## Utile

 ```bash
  $ php artisan ide-helper:generate
 ```

Pour faire des requests sur l'api, il faut fournir un header particulier:
 ``` bash
 X-Authorization : {USER_API_KEY}
 ```
Le {USER_API_KEY} est spécifique à votre user et est disponible dans la base de données de neoclocking dans la table des users.

Toutes les routes de l'api sont sous le préfixe "/api/".