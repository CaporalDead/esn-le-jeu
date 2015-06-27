# Bot ESN Le Jeu

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
