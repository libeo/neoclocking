#!/bin/sh

php artisan migrate:refresh --seed --database=testing && ./tools/generate_doc.sh && node_modules/.bin/dredd
