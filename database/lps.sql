-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : ven. 04 sep. 2026 à 11:52
-- Version du serveur : 8.0.40
-- Version de PHP : 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `lps`
--

-- --------------------------------------------------------

--
-- Structure de la table `alertes`
--

DROP TABLE IF EXISTS `alertes`;
CREATE TABLE IF NOT EXISTS `alertes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `stock_id` int NOT NULL,
  `type_alerte` enum('rupture_imminente','stock_bas','peremption_proche','peremption_depassee') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `priorite` enum('info','alerte','critique') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'alerte',
  `lue` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `stock_id` (`stock_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `autorisations_pra`
--

DROP TABLE IF EXISTS `autorisations_pra`;
CREATE TABLE IF NOT EXISTS `autorisations_pra` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pharmacie_id` int NOT NULL,
  `medicament_id` int NOT NULL,
  `pra_origine_id` int NOT NULL,
  `pra_cible_id` int NOT NULL,
  `initiateur` enum('pharmacie','pra') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pharmacie',
  `motif` text COLLATE utf8mb4_unicode_ci,
  `reponse` text COLLATE utf8mb4_unicode_ci,
  `statut` enum('en_attente','accordee','refusee','revoquee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `traite_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `medicament_id` (`medicament_id`),
  KEY `pra_origine_id` (`pra_origine_id`),
  KEY `pra_cible_id` (`pra_cible_id`),
  KEY `idx_autor_pharma` (`pharmacie_id`,`medicament_id`,`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `avertissements`
--

DROP TABLE IF EXISTS `avertissements`;
CREATE TABLE IF NOT EXISTS `avertissements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int NOT NULL,
  `type` enum('suspension','suppression') COLLATE utf8mb4_unicode_ci NOT NULL,
  `motif` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `emis_par` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `applicable_le` datetime NOT NULL,
  `annule` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `emis_par` (`emis_par`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

DROP TABLE IF EXISTS `commandes`;
CREATE TABLE IF NOT EXISTS `commandes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `demandeur_id` int NOT NULL,
  `pra_cible_id` int DEFAULT NULL,
  `validateur_id` int DEFAULT NULL,
  `fournisseur_id` int DEFAULT NULL,
  `statut` enum('en_attente','validee','rejetee','en_transit','livree') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `urgence` enum('normale','alerte','critique') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normale',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `date_commande` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_validation` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `demandeur_id` (`demandeur_id`),
  KEY `validateur_id` (`validateur_id`),
  KEY `fournisseur_id` (`fournisseur_id`),
  KEY `fk_cmd_pra_cible` (`pra_cible_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `demandes_acces`
--

DROP TABLE IF EXISTS `demandes_acces`;
CREATE TABLE IF NOT EXISTS `demandes_acces` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `structure_nom` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_demande` enum('pra','pharmacie','fournisseur') COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('en_attente','approuvee','rejetee','suspecte') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `traite_par` int DEFAULT NULL,
  `notes_admin` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `traite_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `traite_par` (`traite_par`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `demandes_reset`
--

DROP TABLE IF EXISTS `demandes_reset`;
CREATE TABLE IF NOT EXISTS `demandes_reset` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('en_attente','autorisee','refusee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `traite_par` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `traite_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `traite_par` (`traite_par`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `fournisseurs`
--

DROP TABLE IF EXISTS `fournisseurs`;
CREATE TABLE IF NOT EXISTS `fournisseurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `structure_id` int DEFAULT NULL,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialite` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taux_ponctualite` decimal(5,2) DEFAULT '100.00',
  `statut` enum('actif','suspendu','sous_surveillance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'actif',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `structure_id` (`structure_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fournisseurs`
--

INSERT INTO `fournisseurs` (`id`, `structure_id`, `nom`, `contact`, `email`, `telephone`, `specialite`, `taux_ponctualite`, `statut`, `created_at`) VALUES
(1, 6, 'PharmaPlus SA', NULL, 'contact@pharmaplus.sn', NULL, 'Antibiotiques, generiques', 98.00, 'actif', '2026-09-03 20:38:03');

-- --------------------------------------------------------

--
-- Structure de la table `lignes_commande`
--

DROP TABLE IF EXISTS `lignes_commande`;
CREATE TABLE IF NOT EXISTS `lignes_commande` (
  `id` int NOT NULL AUTO_INCREMENT,
  `commande_id` int NOT NULL,
  `medicament_id` int NOT NULL,
  `quantite_demandee` int NOT NULL,
  `quantite_livree` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `commande_id` (`commande_id`),
  KEY `medicament_id` (`medicament_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `livraisons`
--

DROP TABLE IF EXISTS `livraisons`;
CREATE TABLE IF NOT EXISTS `livraisons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `commande_id` int NOT NULL,
  `livreur_id` int DEFAULT NULL,
  `date_livraison` date DEFAULT NULL,
  `statut` enum('planifiee','en_route','livree','echec') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planifiee',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `commande_id` (`commande_id`),
  KEY `livreur_id` (`livreur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `medicaments`
--

DROP TABLE IF EXISTS `medicaments`;
CREATE TABLE IF NOT EXISTS `medicaments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `forme` enum('comprime','gelule','sirop','injection','sachet','pommade','autre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `dosage` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categorie` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `seuil_alerte` int NOT NULL DEFAULT '500',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `medicaments`
--

INSERT INTO `medicaments` (`id`, `nom`, `forme`, `dosage`, `categorie`, `description`, `seuil_alerte`, `created_at`) VALUES
(1, 'Amoxicilline', 'gelule', '500mg', 'Antibiotique', NULL, 500, '2026-09-03 20:38:03'),
(2, 'Paracetamol', 'comprime', '500mg', 'Antalgique', NULL, 500, '2026-09-03 20:38:03'),
(3, 'Paracetamol', 'comprime', '1g', 'Antalgique', NULL, 500, '2026-09-03 20:38:03'),
(4, 'SRO', 'sachet', '1L', 'Rehydratation', NULL, 300, '2026-09-03 20:38:03'),
(5, 'Artemether', 'injection', '80mg', 'Antipaludique', NULL, 200, '2026-09-03 20:38:03'),
(6, 'Metronidazole', 'comprime', '250mg', 'Antibiotique', NULL, 400, '2026-09-03 20:38:03'),
(7, 'Fer + Acide folique', 'comprime', '200mg', 'Supplement', NULL, 600, '2026-09-03 20:38:03'),
(8, 'Vitamine C', 'comprime', '500mg', 'Supplement', NULL, 200, '2026-09-03 20:38:03');

-- --------------------------------------------------------

--
-- Structure de la table `parametres`
--

DROP TABLE IF EXISTS `parametres`;
CREATE TABLE IF NOT EXISTS `parametres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categorie` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cle` (`cle`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parametres`
--

INSERT INTO `parametres` (`id`, `cle`, `valeur`, `libelle`, `categorie`, `updated_at`) VALUES
(1, 'seuil_surstock', '80', 'Seuil de surstock (%)', 'moteur_ia', '2026-09-04 02:09:35'),
(2, 'delai_suspension', '10', 'Délai avant suspension (jours)', 'sanctions', '2026-09-04 02:09:35'),
(3, 'delai_suppression', '15', 'Délai avant suppression (jours)', 'sanctions', '2026-09-04 02:09:35');

-- --------------------------------------------------------

--
-- Structure de la table `reequilibrages`
--

DROP TABLE IF EXISTS `reequilibrages`;
CREATE TABLE IF NOT EXISTS `reequilibrages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `medicament_id` int NOT NULL,
  `source_id` int DEFAULT NULL,
  `destination_id` int NOT NULL,
  `quantite` int NOT NULL,
  `origine` enum('pra','ia') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pra',
  `signale_par` int DEFAULT NULL,
  `priorite` enum('moderee','critique') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'moderee',
  `justification` text COLLATE utf8mb4_unicode_ci,
  `statut` enum('en_attente','validee','rejetee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `valide_par` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `traite_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `medicament_id` (`medicament_id`),
  KEY `destination_id` (`destination_id`),
  KEY `signale_par` (`signale_par`),
  KEY `valide_par` (`valide_par`),
  KEY `fk_reeq_source` (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `stocks`
--

DROP TABLE IF EXISTS `stocks`;
CREATE TABLE IF NOT EXISTS `stocks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `medicament_id` int NOT NULL,
  `structure_id` int NOT NULL,
  `quantite` int NOT NULL DEFAULT '0',
  `numero_lot` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_peremption` date DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `medicament_id` (`medicament_id`),
  KEY `structure_id` (`structure_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stocks`
--

INSERT INTO `stocks` (`id`, `medicament_id`, `structure_id`, `quantite`, `numero_lot`, `date_peremption`, `updated_at`) VALUES
(1, 1, 1, 120, 'DK2025-19', '2026-12-31', '2026-09-03 20:38:03'),
(2, 2, 1, 8240, 'DK2025-22', '2027-06-30', '2026-09-03 20:38:03'),
(3, 3, 1, 5000, 'DK2025-23', '2027-06-30', '2026-09-03 20:38:03'),
(4, 4, 1, 2400, 'DK2025-21', '2027-03-31', '2026-09-03 20:38:03'),
(5, 5, 1, 500, 'DK2025-20', '2026-11-30', '2026-09-03 20:38:03'),
(6, 6, 1, 280, 'DK2025-18', '2026-09-30', '2026-09-03 20:38:03'),
(7, 7, 1, 350, 'DK2025-17', '2026-08-31', '2026-09-03 20:38:03'),
(8, 8, 1, 1200, 'DK2024-08', '2025-07-05', '2026-09-03 20:38:03');

-- --------------------------------------------------------

--
-- Structure de la table `structures`
--

DROP TABLE IF EXISTS `structures`;
CREATE TABLE IF NOT EXISTS `structures` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('pra','pharmacie','fournisseur') COLLATE utf8mb4_unicode_ci NOT NULL,
  `region` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone` enum('ville','village','rural') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pra_parent` int DEFAULT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `statut` enum('active','suspendue') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_structures_pra_parent` (`pra_parent`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `structures`
--

INSERT INTO `structures` (`id`, `nom`, `type`, `region`, `zone`, `pra_parent`, `adresse`, `telephone`, `email`, `latitude`, `longitude`, `statut`, `created_at`) VALUES
(1, 'PRA Dakar', 'pra', 'Dakar', 'ville', NULL, 'Av. Bourguiba, Dakar', '33 821 00 01', 'pra.dakar@sante.sn', 14.6928000, -17.4467000, 'active', '2026-09-03 20:38:03'),
(2, 'PRA Thies', 'pra', 'Thies', 'ville', NULL, 'Rue 10, Thies', '33 951 00 02', 'pra.thies@sante.sn', 14.7886000, -16.9246000, 'active', '2026-09-03 20:38:03'),
(3, 'PRA Kaolack', 'pra', 'Kaolack', 'ville', NULL, 'Centre-ville, Kaolack', '33 941 00 03', 'pra.kaolack@sante.sn', 14.1652000, -16.0726000, 'active', '2026-09-03 20:38:03'),
(4, 'Pharmacie Centrale HLM', 'pharmacie', 'Dakar', 'ville', 3, 'HLM, Dakar', '33 824 10 10', 'hlm@pharmacie.sn', 14.7150000, -17.4670000, 'active', '2026-09-03 20:38:03'),
(5, 'Pharmacie Ouakam', 'pharmacie', 'Dakar', 'ville', 1, 'Ouakam, Dakar', '33 820 22 22', 'ouakam@pharmacie.sn', 14.6850000, -17.4750000, 'active', '2026-09-03 20:38:03'),
(6, 'PharmaPlus SA', 'fournisseur', 'Dakar', 'ville', NULL, 'Zone Industrielle, Dakar', '33 830 00 50', 'contact@pharmaplus.sn', 14.7000000, -17.4400000, 'active', '2026-09-03 20:38:03');

-- --------------------------------------------------------

--
-- Structure de la table `subventions`
--

DROP TABLE IF EXISTS `subventions`;
CREATE TABLE IF NOT EXISTS `subventions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pharmacie_id` int NOT NULL,
  `signale_par` int NOT NULL,
  `medicaments` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant_estime` decimal(12,2) NOT NULL DEFAULT '0.00',
  `motif` text COLLATE utf8mb4_unicode_ci,
  `statut` enum('en_attente','approuvee','rejetee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `valide_par` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `traite_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pharmacie_id` (`pharmacie_id`),
  KEY `signale_par` (`signale_par`),
  KEY `valide_par` (`valide_par`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doit_changer_mdp` tinyint(1) NOT NULL DEFAULT '0',
  `token_reset` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_expire` datetime DEFAULT NULL,
  `derniere_reset` datetime DEFAULT NULL,
  `reset_autorise` tinyint(1) NOT NULL DEFAULT '0',
  `role` enum('etat','pra','pharmacie','fournisseur') COLLATE utf8mb4_unicode_ci NOT NULL,
  `structure_id` int DEFAULT NULL,
  `statut` enum('actif','suspendu','en_attente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `structure_id` (`structure_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `doit_changer_mdp`, `token_reset`, `token_expire`, `derniere_reset`, `reset_autorise`, `role`, `structure_id`, `statut`, `created_at`, `last_login`) VALUES
(1, 'Ndiaye', 'Fatou', 'admin@sante.sn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, NULL, NULL, NULL, 0, 'etat', NULL, 'actif', '2026-09-03 20:38:03', '2026-09-04 11:14:40'),
(2, 'Diop', 'Moussa', 'pra.dakar@sante.sn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, NULL, NULL, NULL, 0, 'pra', 1, 'actif', '2026-09-03 20:38:03', NULL),
(3, 'Sow', 'Aissatou', 'hlm@pharmacie.sn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, NULL, NULL, NULL, 0, 'pharmacie', 4, 'actif', '2026-09-03 20:38:03', '2026-09-04 11:25:01'),
(4, 'Fall', 'Ibrahim', 'contact@pharmaplus.sn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, NULL, NULL, NULL, 0, 'fournisseur', 6, 'actif', '2026-09-03 20:38:03', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `ventes`
--

DROP TABLE IF EXISTS `ventes`;
CREATE TABLE IF NOT EXISTS `ventes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `structure_id` int NOT NULL,
  `medicament_id` int NOT NULL,
  `quantite` int NOT NULL,
  `date_vente` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `structure_id` (`structure_id`),
  KEY `idx_ventes_date` (`date_vente`),
  KEY `idx_ventes_med` (`medicament_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ventes`
--

INSERT INTO `ventes` (`id`, `structure_id`, `medicament_id`, `quantite`, `date_vente`) VALUES
(1, 4, 2, 40, '2026-09-02 02:09:35'),
(2, 4, 2, 35, '2026-08-30 02:09:35'),
(3, 4, 2, 50, '2026-08-27 02:09:35'),
(4, 4, 4, 20, '2026-09-01 02:09:35'),
(5, 4, 4, 25, '2026-08-25 02:09:35'),
(6, 4, 5, 15, '2026-09-03 02:09:35'),
(7, 4, 5, 18, '2026-08-31 02:09:35'),
(8, 4, 5, 22, '2026-08-28 02:09:35'),
(9, 4, 5, 30, '2026-08-26 02:09:35');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `alertes`
--
ALTER TABLE `alertes`
  ADD CONSTRAINT `alertes_ibfk_1` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `autorisations_pra`
--
ALTER TABLE `autorisations_pra`
  ADD CONSTRAINT `autorisations_pra_ibfk_1` FOREIGN KEY (`pharmacie_id`) REFERENCES `structures` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `autorisations_pra_ibfk_2` FOREIGN KEY (`medicament_id`) REFERENCES `medicaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `autorisations_pra_ibfk_3` FOREIGN KEY (`pra_origine_id`) REFERENCES `structures` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `autorisations_pra_ibfk_4` FOREIGN KEY (`pra_cible_id`) REFERENCES `structures` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `avertissements`
--
ALTER TABLE `avertissements`
  ADD CONSTRAINT `avertissements_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avertissements_ibfk_2` FOREIGN KEY (`emis_par`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`demandeur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `commandes_ibfk_2` FOREIGN KEY (`validateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `commandes_ibfk_3` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cmd_pra_cible` FOREIGN KEY (`pra_cible_id`) REFERENCES `structures` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `demandes_acces`
--
ALTER TABLE `demandes_acces`
  ADD CONSTRAINT `demandes_acces_ibfk_1` FOREIGN KEY (`traite_par`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `demandes_reset`
--
ALTER TABLE `demandes_reset`
  ADD CONSTRAINT `demandes_reset_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `demandes_reset_ibfk_2` FOREIGN KEY (`traite_par`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  ADD CONSTRAINT `fournisseurs_ibfk_1` FOREIGN KEY (`structure_id`) REFERENCES `structures` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `lignes_commande`
--
ALTER TABLE `lignes_commande`
  ADD CONSTRAINT `lignes_commande_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lignes_commande_ibfk_2` FOREIGN KEY (`medicament_id`) REFERENCES `medicaments` (`id`) ON DELETE RESTRICT;

--
-- Contraintes pour la table `livraisons`
--
ALTER TABLE `livraisons`
  ADD CONSTRAINT `livraisons_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `livraisons_ibfk_2` FOREIGN KEY (`livreur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `reequilibrages`
--
ALTER TABLE `reequilibrages`
  ADD CONSTRAINT `fk_reeq_source` FOREIGN KEY (`source_id`) REFERENCES `structures` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reequilibrages_ibfk_1` FOREIGN KEY (`medicament_id`) REFERENCES `medicaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reequilibrages_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `structures` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reequilibrages_ibfk_3` FOREIGN KEY (`signale_par`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reequilibrages_ibfk_4` FOREIGN KEY (`valide_par`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `stocks`
--
ALTER TABLE `stocks`
  ADD CONSTRAINT `stocks_ibfk_1` FOREIGN KEY (`medicament_id`) REFERENCES `medicaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stocks_ibfk_2` FOREIGN KEY (`structure_id`) REFERENCES `structures` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `structures`
--
ALTER TABLE `structures`
  ADD CONSTRAINT `fk_structures_pra_parent` FOREIGN KEY (`pra_parent`) REFERENCES `structures` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `subventions`
--
ALTER TABLE `subventions`
  ADD CONSTRAINT `subventions_ibfk_1` FOREIGN KEY (`pharmacie_id`) REFERENCES `structures` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subventions_ibfk_2` FOREIGN KEY (`signale_par`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subventions_ibfk_3` FOREIGN KEY (`valide_par`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `utilisateurs_ibfk_1` FOREIGN KEY (`structure_id`) REFERENCES `structures` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `ventes`
--
ALTER TABLE `ventes`
  ADD CONSTRAINT `ventes_ibfk_1` FOREIGN KEY (`structure_id`) REFERENCES `structures` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ventes_ibfk_2` FOREIGN KEY (`medicament_id`) REFERENCES `medicaments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
