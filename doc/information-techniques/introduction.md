# Introduction

## Base technique

Le site Mes Histoires est composé de :&#x20;

* un site utilisateur : html, javascript, css
* un portail d'administration : html, javascript, css
* un backoffice accessible via API : PHP 8, mongodb

## Dépendances externes

Dans le cadre de l'authentification de l'utilisateur via un fournisseur d'identité, le site Mes Histoires est configuré pour utiliser le protocole openId Connect.

Dans le cadre de l'envoi de mails, le site Mes Histoires utilise la solution [mailjet ](https://www.mailjet.com/fr/)dans sa version gratuite.
