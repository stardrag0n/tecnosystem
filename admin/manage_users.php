<?php
session_start();
// Protezione: se non è loggato, torna al login (presumendo tu abbia una sessione 'admin_logged')
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'db.php'; // Usa la connessione già esistente

$messaggio = "";

// --- LOGICA AGGIUNTA UTENTE ---
if (isset($_POST['add_user'])) {
    $user = trim($_POST['new_username']);
    $pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    try {
        // Usiamo 'users' come nel tuo login.php
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$user, $pass]);
        $messaggio = "Utente aggiunto con successo!";
    } catch (PDOException $e) {
        $messaggio = "Errore: l'utente esiste già.";
    }
}

// --- LOGICA RIMOZIONE UTENTE ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Protezione: evita di cancellare te stesso se sei l'unico loggato
    if ($id == $_SESSION['user_id']) {
        $messaggio = "Non puoi rimuovere l'utente con cui sei loggato.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $messaggio = "Utente rimosso.";
    }
}

// --- RECUPERO UTENTI ---
$users = $pdo->query("SELECT id, username FROM users")->fetchAll();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Utenti - Tecno System</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 40px; }
        .container { max-width: 600px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        .btn-del { color: red; text-decoration: none; font-weight: bold; }
        .form-add { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #eee; }
        input { padding: 8px; margin-right: 10px; }
        button { padding: 8px 15px; background: #1a55e3; color: white; border: none; cursor: pointer; }
    </style>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Utenti - Tecno System</title>
    <style>
        body { font-family: sans-serif; background: #050505; color: white; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background: #111; padding: 30px; border: 1px solid #333; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #333; text-align: left; }
        input { padding: 10px; background: #222; border: 1px solid #444; color: white; margin-bottom: 10px; width: 100%; box-sizing: border-box; }
        button { padding: 10px 20px; background: #1a55e3; color: white; border: none; cursor: pointer; width: 100%; font-weight: bold; }
        .btn-del { color: #ff4444; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Gestione Amministratori</h2>
        <?php if($messaggio) echo "<p style='color: #1a55e3;'>$messaggio</p>"; ?>

        <form method="POST" style="margin-bottom: 40px;">
            <h3>Aggiungi Utente</h3>
            <input type="text" name="new_username" placeholder="Nuovo Username" required>
            <input type="password" name="new_password" placeholder="Nuova Password" required>
            <button type="submit" name="add_user">AGGIUNGI</button>
        </form>

        <h3>Utenti Registrati</h3>
        <table>
            <tr><th>Username</th><th>Azione</th></tr>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><a href="?delete=<?= $u['id'] ?>" class="btn-del" onclick="return confirm('Rimuovere l\'utente?')">Rimuovi</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <p><a href="dashboard.php" style="color: #888; text-decoration: none;">← Torna alla Dashboard</a></p>
    </div>
</body>
</html>