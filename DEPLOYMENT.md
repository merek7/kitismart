# Deploiement KitiSmart sur Coolify

## Pre-requis

- VPS avec Coolify installe
- Domaine pointe vers le VPS
- Repository Git accessible (GitHub, GitLab, etc.)

## Etapes de deploiement sur Coolify

### 1. Creer un nouveau projet

1. Connectez-vous a Coolify
2. Cliquez sur "New Resource" > "Application"
3. Selectionnez votre source Git (GitHub/GitLab)
4. Choisissez le repository KitiSmart

### 2. Configuration du build

- **Build Pack**: Dockerfile
- **Dockerfile Location**: `Dockerfile`
- **Port**: 80

### 3. Variables d'environnement

Configurez ces variables dans Coolify (Settings > Environment Variables):

```env
APP_ENV=prod
APP_URL=https://votre-domaine.com

# Base de donnees PostgreSQL
DB_DRIVER=pgsql
DB_HOST=kitismart-db
DB_PORT=5432
DB_NAME=kitismart
DB_USER=kitismart
DB_PASS=VOTRE_MOT_DE_PASSE_SECURISE

# Email
MAIL_FROM=contact@votre-domaine.com
MAIL_FROM_NAME=KitiSmart
SMTP_HOST=smtp.hostinger.com
SMTP_PORT=465
SMTP_USERNAME=contact@votre-domaine.com
SMTP_PASSWORD="VOTRE_MOT_DE_PASSE_SMTP"
SMTP_ENCRYPTION=ssl
```

### 4. Base de donnees PostgreSQL

#### Option A: Service PostgreSQL dans Coolify

1. Dans votre projet, ajoutez un nouveau service
2. Selectionnez "PostgreSQL"
3. Configurez:
   - Database Name: `kitismart`
   - Username: `kitismart`
   - Password: (generez un mot de passe securise)
4. Notez le hostname interne (ex: `kitismart-db`)

#### Option B: PostgreSQL externe

Si vous avez deja un serveur PostgreSQL, utilisez ses coordonnees.

### 5. Domaine et SSL

1. Dans les settings de l'application
2. Ajoutez votre domaine
3. Activez "Generate SSL Certificate" (Let's Encrypt)

### 6. Deploiement

1. Cliquez sur "Deploy"
2. Attendez la fin du build
3. Verifiez les logs pour les erreurs

## Verifications post-deploiement

### Test de base
```bash
curl https://votre-domaine.com
```

### Test de la base de donnees
Accedez a l'application et creez un compte.

### Test des emails
Allez sur `/admin/email-test` (en mode dev uniquement)

## Structure des fichiers Docker

```
kitismart/
├── Dockerfile              # Image PHP + Apache
├── docker-compose.yml      # Pour dev local ou Coolify
├── .dockerignore           # Fichiers exclus du build
├── docker/
│   └── nginx.conf          # Config Nginx (alternative)
└── .env.example            # Template des variables
```

## Commandes utiles

### Build local
```bash
docker build -t kitismart .
```

### Run local avec docker-compose
```bash
docker-compose up -d
```

### Voir les logs
```bash
docker-compose logs -f app
```

### Acces au container
```bash
docker exec -it kitismart-app bash
```

## Troubleshooting

### Erreur de connexion a la base de donnees
- Verifiez que `DB_HOST` correspond au nom du service PostgreSQL dans Coolify
- Verifiez que le service PostgreSQL est demarre

### Erreur SMTP
- Verifiez les credentials SMTP
- Assurez-vous que le port 465 n'est pas bloque

### Erreur 500
- Passez `APP_ENV=dev` temporairement pour voir les erreurs
- Consultez les logs Docker dans Coolify

### Permissions
Les permissions sont configurees dans le Dockerfile. Si probleme:
```bash
docker exec -it kitismart-app chown -R www-data:www-data /var/www/html
```

## Mise a jour

Pour mettre a jour l'application:
1. Push les changements sur Git
2. Dans Coolify, cliquez sur "Redeploy"

Ou activez le "Auto Deploy" pour deployer automatiquement a chaque push.
