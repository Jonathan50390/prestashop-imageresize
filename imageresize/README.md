# Image Resize Module for PrestaShop

Module PrestaShop pour redimensionner automatiquement les images selon les paramètres du thème actif.

## Caractéristiques

- Redimensionnement automatique des images lors de l'upload
- Support de multiples types d'entités (Produits, Catégories, Fabricants, Fournisseurs)
- Régénération manuelle des images existantes
- Compatible PrestaShop 8.0+ et 9.0+
- Support des formats: JPG, JPEG, PNG, GIF, WEBP

## Installation

### Méthode 1: Via le Back Office PrestaShop

1. Téléchargez le fichier ZIP du module
2. Connectez-vous au back office PrestaShop
3. Allez dans **Modules > Module Manager**
4. Cliquez sur **Uploader un module**
5. Sélectionnez le fichier ZIP
6. Cliquez sur **Installer**

### Méthode 2: Via FTP

1. Décompressez le fichier ZIP
2. Uploadez le dossier `imageresize` dans `/modules/` de votre PrestaShop
3. Allez dans **Modules > Module Manager**
4. Recherchez "Image Resize"
5. Cliquez sur **Installer**

## Configuration

1. Une fois installé, cliquez sur **Configurer**
2. Sélectionnez le type d'entité à traiter (Produits, Catégories, etc.)
3. Activez "Régénérer toutes les images" si nécessaire
4. Cliquez sur **Redimensionner**

## Structure du Module

```
imageresize/
├── imageresize.php          # Classe principale du module
├── config.xml               # Configuration du module
├── composer.json            # Dépendances Composer
├── logo.png                 # Logo du module
├── index.php               # Fichier de sécurité
├── README.md               # Documentation
├── src/
│   ├── Service/
│   │   ├── ImageProcessorService.php    # Service de traitement d'images
│   │   └── EntityImageService.php       # Service de gestion des entités
│   └── Helper/
│       └── FormHelper.php               # Helper pour les formulaires
└── translations/
    └── fr.php              # Traductions françaises
```

## Hooks Utilisés

- `actionAdminControllerSetMedia`: Chargement des ressources dans le back office
- `actionObjectImageAddAfter`: Traitement automatique après ajout d'image
- `actionObjectImageUpdateAfter`: Traitement automatique après mise à jour d'image

## Fonctionnement

### Traitement Automatique

Le module redimensionne automatiquement les images lors de:
- L'ajout d'une nouvelle image produit
- La modification d'une image produit existante

### Traitement Manuel

Vous pouvez régénérer manuellement toutes les images:
1. Accédez à la configuration du module
2. Choisissez le type d'entité
3. Lancez le redimensionnement

## Support des Formats

Le module recherche automatiquement les fichiers source dans les formats suivants:
- JPG / JPEG
- PNG
- GIF
- WEBP

Les images générées sont au format JPG pour optimiser la compatibilité et la performance.

## Logs

Les erreurs sont automatiquement enregistrées dans les logs PrestaShop avec les détails suivants:
- Type d'erreur
- ID de l'image concernée
- Message d'erreur détaillé

Consultez les logs dans: **Paramètres avancés > Logs**

## Compatibilité

- PrestaShop: 8.0.0 à 9.99.99
- PHP: 7.2.5 minimum
- Extensions PHP requises: GD ou Imagick

## Développeur

**Jonathan Guillerm**
- Email: jonathan.guillerm@gmail.com
- GitHub: [Jonathan50390](https://github.com/Jonathan50390)

## Licence

AFL-3.0 (Academic Free License)

## Changelog

### Version 2.1.0
- Refactorisation complète du code
- Séparation en services (ImageProcessorService, EntityImageService)
- Amélioration de la gestion des erreurs
- Meilleure organisation du code (architecture modulaire)
- Ajout de logs détaillés

### Version 2.0.0
- Support PrestaShop 9.0+
- Amélioration des performances
- Support des nouveaux formats d'images

## Support

Pour toute question ou problème, veuillez ouvrir une issue sur le dépôt GitHub.
