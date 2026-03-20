<?php
require 'db.php';

echo "<h1>Setup Completo Database</h1>";

try {
    // ---------------------------------------------------------
    // 1. TABELLA UTENTI (Admin)
    // ---------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL
    )");
    
    // Crea Admin di default se non esiste
    $checkUser = $pdo->query("SELECT count(*) FROM users WHERE username = 'Admin'")->fetchColumn();
    if ($checkUser == 0) {
        $pass = password_hash('Password123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, password) VALUES ('Admin', ?)")->execute([$pass]);
        echo "✅ Utente Admin creato (Admin / Password123)<br>";
    } else {
        echo "✅ Utente Admin esistente<br>";
    }

    // ---------------------------------------------------------
    // 2. TABELLA PRODOTTI (Soluzioni)
    // ---------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id TEXT NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        image_path TEXT,
        gallery_images TEXT,
        sort_order INTEGER DEFAULT 0,
        is_visible INTEGER DEFAULT 1
    )");
    
    // Fix colonne mancanti (per chi aveva vecchie versioni)
    try { $pdo->exec("ALTER TABLE products ADD COLUMN is_visible INTEGER DEFAULT 1"); } catch(Exception $e){}
    try { $pdo->exec("ALTER TABLE products ADD COLUMN sort_order INTEGER DEFAULT 0"); } catch(Exception $e){}
    try { $pdo->exec("ALTER TABLE products ADD COLUMN gallery_images TEXT"); } catch(Exception $e){}
    echo "✅ Tabella Prodotti OK<br>";

    // ---------------------------------------------------------
    // 3. TABELLA SERVIZI
    // ---------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        image_path TEXT,
        sort_order INTEGER DEFAULT 0,
        is_visible INTEGER DEFAULT 1
    )");
    echo "✅ Tabella Servizi OK<br>";

    // ---------------------------------------------------------
    // 4. NUOVA TABELLA GALLERY (Ambient Images)
    // ---------------------------------------------------------
    // Molto semplice: solo ID e percorso file. Non servono titoli.
    $pdo->exec("CREATE TABLE IF NOT EXISTS gallery (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        image_path TEXT NOT NULL
    )");
    echo "✅ Tabella Ambient Gallery OK (Nuova!)<br>";

    echo "<hr><h3>🎉 Database Aggiornato con Successo!</h3>";
    echo "<a href='dashboard.php'>Vai alla Dashboard</a>";

} catch (PDOException $e) {
    die("Errore Setup: " . $e->getMessage());
}
?>