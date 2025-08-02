# Makefile pour ai-redmine
# Usage: make [target]

.PHONY: install test static-analysis cs-fix cs-check code-quality validate cache-clear clean help

# Installation et setup
install:
	composer install --prefer-dist --no-progress

# Tests
test:
	vendor/bin/phpunit --testdox

# Analyse statique
static-analysis:
	vendor/bin/phpstan analyse src tests --level=5

# Formatage du code
cs-fix:
	vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php

cs-check:
	vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff

# Formatage et analyse
code-quality: cs-fix static-analysis 

# Validation
validate:
	composer validate --strict
	@echo "Validation Composer OK"

# Cache
cache-clear:
	php bin/console cache:clear --env=test
	@echo "Cache vidé"

# Nettoyage
clean:
	rm -rf var/cache/* var/log/*
	@echo "Nettoyage effectué"



# Aide
help:
	@echo "Commandes disponibles :"
	@echo "  install        - Installer les dépendances"
	@echo "  test           - Lancer tous les tests"
	@echo "  static-analysis- Analyse statique avec PHPStan"
	@echo "  cs-fix         - Corriger le formatage du code"
	@echo "  cs-check       - Vérifier le formatage (dry-run)"
	@echo "  code-quality   - Formatage + analyse statique"
	@echo "  validate       - Valider composer.json"
	@echo "  cache-clear    - Vider le cache"
	@echo "  clean          - Nettoyer les fichiers temporaires"
	@echo "  help           - Afficher cette aide" 