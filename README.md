# redmine-mcp

[![PHPStan](https://img.shields.io/badge/PHPStan-Level%205-brightgreen.svg)](https://phpstan.org/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-100%25%20coverage-brightgreen.svg)](https://phpunit.de/)

Un serveur MCP (Model Context Protocol) pour Redmine qui expose les endpoints GET de l'API Redmine comme outils MCP.

## 🚀 Installation

### Prérequis

- PHP 8.1+
- Composer
- Node.js (pour MCP Inspector)
- Accès à une instance Redmine avec API key

### 1. Installation des dépendances PHP

```bash
composer install
```

### 2. Installation de MCP Inspector (outil de développement)

```bash
npm install
```

### 3. Configuration

Créez un fichier `.env.local` avec vos paramètres Redmine :

```env
REDMINE_URL=https://votre-instance-redmine.com
REDMINE_API_KEY=votre_clé_api_redmine
```

### Utiliser MCP Inspector

```bash
# Inspecter le serveur MCP
npx mcp-inspector php bin/console mcp:server
```

**Note :** Le serveur MCP est fourni par le bundle Symfony MCP et expose automatiquement les outils basés sur l'API Redmine.

### Connecter à Cursor

Pour utiliser ce serveur MCP avec Cursor, ajoutez cette configuration dans votre fichier `.cursor/mcp.json` :

```json
{
  "mcpServers": {
    "redmine": {
      "command": "php",
      "args": [
        "/path/project/bin/console",
        "mcp:server"
      ]
    }
  }
}
```

### Tests

Le projet utilise un **Makefile** pour simplifier les commandes de développement.

**Commandes principales :**
```bash
make test              # Lancer tous les tests
make static-analysis   # Analyse statique avec PHPStan
```

**Autres commandes utiles :**
```bash
make install           # Installer les dépendances
make cache-clear       # Vider le cache
make clean             # Nettoyer les fichiers temporaires
make help              # Afficher toutes les commandes
```


### Ajouter un nouvel outil

Les outils sont générés automatiquement à partir du fichier `redmine_openapi.yml`. Pour ajouter un nouvel endpoint :

1. Mettre à jour `redmine_openapi.yml`
2. Redémarrer le serveur MCP

**Note :** Les outils sont générés dynamiquement par `DynamicToolFactory` basé sur la spécification OpenAPI.

## 📋 Fonctionnalités

- ✅ **Outils GET** pour récupérer les données Redmine
- ✅ **Conformité MCP** avec les conventions de nommage

### Problème d'authentification

Vérifiez votre `.env.local` :
- URL Redmine correcte
- Clé API valide
- Permissions suffisantes

## 📝 Licence

Ce projet est sous licence MIT.