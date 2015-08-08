# Bot ESN Le Jeu

Ce projet est maintenant arrêté après avoir servi pendant plusieurs mois, à la vue de la dégradation de l'ambiance dans la communauté du jeu ESN, nous avons décider d'arrêter la mise à jour de ce bot ne trouvant plus l'envie de le faire.

## Lancer le bot

```
./run bot:run --config fichier --logger [phpoutput|file|mail]
```

Avec :
* "config" > Chemin complet vers le fichier de configuration ou son nom complet dans le dossier `config`
* "logger" > Le type de log de l'application (par défaut `phpoutput`), au choix phpoutput/file/mail

## Générer un fichier de configuration

```
./run bot:config:generate
```

Génère un fichier de configuration par défaut dans le dossier courant.
