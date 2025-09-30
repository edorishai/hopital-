<?php

session_start();

// Configuration de la connexion à la base de données
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // À CHANGER si différent
define('DB_PASSWORD', 'sio2024');
define('DB_NAME', 'LPFS');

$pdo = null;

try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8");

    // Vérification/Création des tables et insertion des médecins (pour garantir la sélection)
    initialiser_base($pdo);

} catch (PDOException $e) {
    die("ERREUR : Impossible de se connecter à la base de données. " . $e->getMessage());
}

/**
 * Fonction d'initialisation de la base de données.
 * Crée les tables si elles n'existent pas et insère les médecins.
 */
function initialiser_base($pdo) {
    // Le SQL complet pour la création des tables doit être ici ou dans un script séparé.
    // Pour cet exemple, nous allons juste vérifier la table MEDECIN.
    
    // Tentative de créer la table MEDECIN (si elle n'existe pas)
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS MEDECIN (
                med_id INT PRIMARY KEY AUTO_INCREMENT,
                nom VARCHAR(100) NOT NULL,
                specialite VARCHAR(100)
            );
        ");

        // Vérification et insertion des médecins si la table est vide
        $count = $pdo->query("SELECT COUNT(*) FROM MEDECIN")->fetchColumn();
        if ($count == 0) {
            $pdo->exec("
                INSERT INTO MEDECIN (nom, specialite) VALUES
                ('Dr. GOUSSE Marc', 'Chirurgie Digestive'),
                ('Dr. DUPONT Marie', 'Anesthésie-Réanimation'),
                ('Dr. LEROY Alain', 'Orthopédie');
            ");
        }
        
        // Initialiser les autres tables (similaire à la première réponse SQL)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS PATIENT (...) ;
            CREATE TABLE IF NOT EXISTS PERSONNE (...) ;
            CREATE TABLE IF NOT EXISTS PRE_ADMISSION (...) ;
            CREATE TABLE IF NOT EXISTS DOCUMENT (...) ;
            CREATE TABLE IF NOT EXISTS DOCUMENT_JOINT (...) ;
        ");
        
        // Initialisation des documents requis si la table est vide
        $count_doc = $pdo->query("SELECT COUNT(*) FROM DOCUMENT")->fetchColumn();
        if ($count_doc == 0) {
             $pdo->exec("
                INSERT INTO DOCUMENT (nom_document) VALUES
                ('Carte d''identité (recto / verso)'),
                ('Carte de mutuelle'),
                ('Carte vitale'),
                ('Livret de famille (pour mineurs)');
            ");
        }
        
    } catch (PDOException $e) {
        // Ignorer si la table existe déjà
    }
}
?>