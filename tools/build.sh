#!/bin/sh

composer install -n
cp .env.example .env
php artisan key:generate