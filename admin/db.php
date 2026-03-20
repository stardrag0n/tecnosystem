<?php
// Percorso del file database interno
$db_path = __DIR__ . '/../data/database.sqlite';

try {
    // Connessione a SQLite
    $pdo = new PDO("sqlite:" . $db_path);
    // Imposta la modalità di errore per vedere se qualcosa non va
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Imposta il fetch di default come array associativo
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Errore di connessione al database: " . $e->getMessage());
}
?>