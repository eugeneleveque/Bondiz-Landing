# Bondiz Blog

### Prérequis

Avant de commencer, assurez-vous d’avoir installé :

Docker
et Docker Compose

Make

#### Installation et lancement

Cloner le projet

`git clone <URL_DU_REPO>`

Créer le fichier .env

Copiez le fichier d’exemple et modifiez-le selon vos besoins :

`cp .env.example .env`

Lancer les containers Docker

`make dc-up`

Cette commande build les containers et les lance en arrière-plan.

#### Accéder au site

Une fois les containers lancés, vous pouvez accéder au site en ouvrant votre navigateur et en vous rendant à l’adresse suivante :

`http://localhost`
