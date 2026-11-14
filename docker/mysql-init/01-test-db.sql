-- Base dédiée aux tests automatisés (créée au premier démarrage du conteneur MySQL).
CREATE DATABASE IF NOT EXISTS mediatheque_test CHARACTER SET utf8mb4;
GRANT ALL PRIVILEGES ON mediatheque_test.* TO 'app'@'%';
FLUSH PRIVILEGES;
