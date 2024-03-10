# Sécurité de vos données

## Sécurité des données transmises par l'utilisateur

L'utilisateur peut transmettre des informations personnelles, telles qu'à l'enregistrement de son compte,  modification de son compte ou contact vers le site. Toute donnée est transmises de façon chiffrée via une connexion client-serveur TLS. Toute donnée personnelle envoyée par l'utilisateur au serveur "Mes Histoires" est transmises avec une couche de chiffrement supplémentaire.

Chaque session a sa propre clé de chiffrement afin que l'utilisateur puisse transmettre des données via les protocoles POST, PUT, DELETE.

## Sécurité des sessions

Les sessions sont enregistrées en base de données. Elles ont une durée de vie de 24h. Chaque session est enregistrée chiffrée dans la base de données via un algorithme AES 256 bits.

## Utilisateur authentifié

L'utilisateur authentifié a son compte mis en base de données. Le site "Mes Histoires" ne gère aucun mot de passe.&#x20;

### **Authentification via code :**&#x20;

L'utilisateur demande un code d'accès qui lui est transmis par email. Ce code est généré de façon aléatoire. Le code est enregistré dans la session de l'utilisateur. Une fois utilisé, le code est supprimé. Le code a une durée de vie de 1H. Le code est supprimé après 3 tentatives infructueuses d'accès.&#x20;

### **Authentification** via fournisseur d'identité :&#x20;

Le site "Mes Histoires" permet de s'authentifier via une liste de fournisseurs d'identité définie. Le protocole utilisé est OpenId Connect/Oauth2. Cette liste peut évoluer dans le temps.

Le site "Mes Histoires" utilise les informations suivantes transmises par le fournisseur d'identité :&#x20;

* Le prénom (given\_name)
* Le nom (family\_name)
* l'email (email)

Ces données sont utilisée pour enregistrer l'utilisateur à sa première connexion et pour authentifier l'utilisateur via son adresse email. Aucune autre donnée pouvant être transmise par le fournisseur d'identité n'est utilisée, ni enregistrée.

Le site "Mes Histoires" ne peut garantir la sécurité des fournisseurs d'identité et de leur utilisation par l'utilisateur ou un tiers. Il est fortement conseillé qu'un mot de passe robuste soit mis en place par l'utilisateur ainsi qu'un second facteur d'authentification.

### **Authentification** via clé de sécurité - authentification forte :&#x20;

L'accès via une clé de sécurité nécessite une première connexion via un [code ](securite-de-vos-donnees.md#authentification-via-code)ou un [fournisseur d'identité](securite-de-vos-donnees.md#authentification-via-fournisseur-didentite). Suite à cette connexion, l'utilisateur peut enregistrer une clé de sécurité dans son profil.

La clé de sécurité nécessite de répondre à la norme Webauthn. Tout dispositif compatible peut être :&#x20;

* une clé physique via usb, bluetooth, nfc telle qu'une Yubikey
* un smartphone
* un ordinateur

Le site "Mes histoires" impose un accès de l'utilisateur à son dispositif tel qu'une authentification biométrique ou un code PIN.

Le site "Mes Histoires" garde en base de données l'identification de la clé avec sa clé publique de vérification de signature cryptographique pour assurer l'authentification de l'utilisateur.

Le site "Mes Histoires", dans un soucis de sécurité pour l'utilisateur, recommande l'utilisation d'une clé de sécurité pour les raisons suivantes :&#x20;

* Meilleure sécurité que via [code](securite-de-vos-donnees.md#authentification-via-code)
* Ne pas dépendre d'un fournisseur d'identité dont la sécurité d'accès peut être limitée et dont vos données personnelles vous échappent.

Le site "Mes Histoires" laisse libre l'utilisateur d'enregistrer autant de clés de sécurité qui lui semble nécessaire.&#x20;
