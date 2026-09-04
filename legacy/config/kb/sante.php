<?php
// ============================================================
//  BASE DE CONNAISSANCES — SANTE PUBLIQUE & LOGISTIQUE SANITAIRE
//  Ton institutionnel, vouvoiement. ~50 sujets riches.
//  Avertissement : informations generales, ne remplacent pas
//  un avis medical professionnel.
// ============================================================

return [

// ---------- CHAINE DU FROID ----------
['mots'=>['chaine du froid','chaine de froid','conservation au froid','rupture chaine froid','temperature medicament','conserver au frais'],
 'rep'=>"La chaîne du froid désigne le maintien d'une température contrôlée (généralement 2 à 8 °C) pour les produits thermosensibles tels que les vaccins, l'insuline ou certains sérums. Toute rupture de cette chaîne peut altérer l'efficacité du produit, qui doit alors être considéré comme potentiellement inutilisable."],

['mots'=>['vaccin conservation','conserver vaccin','stockage vaccin','temperature vaccin'],
 'rep'=>"Les vaccins doivent être conservés en permanence entre 2 et 8 °C, à l'abri de la lumière et de la congélation. Un contrôle régulier de la température des réfrigérateurs et l'usage d'indicateurs de température sont recommandés pour garantir leur intégrité."],

['mots'=>['rupture chaine froid que faire','vaccin expose chaleur','medicament expose chaleur'],
 'rep'=>"En cas de suspicion de rupture de la chaîne du froid, le produit concerné doit être isolé, ne pas être dispensé, et signalé aux autorités sanitaires compétentes pour évaluation avant toute décision d'utilisation ou de destruction."],

// ---------- PALUDISME ----------
['mots'=>['paludisme','malaria','traitement paludisme','medicament paludisme','antipaludique','palu'],
 'rep'=>"Le paludisme est une maladie parasitaire transmise par les moustiques, endémique au Sénégal, avec une recrudescence en saison des pluies. La prise en charge repose notamment sur les combinaisons thérapeutiques à base d'artémisinine (ACT). Le diagnostic doit être confirmé avant tout traitement."],

['mots'=>['saison paludisme','pic paludisme','quand paludisme','periode palu','saison des pluies maladie'],
 'rep'=>"Au Sénégal, la transmission du paludisme s'intensifie pendant et après la saison des pluies (généralement de juillet à novembre). Il est recommandé d'anticiper le renforcement des stocks d'antipaludiques et de tests de diagnostic rapide durant cette période."],

['mots'=>['artemether','artesunate','act paludisme','combinaison therapeutique'],
 'rep'=>"Les dérivés de l'artémisinine (artéméther, artésunate), souvent en combinaison, constituent le traitement de référence du paludisme simple à falciparum. Leur usage doit suivre les protocoles nationaux. L'artésunate injectable est réservé aux formes graves."],

// ---------- DESHYDRATATION / SRO ----------
['mots'=>['diarrhee','deshydratation','sro','sels de rehydratation','rehydratation','solute'],
 'rep'=>"Les sels de réhydratation orale (SRO) sont essentiels dans la prise en charge des diarrhées, en particulier chez l'enfant, pour prévenir la déshydratation. Leur disponibilité doit être assurée en continu, notamment en période de gastro-entérites et dans les zones rurales."],

['mots'=>['cholera','epidemie cholera','diarrhee aigue','choléra'],
 'rep'=>"Le choléra est une infection diarrhéique aiguë pouvant entraîner une déshydratation sévère. La réhydratation (orale ou intraveineuse) est prioritaire. Une augmentation soudaine des cas dans une zone doit être signalée sans délai aux autorités sanitaires."],

// ---------- ANTIBIOTIQUES ----------
['mots'=>['antibiotique','amoxicilline','infection bacterienne','antibiotherapie'],
 'rep'=>"Les antibiotiques, dont l'amoxicilline est un représentant courant, traitent les infections bactériennes. Leur usage rationnel est essentiel pour limiter l'antibiorésistance. La dispensation doit respecter la prescription médicale et les protocoles nationaux."],

['mots'=>['antibioresistance','resistance antibiotique','usage rationnel antibiotique'],
 'rep'=>"L'antibiorésistance est un enjeu majeur de santé publique. Elle résulte notamment d'un usage excessif ou inadapté des antibiotiques. La plateforme contribue à la rationalisation en assurant la traçabilité de la consommation et en évitant les sur-stockages inutiles."],

['mots'=>['metronidazole','infection parasitaire','amibiase'],
 'rep'=>"Le métronidazole est utilisé contre certaines infections bactériennes et parasitaires, notamment les amibiases et infections à anaérobies. Sa dispensation doit respecter la prescription et les recommandations en vigueur."],

// ---------- DOULEUR / FIEVRE ----------
['mots'=>['paracetamol','douleur','fievre','antalgique','antipyretique','mal de tete'],
 'rep'=>"Le paracétamol est un antalgique et antipyrétique de première intention pour la douleur légère à modérée et la fièvre. C'est l'un des médicaments les plus consommés ; sa disponibilité constante est une priorité logistique."],

['mots'=>['aspirine','anti-inflammatoire','ibuprofene','inflammation'],
 'rep'=>"Les anti-inflammatoires comme l'aspirine ou l'ibuprofène traitent douleur, fièvre et inflammation. Leur dispensation tient compte des contre-indications. La gestion des stocks doit prévenir les ruptures sur ces produits de grande consommation."],

// ---------- SANTE MATERNELLE / SUPPLEMENTS ----------
['mots'=>['fer','anemie','acide folique','grossesse supplement','carence fer'],
 'rep'=>"La supplémentation en fer et acide folique est recommandée notamment chez la femme enceinte pour prévenir l'anémie. Ces produits relèvent des programmes de santé maternelle et leur disponibilité doit être maintenue dans les structures de proximité."],

['mots'=>['vitamine','carence','supplement nutritionnel','vitamine c'],
 'rep'=>"Les suppléments vitaminiques contribuent à la prévention des carences. Leur gestion suit les mêmes règles de stock et de péremption que les autres produits, avec une attention particulière aux dates d'expiration."],

// ---------- EPIDEMIES / ALERTES SANITAIRES ----------
['mots'=>['epidemie','risque epidemie','flambee','propagation maladie','alerte sanitaire'],
 'rep'=>"Une épidémie se caractérise par une augmentation anormale et rapide des cas d'une maladie dans une zone donnée. La plateforme aide à la détecter en repérant les pics de consommation de certains médicaments, ce qui permet d'anticiper les besoins et d'alerter les autorités."],

['mots'=>['pic de consommation','augmentation soudaine','hausse anormale','consommation anormale'],
 'rep'=>"Un pic anormal de consommation d'un médicament dans une zone peut signaler une situation sanitaire émergente. Le moteur d'analyse compare la consommation récente à la moyenne afin d'identifier ces situations et de suggérer les mesures appropriées."],

['mots'=>['surveillance epidemiologique','veille sanitaire','detecter maladie'],
 'rep'=>"La surveillance épidémiologique consiste à suivre l'évolution des maladies pour détecter précocement les flambées. L'analyse des données de consommation pharmaceutique constitue un indicateur complémentaire utile à cette veille."],

// ---------- DON / GESTION SANITAIRE ----------
['mots'=>['penurie nationale','rupture nationale','manque medicament pays','indisponibilite'],
 'rep'=>"En cas de pénurie nationale d'un médicament, l'État coordonne la répartition des stocks disponibles selon les priorités sanitaires, peut activer des rééquilibrages entre régions et solliciter les fournisseurs. La transparence des stocks facilite ces arbitrages."],

['mots'=>['medicament essentiel','liste medicaments essentiels','produits prioritaires'],
 'rep'=>"Les médicaments essentiels sont ceux qui répondent aux besoins de santé prioritaires de la population. Leur disponibilité permanente est un objectif central de la plateforme, qui en assure le suivi rapproché."],

['mots'=>['generique','medicament generique','equivalent','difference generique'],
 'rep'=>"Un médicament générique a la même composition en principe actif et la même efficacité que le médicament de référence, à moindre coût. Le recours aux génériques contribue à l'accessibilité financière des traitements."],

['mots'=>['ordonnance','prescription','dispensation sans ordonnance'],
 'rep'=>"Certains médicaments ne peuvent être délivrés que sur présentation d'une ordonnance médicale. La dispensation doit respecter la réglementation pharmaceutique en vigueur afin de garantir la sécurité des patients."],

['mots'=>['pharmacovigilance','effet indesirable','effet secondaire','signaler effet'],
 'rep'=>"La pharmacovigilance consiste à surveiller et signaler les effets indésirables des médicaments. Tout effet inattendu doit être notifié aux structures compétentes afin de contribuer à la sécurité sanitaire."],

['mots'=>['stockage medicament','conservation medicament','conditions de stockage','entreposage'],
 'rep'=>"Les médicaments doivent être conservés dans des conditions appropriées de température, d'humidité et de lumière, selon les indications du fabricant. Un stockage inadéquat peut compromettre leur efficacité et leur sécurité."],

['mots'=>['zone rurale acces','village medicament','desert medical','acces soins rural'],
 'rep'=>"L'accès aux médicaments dans les zones rurales et les villages constitue un enjeu d'équité majeur. La plateforme vise à réduire les écarts entre zones urbaines et rurales par le rééquilibrage des stocks et les subventions ciblées."],

['mots'=>['equite acces soins','justice sanitaire','egalite acces medicament'],
 'rep'=>"L'équité d'accès aux soins implique que chaque population, quelle que soit sa localisation, puisse obtenir les médicaments nécessaires. Ce principe guide les mécanismes de distribution et de subvention de la plateforme."],

['mots'=>['tuberculose','traitement tuberculose','tb','antituberculeux'],
 'rep'=>"La tuberculose est une maladie infectieuse nécessitant un traitement prolongé et continu. Toute rupture de stock d'antituberculeux compromet l'efficacité du traitement et favorise les résistances ; leur disponibilité doit être assurée sans interruption."],

['mots'=>['vih','sida','antiretroviraux','arv','traitement vih'],
 'rep'=>"Les traitements antirétroviraux (ARV) doivent être pris sans interruption. La continuité de leur approvisionnement est une priorité absolue, toute rupture exposant les patients à des risques graves. La plateforme assure le suivi rapproché de ces produits."],

['mots'=>['hypertension','tension arterielle','antihypertenseur','maladie chronique'],
 'rep'=>"L'hypertension artérielle est une maladie chronique nécessitant un traitement régulier et durable. La disponibilité constante des antihypertenseurs dans les officines de proximité est essentielle pour la continuité des soins."],

['mots'=>['diabete','insuline','antidiabetique','glycemie'],
 'rep'=>"Le diabète requiert un traitement continu, dont l'insuline pour certains patients. L'insuline étant thermosensible, sa conservation relève de la chaîne du froid. Sa disponibilité régulière est indispensable à la prise en charge des patients."],

['mots'=>['stupefiant','medicament controle','psychotrope','produit reglemente'],
 'rep'=>"Les stupéfiants et psychotropes font l'objet d'une réglementation stricte de détention, de prescription et de dispensation. Leur gestion exige une traçabilité rigoureuse et des conditions de stockage sécurisées."],

['mots'=>['don de medicament','medicament donne','dons humanitaires'],
 'rep'=>"Les dons de médicaments doivent respecter des critères de qualité, de durée de validité et de pertinence par rapport aux besoins. Un don inadapté peut générer des surcoûts de gestion et des risques sanitaires."],

['mots'=>['destruction medicament','eliminer medicament','dechet pharmaceutique','medicament a detruire'],
 'rep'=>"Les médicaments périmés ou impropres doivent être éliminés selon des procédures encadrées afin de prévenir tout risque sanitaire et environnemental. Leur destruction ne doit jamais s'effectuer par des circuits ordinaires."],

['mots'=>['contrefacon','faux medicament','medicament falsifie','marche parallele'],
 'rep'=>"Les médicaments falsifiés représentent un danger sanitaire majeur. Le circuit officiel encadré par l'État, dont cette plateforme assure la traçabilité, constitue une garantie contre l'introduction de produits non conformes."],

['mots'=>['premiers secours','trousse urgence','medicament urgence','kit urgence'],
 'rep'=>"Une dotation de produits de première nécessité (antalgiques, antiseptiques, réhydratants, matériel de pansement) doit être maintenue en permanence dans les structures de soins pour répondre aux situations d'urgence."],

['mots'=>['femme enceinte','medicament grossesse','traitement grossesse'],
 'rep'=>"La dispensation de médicaments chez la femme enceinte exige une prudence particulière en raison des contre-indications. Toute prescription doit être respectée et la disponibilité des produits de santé maternelle assurée en priorité."],

['mots'=>['enfant','pediatrie','medicament enfant','dosage enfant'],
 'rep'=>"Les médicaments à usage pédiatrique requièrent des formes et dosages adaptés. La disponibilité des produits essentiels pour l'enfant, notamment les antipaludiques et les réhydratants, constitue une priorité de santé publique."],

['mots'=>['rougeole','meningite','fievre jaune','maladie a declaration'],
 'rep'=>"Certaines maladies à potentiel épidémique (rougeole, méningite, fièvre jaune) font l'objet d'une surveillance renforcée. Une hausse anormale des cas ou de la consommation des produits associés doit être signalée sans délai aux autorités sanitaires."],

['mots'=>['stock de securite','stock tampon','reserve strategique','stock minimal'],
 'rep'=>"Un stock de sécurité est une réserve destinée à absorber les variations imprévues de la demande ou les retards d'approvisionnement. Son maintien permet d'éviter les ruptures sur les produits essentiels."],

['mots'=>['rotation stock','fefo','premier perime premier sorti','gestion lots'],
 'rep'=>"La règle FEFO (« premier périmé, premier sorti ») consiste à dispenser en priorité les lots dont la date de péremption est la plus proche. Cette pratique limite les pertes liées aux péremptions."],

];
