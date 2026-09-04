<?php
// ============================================================
//  BASE DE CONNAISSANCES — FONCTIONNEMENT DE LA PLATEFORME
//  Ton institutionnel, vouvoiement. ~200 entrees.
//  Format : ['mots' => [...variantes...], 'rep' => 'reponse']
// ============================================================
// Note : 'mots' sert au moteur de recherche par score.
//        Utiliser des mots-cles distinctifs en minuscules sans accents.

return [

// ---------- GENERALITES PLATEFORME ----------
['mots'=>['c\'est quoi la plateforme','presentation','a quoi sert','but de la plateforme','objectif plateforme','role plateforme'],
 'rep'=>"La Pharmacie Sénégalaise est une plateforme nationale de gestion logistique pharmaceutique, pilotée par l'État. Elle assure le suivi des stocks, la distribution équitable des médicaments entre les régions, et la coordination entre l'État, les PRA et les pharmacies."],

['mots'=>['qui gere la plateforme','administrateur','qui controle','qui dirige','responsable plateforme'],
 'rep'=>"La plateforme est administrée par le Ministère de la Santé (rôle « État »), qui supervise l'ensemble du circuit pharmaceutique national et valide les opérations sensibles."],

['mots'=>['quels sont les roles','types d\'utilisateurs','differents acteurs','qui utilise','profils'],
 'rep'=>"La plateforme distingue trois rôles : l'État (supervision nationale), le PRA (Pharmacie Régionale d'Approvisionnement, relais régional), et la Pharmacie (dispensation aux patients). Les fournisseurs interviennent en amont sans accès dédié."],

['mots'=>['c\'est quoi un pra','definition pra','role du pra','pharmacie regionale','que fait le pra'],
 'rep'=>"Le PRA (Pharmacie Régionale d'Approvisionnement) est l'échelon régional du circuit. Il réceptionne les médicaments, gère le stock régional, approvisionne les pharmacies de sa zone et signale les besoins à l'État."],

['mots'=>['c\'est quoi une pharmacie','role pharmacie','que fait la pharmacie','officine'],
 'rep'=>"La pharmacie est l'échelon de proximité. Elle commande ses médicaments auprès de son PRA de rattachement, dispense aux patients, enregistre ses ventes et suit ses stocks et péremptions."],

['mots'=>['role de l\'etat','que fait l\'etat','mission etat','pouvoir etat','prerogatives etat'],
 'rep'=>"L'État supervise l'ensemble du dispositif : il valide les commandes, approuve les subventions et les rééquilibrages, gère les comptes, définit les paramètres et veille à l'équité de la distribution sur le territoire national."],

['mots'=>['chaine d\'approvisionnement','circuit','comment circulent les medicaments','flux','parcours medicament'],
 'rep'=>"Le circuit suit l'ordre suivant : Fournisseur → État → PRA → Pharmacie. L'État agit en garant et superviseur, le PRA en relais régional, et la pharmacie en point de dispensation au patient."],

// ---------- CONNEXION / COMPTE ----------
['mots'=>['comment se connecter','connexion','login','acceder a mon compte','m\'identifier'],
 'rep'=>"Pour vous connecter, saisissez votre adresse email et votre mot de passe sur la page d'accueil, puis cliquez sur « Se connecter ». Vous serez automatiquement dirigé vers votre espace selon votre rôle."],

['mots'=>['mot de passe oublie','perdu mot de passe','reinitialiser mot de passe','recuperer compte','plus mon mot de passe'],
 'rep'=>"Sur la page de connexion, cliquez sur « Mot de passe oublié ? », saisissez votre adresse email, et vous recevrez un lien de réinitialisation valable une heure. Veuillez noter qu'une seule réinitialisation par mois est autorisée sans accord de l'État."],

['mots'=>['changer mot de passe','modifier mot de passe','nouveau mot de passe','premiere connexion mot de passe'],
 'rep'=>"Lors de votre première connexion avec un mot de passe temporaire, le système vous demande obligatoirement de définir un nouveau mot de passe personnel avant d'accéder à votre espace."],

['mots'=>['creer un compte','nouveau compte','inscription','m\'inscrire','demande d\'acces','obtenir un acces'],
 'rep'=>"L'inscription libre n'est pas autorisée. Pour obtenir un accès, cliquez sur « Faire une demande d'accès » depuis la page de connexion et complétez le formulaire. Le Ministère de la Santé examinera votre demande et créera votre compte après vérification."],

['mots'=>['demande refusee','acces refuse','pourquoi refuse','demande rejetee'],
 'rep'=>"Si votre demande d'accès est rejetée, vous en êtes informé par email. Pour connaître les motifs ou contester la décision, veuillez vous rapprocher du Ministère de la Santé."],

['mots'=>['combien de temps demande','delai demande acces','attente validation','quand reponse demande'],
 'rep'=>"Les demandes d'accès sont examinées par le Ministère de la Santé. Vous recevrez une notification par email dès qu'une décision sera prise concernant votre dossier."],

['mots'=>['se deconnecter','deconnexion','quitter','fermer session','logout'],
 'rep'=>"Pour vous déconnecter, cliquez sur « Déconnexion » en bas du menu latéral. Votre session sera fermée en toute sécurité."],

['mots'=>['mon compte est suspendu','compte bloque','suspendu','je ne peux plus me connecter','acces suspendu'],
 'rep'=>"Un compte suspendu ne permet plus la connexion. La suspension est décidée par l'État. Pour toute réactivation, veuillez contacter le Ministère de la Santé."],

['mots'=>['limite reinitialisation','combien de fois reinitialiser','trop de reinitialisation','reinitialisation refusee'],
 'rep'=>"Pour des raisons de sécurité, une seule réinitialisation de mot de passe par mois est autorisée. Au-delà, une demande d'autorisation exceptionnelle est transmise à l'État, qui peut l'accorder."],

// ---------- COMMANDES (PHARMACIE) ----------
['mots'=>['passer une commande','commander','faire une commande','demander des medicaments','nouvelle commande'],
 'rep'=>"Pour passer une commande (rôle Pharmacie) : ouvrez le menu « Commander », sélectionnez le médicament, indiquez la quantité et le niveau d'urgence, ajoutez une justification, puis soumettez. Votre PRA recevra la demande pour validation."],

['mots'=>['suivre ma commande','etat de ma commande','ou en est ma commande','statut commande','suivi commande'],
 'rep'=>"Le menu « Mes commandes » affiche l'historique et le statut de chacune de vos commandes : en attente, validée, rejetée, en transit ou livrée."],

['mots'=>['commande en attente','pourquoi en attente','commande pas validee','attente validation commande'],
 'rep'=>"Une commande « en attente » n'a pas encore été traitée par votre PRA. Le délai de traitement dépend de la charge du PRA et du niveau d'urgence indiqué."],

['mots'=>['commande rejetee','commande refusee','pourquoi refusee commande'],
 'rep'=>"Une commande peut être rejetée par le PRA ou l'État, généralement pour stock insuffisant, quantité disproportionnée ou justification incomplète. Vous pouvez soumettre une nouvelle commande corrigée."],

['mots'=>['niveau d\'urgence','urgence commande','critique alerte normale','definir urgence'],
 'rep'=>"Trois niveaux d'urgence sont disponibles : Normale (réapprovisionnement courant), Alerte (stock bas) et Critique (rupture imminente). Le niveau choisi oriente la priorité de traitement."],

['mots'=>['annuler commande','supprimer commande','retirer commande'],
 'rep'=>"L'annulation d'une commande n'est pas automatique. Si une commande est encore en attente, rapprochez-vous de votre PRA pour demander son annulation avant validation."],

['mots'=>['modifier commande','changer quantite commande','corriger commande'],
 'rep'=>"Une commande soumise ne peut pas être modifiée directement. Si elle est encore en attente, contactez votre PRA ; sinon, soumettez une nouvelle commande avec les bonnes informations."],

// ---------- DEMANDES (PRA) ----------
['mots'=>['valider une demande pharmacie','traiter demande','demandes des pharmacies','valider commande pharmacie'],
 'rep'=>"En tant que PRA, le menu « Demandes pharmacies » liste les commandes des pharmacies de votre zone. Vous pouvez les valider ou les refuser. Une commande validée déclenche la préparation de la livraison."],

['mots'=>['livrer une pharmacie','organiser livraison','expedier','envoi medicament pharmacie'],
 'rep'=>"Après validation d'une commande, le PRA organise la livraison vers la pharmacie concernée. Le suivi des sorties est disponible dans la section dédiée du tableau de bord."],

['mots'=>['pharmacie negligente','pharmacie ne commande pas','relancer pharmacie','rappeler pharmacie'],
 'rep'=>"Si une pharmacie néglige son réapprovisionnement alors que son stock est bas, le PRA peut la relancer. En cas de seuil critique conjugué à une forte demande de la zone, l'État peut imposer un réapprovisionnement."],

// ---------- SUBVENTIONS ----------
['mots'=>['c\'est quoi une subvention','definition subvention','a quoi sert subvention','principe subvention'],
 'rep'=>"La subvention est une prise en charge financière accordée par l'État pour permettre à une pharmacie en difficulté d'accéder aux médicaments. Elle garantit la continuité de l'accès aux soins, y compris dans les zones défavorisées."],

['mots'=>['demander une subvention','signaler subvention','faire une demande de subvention','solliciter subvention'],
 'rep'=>"En tant que PRA, ouvrez le menu « Subventions », puis renseignez la pharmacie concernée, les médicaments nécessaires, le montant estimé et le motif. La demande est transmise à l'État pour décision."],

['mots'=>['qui peut subventionner','qui accorde subvention','qui valide subvention'],
 'rep'=>"Seul l'État (Ministère de la Santé) peut accorder une subvention. Les PRA signalent les situations, mais la décision finale relève de l'État."],

['mots'=>['subvention refusee','pourquoi subvention refusee','rejet subvention'],
 'rep'=>"Une demande de subvention peut être refusée si elle ne répond pas aux critères d'éligibilité ou si le plafond mensuel est atteint. Le motif peut être obtenu auprès du Ministère de la Santé."],

['mots'=>['plafond subvention','montant maximum subvention','limite subvention'],
 'rep'=>"Un plafond de subvention par pharmacie et par mois est défini dans les paramètres de la plateforme par l'État. Au-delà de ce plafond, une analyse complémentaire est requise."],

['mots'=>['qui beneficie subvention','pharmacie eligible subvention','conditions subvention'],
 'rep'=>"Les pharmacies en difficulté d'approvisionnement, notamment en zone rurale ou à faible trésorerie, sont prioritaires pour les subventions. Le signalement par le PRA est requis."],

// ---------- REEQUILIBRAGE ----------
['mots'=>['c\'est quoi le reequilibrage','definition reequilibrage','principe reequilibrage','a quoi sert reequilibrage'],
 'rep'=>"Le rééquilibrage consiste à transférer des stocks d'une région en surplus vers une région en déficit, afin d'assurer une répartition équitable des médicaments sur le territoire national."],

['mots'=>['demander un reequilibrage','signaler reequilibrage','besoin de transfert','demander transfert'],
 'rep'=>"En tant que PRA, ouvrez le menu « Rééquilibrage », indiquez le médicament concerné, la quantité nécessaire, la priorité et une justification. La demande est transmise à l'État, qui peut organiser le transfert depuis une région en surstock."],

['mots'=>['qui valide reequilibrage','qui autorise transfert','validation reequilibrage'],
 'rep'=>"Les transferts de rééquilibrage sont validés par l'État. Ils peuvent être suggérés automatiquement par le moteur d'analyse ou signalés manuellement par un PRA."],

['mots'=>['reequilibrage automatique','suggestion automatique transfert','ia reequilibrage'],
 'rep'=>"Le moteur d'analyse de la plateforme détecte automatiquement les déséquilibres entre régions et propose des transferts. Ces suggestions sont soumises à la validation de l'État."],

// ---------- STOCKS / INVENTAIRE ----------
['mots'=>['voir mon stock','consulter stock','mon inventaire','etat de mon stock'],
 'rep'=>"Le menu « Mon stock » (Pharmacie) ou « Inventaire » (PRA) affiche l'ensemble de vos médicaments avec les quantités, lots, dates de péremption et seuils d'alerte."],

['mots'=>['ajouter un medicament','nouveau medicament stock','enregistrer medicament'],
 'rep'=>"L'ajout de médicaments au stock s'effectue lors des réceptions de livraison. Le PRA enregistre les entrées dans son inventaire ; la pharmacie reçoit les quantités validées par son PRA."],

['mots'=>['seuil d\'alerte','c\'est quoi le seuil','seuil critique stock','niveau d\'alerte'],
 'rep'=>"Le seuil d'alerte est la quantité minimale en dessous de laquelle un médicament est considéré comme insuffisant. Lorsqu'un stock passe sous ce seuil, une alerte est générée automatiquement."],

['mots'=>['enregistrer une vente','vendre un medicament','saisir vente','noter vente','vente patient'],
 'rep'=>"En tant que pharmacie, ouvrez le menu « Enregistrer une vente », sélectionnez le médicament et la quantité vendue, puis validez. Le stock est automatiquement décrémenté et la vente alimente les statistiques de consommation de votre zone."],

['mots'=>['pourquoi enregistrer ventes','interet des ventes','a quoi servent les ventes'],
 'rep'=>"L'enregistrement des ventes permet de connaître la consommation réelle par zone. Ces données alimentent les prévisions de rupture, l'évaluation de la demande et les décisions de réapprovisionnement de l'État."],

// ---------- PEREMPTIONS ----------
['mots'=>['peremption','medicament perime','date d\'expiration','suivi peremption','produits perimes'],
 'rep'=>"Le menu « Péremptions » liste les médicaments dont la date d'expiration approche. Un préavis paramétrable (par défaut 30 jours) permet d'anticiper le retrait ou l'écoulement prioritaire des lots concernés."],

['mots'=>['que faire medicament perime','gerer peremption','medicament bientot perime'],
 'rep'=>"Un médicament proche de la péremption doit être écoulé en priorité ou signalé. Les produits périmés ne doivent jamais être dispensés et doivent être retirés selon les procédures en vigueur."],

// ---------- COMPTES / GESTION (ETAT) ----------
['mots'=>['gerer les comptes','administration comptes','liste des utilisateurs','gestion utilisateurs'],
 'rep'=>"En tant qu'État, le menu « Comptes » permet de consulter tous les utilisateurs, de traiter les demandes d'accès, et de suspendre, réactiver ou supprimer un compte."],

['mots'=>['suspendre un compte','bloquer utilisateur','desactiver compte'],
 'rep'=>"Dans la section « Comptes », le bouton de suspension empêche un utilisateur de se connecter sans supprimer ses données. La réactivation est possible à tout moment. Les comptes État ne peuvent pas être suspendus."],

['mots'=>['supprimer un compte','effacer utilisateur','retirer compte'],
 'rep'=>"La suppression d'un compte est définitive. Si l'utilisateur possède un historique de commandes, le système le suspend automatiquement au lieu de le supprimer, afin de préserver l'intégrité des données."],

['mots'=>['parametres','reglages','configuration','modifier les seuils','configurer plateforme'],
 'rep'=>"Le menu « Paramètres » (État) permet de configurer les seuils d'alerte, les règles de subvention et de rééquilibrage, ainsi que les données de référence de la plateforme. Les modifications sont enregistrées dans le système."],

// ---------- STATISTIQUES ----------
['mots'=>['voir les statistiques','consulter stats','graphiques','tableaux de bord chiffres','analyse donnees'],
 'rep'=>"Le menu « Statistiques » présente les indicateurs clés sous forme de graphiques : consommation par catégorie, niveaux de stock par région et tendances. Ces analyses reposent sur les données réelles de la plateforme."],

['mots'=>['exporter donnees','telecharger rapport','export','sortir les donnees'],
 'rep'=>"Certaines sections proposent une fonction d'export permettant de télécharger les données affichées. Cette fonctionnalité facilite l'élaboration de rapports et le suivi administratif."],

// ---------- ALERTES / NOTIFICATIONS ----------
['mots'=>['c\'est quoi une alerte','alertes','notifications','recevoir alerte','systeme d\'alerte'],
 'rep'=>"Les alertes signalent automatiquement les situations nécessitant une attention : rupture imminente, stock sous le seuil, péremption proche ou suggestion de rééquilibrage. Elles sont consultables dans la section dédiée du tableau de bord."],

['mots'=>['desactiver alerte','trop d\'alertes','gerer notifications','reglage alerte'],
 'rep'=>"Les seuils déclenchant les alertes sont configurables dans les paramètres. L'État peut ajuster ces seuils pour adapter la sensibilité du système d'alerte aux besoins réels."],

// ---------- TABLEAU DE BORD / NAVIGATION ----------
['mots'=>['tableau de bord','dashboard','page d\'accueil','vue d\'ensemble','accueil'],
 'rep'=>"Le tableau de bord constitue la page d'accueil de votre espace. Il présente une synthèse des indicateurs clés correspondant à votre rôle : stocks, alertes, demandes en cours et statistiques."],

['mots'=>['naviguer','menu','trouver une section','utiliser l\'interface','se reperer'],
 'rep'=>"La navigation s'effectue via le menu latéral, organisé par thèmes. Chaque entrée donne accès à une section spécifique. Le titre en haut de page indique en permanence la section consultée."],

// ---------- FOURNISSEURS ----------
['mots'=>['fournisseur','c\'est quoi un fournisseur','role fournisseur','grossiste'],
 'rep'=>"Le fournisseur approvisionne le circuit en médicaments, en amont de l'État. Il ne dispose pas d'un espace dédié sur la plateforme : son intervention se situe à l'entrée de la chaîne d'approvisionnement."],

['mots'=>['pourquoi pas d\'acces fournisseur','fournisseur connexion','espace fournisseur'],
 'rep'=>"Les fournisseurs n'ont pas besoin d'un tableau de bord dédié : leur rôle se limite à la livraison des produits au circuit national. Le suivi de leurs livraisons est assuré par l'État et les PRA."],

['mots'=>['ponctualite fournisseur','fiabilite fournisseur','evaluation fournisseur'],
 'rep'=>"L'État suit les fournisseurs référencés, notamment leur taux de ponctualité de livraison. Un fournisseur peu fiable peut être placé sous surveillance afin de garantir la régularité de l'approvisionnement."],

// ---------- SECURITE / DONNEES ----------
['mots'=>['securite donnees','confidentialite','protection donnees','mes donnees sont securisees'],
 'rep'=>"Les accès sont strictement contrôlés par authentification et par rôle. Les mots de passe sont stockés de manière chiffrée et ne sont jamais accessibles en clair. Chaque utilisateur n'accède qu'aux données relevant de ses prérogatives."],

['mots'=>['qui voit mes donnees','visibilite donnees','qui a acces a mes informations'],
 'rep'=>"Chaque rôle dispose d'une visibilité limitée à son périmètre : une pharmacie voit ses propres données, un PRA celles de sa région, et l'État dispose d'une vue nationale consolidée."],

['mots'=>['mot de passe securise','bon mot de passe','proteger mon compte'],
 'rep'=>"Il est recommandé de choisir un mot de passe d'au moins six caractères, connu de vous seul, et de ne jamais le partager. En cas de doute sur la sécurité de votre compte, procédez à sa réinitialisation."],

// ---------- ROLE ETAT DETAILLE ----------
['mots'=>['valider une commande etat','validation etat','approuver commande','controle commande etat'],
 'rep'=>"L'État valide les commandes dans la section « Validation ». Cette étape de contrôle garantit la cohérence des approvisionnements avec les priorités sanitaires nationales et la disponibilité des ressources."],

['mots'=>['obliger reapprovisionnement','forcer pharmacie','imposer commande','contrainte reapprovisionnement'],
 'rep'=>"L'État peut imposer un réapprovisionnement à une pharmacie lorsque deux conditions sont réunies : un stock à un seuil critique, et une demande de la zone jugée haute ou nécessaire. Cette règle de gestion vise à protéger l'accès aux soins des populations concernées."],

['mots'=>['vue nationale','vision globale','suivi national','panorama'],
 'rep'=>"L'État dispose d'une vue nationale consolidée : état des stocks par région, alertes, demandes en cours et indicateurs d'équité. Cette vision globale permet un pilotage cohérent de la distribution."],

// ---------- DIVERS PROCEDURES ----------
['mots'=>['reception livraison','recevoir livraison','enregistrer reception','accuser reception'],
 'rep'=>"À la réception d'une livraison, le PRA enregistre les quantités reçues dans son inventaire, avec les numéros de lot et dates de péremption. Cette étape met à jour le stock disponible."],

['mots'=>['transit','en cours de livraison','statut transit','commande expediee'],
 'rep'=>"Le statut « en transit » indique qu'une commande validée est en cours d'acheminement vers son destinataire. Une fois réceptionnée, elle passe au statut « livrée »."],

['mots'=>['historique','journal','suivi des operations','tracabilite'],
 'rep'=>"La plateforme conserve l'historique des opérations (commandes, livraisons, signalements). Cette traçabilité facilite le contrôle, l'audit et l'établissement de rapports administratifs."],

['mots'=>['probleme technique','bug','erreur','ca ne marche pas','dysfonctionnement'],
 'rep'=>"En cas de difficulté technique, il est recommandé de recharger la page, de vérifier votre connexion, puis de réessayer. Si le problème persiste, signalez-le à l'administrateur de la plateforme."],

['mots'=>['contacter support','aide technique','assistance','joindre administrateur'],
 'rep'=>"Pour toute assistance dépassant le périmètre de cet outil, veuillez vous adresser au Ministère de la Santé, administrateur de la plateforme."],

['mots'=>['langue','changer langue','francais','traduction'],
 'rep'=>"La plateforme est actuellement disponible en langue française, conformément au cadre administratif national."],

['mots'=>['imprimer','impression','version papier'],
 'rep'=>"Les pages peuvent être imprimées via la fonction d'impression de votre navigateur. Pour les données structurées, privilégiez la fonction d'export lorsqu'elle est disponible."],

['mots'=>['horaires','disponibilite plateforme','24h','acces permanent'],
 'rep'=>"La plateforme est accessible en permanence. Le traitement des demandes (commandes, subventions) dépend toutefois de la disponibilité des agents habilités à les valider."],

['mots'=>['securite','donnees protegees','confidentialite','mes donnees sont elles sures','protection donnees'],
 'rep'=>"L'accès à la plateforme est strictement contrôlé et limité aux structures habilitées. Les mots de passe sont chiffrés et chaque rôle ne voit que les informations qui le concernent. Aucune inscription libre n'est permise."],

['mots'=>['donnees personnelles','rgpd','vie privee','utilisation de mes donnees'],
 'rep'=>"Les données traitées par la plateforme relèvent de la logistique pharmaceutique et de la gestion des comptes habilités. Elles sont utilisées dans le seul cadre de la mission de service public confiée au Ministère de la Santé."],

['mots'=>['signaler un probleme','bug','erreur technique','dysfonctionnement','ca ne marche pas'],
 'rep'=>"En cas de dysfonctionnement technique, veuillez contacter l'administration de la plateforme au sein du Ministère de la Santé en décrivant précisément le problème rencontré et l'action effectuée."],

['mots'=>['contacter le support','assistance','aide technique','qui contacter','joindre administration'],
 'rep'=>"Pour toute assistance, le point de contact est l'administration de la plateforme, rattachée au Ministère de la Santé. Les questions fonctionnelles courantes peuvent également être posées à cet assistant."],

['mots'=>['notification','alerte email','recevoir alertes','etre prevenu'],
 'rep'=>"La plateforme génère des alertes internes (stocks critiques, péremptions) et envoie certaines notifications par email, notamment lors de la création de compte ou de la réinitialisation du mot de passe."],

['mots'=>['validation commande etat','l\'etat valide quoi','que valide l\'etat','controle etat'],
 'rep'=>"L'État valide les opérations sensibles : commandes importantes, subventions, transferts de rééquilibrage et créations de comptes. Cette supervision garantit la cohérence et l'équité du dispositif national."],

['mots'=>['difference pra pharmacie','pra ou pharmacie','distinguer pra pharmacie'],
 'rep'=>"Le PRA est l'échelon régional qui approvisionne plusieurs pharmacies, tandis que la pharmacie est le point de dispensation aux patients. La pharmacie commande auprès de son PRA de rattachement."],

['mots'=>['fournisseur','role fournisseur','grossiste','laboratoire'],
 'rep'=>"Les fournisseurs approvisionnent le circuit en amont. Ils n'interviennent pas directement sur la plateforme : l'État référence les fournisseurs et supervise les approvisionnements vers les PRA."],

['mots'=>['historique','journal','suivi des operations','tracabilite actions'],
 'rep'=>"Les opérations (commandes, validations, livraisons, signalements) sont enregistrées et consultables dans les sections correspondantes de votre tableau de bord, assurant un suivi complet de l'activité."],

['mots'=>['obliger reapprovisionnement','forcer reapprovisionnement','imposer commande pharmacie','obligation pharmacie'],
 'rep'=>"L'État peut imposer un réapprovisionnement à une pharmacie lorsque deux conditions sont réunies : un stock à un seuil critique, et une demande de la zone jugée haute (forte consommation) ou nécessaire (pic anormal évoquant une épidémie, ou pharmacies voisines également en difficulté). Cette règle garantit la continuité de l'accès aux médicaments."],

['mots'=>['zone ville village','classification zone','type de zone','urbain rural'],
 'rep'=>"Chaque structure est rattachée à une zone (ville, village ou rural). Cette classification permet à la plateforme de mesurer l'équité de la distribution et de repérer les zones défavorisées nécessitant une intervention."],

];
