#!/bin/sh
set -e

# Le code est partagé avec Windows : un cache de configuration ou de routes créé par Laragon (chemins C:\...)
# serait illisible dans le conteneur
rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php

exec "$@"
