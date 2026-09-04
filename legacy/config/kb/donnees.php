<?php
// ============================================================
//  BASE DE CONNAISSANCES — DONNEES (DYNAMIQUE)
//  Chaque entree pointe vers une fonction qui lit la base
//  en temps reel. Le champ 'fn' est traite par assistant.php.
// ============================================================

return [

['mots'=>['stock critique','stocks critiques','sous le seuil','niveau bas','stock bas','en alerte','qui manque','quels medicaments manquent','rupture de stock actuelle','manque quoi'],
 'fn'=>'stocks_critiques'],

['mots'=>['risque de rupture','va manquer','bientot vide','prediction rupture','jours restants','combien de jours stock','quand rupture','prevision rupture','rupture imminente'],
 'fn'=>'predire_ruptures'],

['mots'=>['transfert propose','reequilibrage propose','suggestion transfert','quels transferts','que transferer','deplacer stock','suggestion reequilibrage'],
 'fn'=>'suggerer_reequilibrages'],

['mots'=>['village defavorise','desequilibre zone','ville village','equite region','zone rurale stock','campagne defavorisee','desequilibre ville'],
 'fn'=>'detecter_desequilibres'],

['mots'=>['combien de medicaments','nombre de medicaments','total medicaments','medicaments suivis','catalogue total'],
 'fn'=>'nb_medicaments'],

['mots'=>['combien de pra','nombre de pra','liste des pra','combien de regions','pra actifs'],
 'fn'=>'nb_pra'],

['mots'=>['combien de pharmacies','nombre de pharmacies','liste pharmacies','pharmacies actives'],
 'fn'=>'nb_pharmacies'],

['mots'=>['combien d\'alertes','nombre d\'alertes','alertes actives','alertes en cours'],
 'fn'=>'nb_alertes'],

['mots'=>['commandes en attente','combien de commandes','commandes a valider','commandes a traiter'],
 'fn'=>'commandes_attente'],

['mots'=>['subventions en attente','combien de subventions','demandes de subvention en cours'],
 'fn'=>'subventions_attente'],

['mots'=>['mon stock total','combien j\'ai de stock','etat de mon stock','resume stock'],
 'fn'=>'mon_stock'],

['mots'=>['mes ventes','combien j\'ai vendu','ventes du mois','total ventes','chiffre ventes'],
 'fn'=>'mes_ventes'],

['mots'=>['medicament le plus vendu','top vente','meilleure vente','produit populaire','plus consomme'],
 'fn'=>'top_ventes'],

['mots'=>['peremptions proches','medicaments perimes bientot','expiration proche','combien de peremptions'],
 'fn'=>'peremptions_proches'],

['mots'=>['equite nationale','niveau equite','pourcentage equite','score equite'],
 'fn'=>'equite_nationale'],

];
