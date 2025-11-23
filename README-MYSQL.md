# Migration vers MySQL - Instructions

## 🎯 Pourquoi MySQL ?

Les données disparaissaient avec JSON. MySQL est **beaucoup plus fiable** et **persistant**.

## 📋 Étapes d'installation

### 1. Créer la base de données

1. Ouvrez **phpMyAdmin** (http://localhost/phpmyadmin)
2. Cliquez sur l'onglet **SQL**
3. Copiez-collez le contenu de `database.sql`
4. Cliquez sur **Exécuter**

### 2. Vérifier la configuration

Ouvrez `config.php` et vérifiez que les paramètres correspondent à votre XAMPP :
- `DB_HOST`: `localhost` (par défaut)
- `DB_USER`: `root` (par défaut)
- `DB_PASS`: `` (vide par défaut dans XAMPP)
- `DB_NAME`: `manhwareader`

### 3. Migrer les données existantes

1. Assurez-vous que `data.json` existe et contient vos données
2. Ouvrez dans votre navigateur : `http://localhost/Projet/Site/migrate-to-mysql.php`
3. Le script va transférer toutes vos données de `data.json` vers MySQL
4. Vous verrez un message de confirmation avec le nombre d'éléments migrés

### 4. Tester

1. Rafraîchissez votre site
2. Vos manhwas et chapitres devraient apparaître
3. Créez un nouveau manhwa/chapitre pour tester
4. Rafraîchissez → les données doivent persister !

## ✅ Avantages de MySQL

- ✅ **Données persistantes** : Plus jamais de perte de données
- ✅ **Performances** : Plus rapide que JSON pour les grandes quantités
- ✅ **Fiabilité** : Transactions et intégrité des données
- ✅ **Sauvegarde facile** : Export SQL standard
- ✅ **Synchronisation** : Plus facile à synchroniser entre environnements

## 🔧 Dépannage

### Erreur de connexion MySQL
- Vérifiez que MySQL est démarré dans XAMPP
- Vérifiez les paramètres dans `config.php`

### Données ne s'affichent pas
- Vérifiez la console du navigateur (F12) pour les erreurs
- Vérifiez que la migration s'est bien passée
- Vérifiez que les tables existent dans phpMyAdmin

### Les données ne se sauvegardent pas
- Vérifiez les permissions d'écriture dans MySQL
- Vérifiez la console pour les erreurs PHP

## 📝 Note importante

Le code utilise maintenant MySQL par défaut. Si MySQL n'est pas disponible, il basculera automatiquement vers `data.json` comme fallback.

