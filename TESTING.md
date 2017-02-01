Tests PHPUnit roulés localement
===============================

## Forwarder les ports de Postgres et de Elasticsearch localement:

 ```shell
 ssh -f vagrant@neoclocking.local -p22 -L 24869:neoclocking.local:5432 -N
 ssh -f vagrant@neoclocking.local -p22 -L 28951:neoclocking.local:9200 -N
 ```

## Utiliser gulp pour automatiquement lancer phpunit lors de changement de test

* Installer les dépendances nécessaire:
 ```shell
 npm install
 ```
* Installer le package notify-osd (sous linux) si manquant
* Lancer la tâche tdd d'elixir:
 ```shell
 gulp tdd
 ```
