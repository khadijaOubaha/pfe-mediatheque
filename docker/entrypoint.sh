#!/bin/sh
set -e

echo "Attente de la base de données..."
until php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; do
    sleep 2
done

echo "Application des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "Chargement des données de démonstration (si la base est vide)..."
php bin/console app:demo-data

exec "$@"
