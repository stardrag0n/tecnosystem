<?php
session_start();
require 'db.php';

// Controllo Accesso
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// Helper per Slug
function createSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    return trim(preg_replace('/-+/', '-', $string), '-');
}

// Helper per Redirect con Messaggio e Ancora
function redirectWithAnchor($anchor, $msg = '') {
    if ($msg) $_SESSION['message'] = $msg;
    header("Location: dashboard.php#" . $anchor);
    exit;
}

// =========================================================
// 1. LOGICA PHP (Gestione POST)
// =========================================================

// --- A. GESTIONE PRODOTTI ---

// Sposta Ordine
if (isset($_POST['move_order'])) {
    $id = $_POST['product_id'];
    $current_order = $_POST['current_order'];
    $direction = $_POST['direction'];
    
    if ($direction == 'up') {
        $stmt = $pdo->prepare("SELECT id, sort_order FROM products WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1");
    } else {
        $stmt = $pdo->prepare("SELECT id, sort_order FROM products WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
    }
    $stmt->execute([$current_order]);
    $swap_target = $stmt->fetch();

    if ($swap_target) {
        $pdo->prepare("UPDATE products SET sort_order = ? WHERE id = ?")->execute([$swap_target['sort_order'], $id]);
        $pdo->prepare("UPDATE products SET sort_order = ? WHERE id = ?")->execute([$current_order, $swap_target['id']]);
    }
    redirectWithAnchor("card_" . $id);
}

// Visibilità
if (isset($_POST['toggle_visibility'])) {
    $pdo->prepare("UPDATE products SET is_visible = ? WHERE id = ?")
        ->execute([$_POST['new_status'], $_POST['product_id']]);
    redirectWithAnchor("card_" . $_POST['product_id']);
}

// Elimina Prodotto Completo
if (isset($_POST['delete_product_completely'])) {
    $id = $_POST['product_id'];
    $row = $pdo->prepare("SELECT image_path, gallery_images FROM products WHERE id = ?"); $row->execute([$id]); 
    $data = $row->fetch();
    if($data) {
        if (!empty($data['image_path']) && file_exists('../' . $data['image_path'])) unlink('../' . $data['image_path']);
        $gal = !empty($data['gallery_images']) ? json_decode($data['gallery_images'], true) : [];
        foreach ($gal as $img) if (file_exists('../' . $img)) unlink('../' . $img);
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        $_SESSION['message'] = "Prodotto eliminato correttamente.";
    }
    header("Location: dashboard.php"); // Torna in cima perché il prodotto non esiste più
    exit;
}

// Crea Nuovo Prodotto
if (isset($_POST['create_new_product'])) {
    $title = $_POST['new_title'];
    $slug = createSlug($title);
    if (!empty($title)) {
        $max = $pdo->query("SELECT MAX(sort_order) FROM products")->fetchColumn();
        $next_order = $max ? $max + 1 : 1;
        $pdo->prepare("INSERT INTO products (category_id, title, description, sort_order, is_visible) VALUES (?, ?, ?, ?, 1)")
            ->execute([$slug, $title, "Descrizione...", $next_order]);
        
        // Recuperiamo l'ID appena creato per saltare lì
        $new_id = $pdo->lastInsertId();
        redirectWithAnchor("card_" . $new_id, "Nuovo prodotto creato!");
    }
}

// --- B. SALVATAGGIO PRODOTTI & IMMAGINI ---

// Elimina foto singola dalla galleria PRODOTTO
if (isset($_POST['delete_product_gallery_image'])) {
    $id = $_POST['product_id']; $img = $_POST['image_path'];
    $row = $pdo->prepare("SELECT gallery_images FROM products WHERE id = ?"); $row->execute([$id]);
    $gal = json_decode($row->fetchColumn(), true) ?? [];
    if (($key = array_search($img, $gal)) !== false) {
        unset($gal[$key]);
        if (file_exists('../'.$img)) unlink('../'.$img);
        $pdo->prepare("UPDATE products SET gallery_images = ? WHERE id = ?")->execute([json_encode(array_values($gal)), $id]);
    }
    redirectWithAnchor("card_" . $id);
}

// Salva modifiche Prodotto (inclusi Upload)
if (isset($_POST['save_product'])) {
    $id = $_POST['id']; $title = $_POST['title']; $desc = $_POST['description'];
    
    // 1. Copertina
    $mainPath = $_POST['current_main_image']; 
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === 0) {
        if(!empty($mainPath) && file_exists('../'.$mainPath)) unlink('../'.$mainPath);
        $name = time().'_main_'.basename($_FILES['main_image']['name']);
        if(move_uploaded_file($_FILES['main_image']['tmp_name'], '../uploads/'.$name)) $mainPath = 'uploads/'.$name;
    }

    // 2. Galleria Prodotto
    $galJson = $_POST['current_gallery_json']; 
    $galArr = !empty($galJson) ? json_decode($galJson, true) : [];
    if (isset($_FILES['gallery_images'])) {
        $count = count($_FILES['gallery_images']['name']);
        for ($i=0; $i<$count; $i++) {
            if ($_FILES['gallery_images']['error'][$i] === 0) {
                $name = time().'_'.$i.'_'.basename($_FILES['gallery_images']['name'][$i]);
                if(move_uploaded_file($_FILES['gallery_images']['tmp_name'][$i], '../uploads/'.$name)) $galArr[] = 'uploads/'.$name;
            }
        }
    }
    
    $pdo->prepare("UPDATE products SET title=?, description=?, image_path=?, gallery_images=? WHERE id=?")
        ->execute([$title, $desc, $mainPath, json_encode($galArr), $id]);
    
    redirectWithAnchor("card_" . $id, "Prodotto salvato!");
}

// --- C. GESTIONE SERVIZI ---

// Crea Nuovo Servizio
if (isset($_POST['create_new_service'])) {
    $title = $_POST['new_title'];
    if (!empty($title)) {
        $max = $pdo->query("SELECT MAX(sort_order) FROM services")->fetchColumn();
        $next_order = $max ? $max + 1 : 1;
        $pdo->prepare("INSERT INTO services (title, description, sort_order, is_visible) VALUES (?, ?, ?, 1)")
            ->execute([$title, "Descrizione...", $next_order]);
        
        $new_id = $pdo->lastInsertId();
        redirectWithAnchor("service_" . $new_id, "Nuovo servizio creato!");
    }
}

// Elimina Servizio Completo
if (isset($_POST['delete_service_completely'])) {
    $id = $_POST['service_id'];
    $row = $pdo->prepare("SELECT image_path FROM services WHERE id = ?"); $row->execute([$id]); 
    $data = $row->fetch();
    if($data) {
        if (!empty($data['image_path']) && file_exists('../' . $data['image_path'])) unlink('../' . $data['image_path']);
        $pdo->prepare("DELETE FROM services WHERE id = ?")->execute([$id]);
        $_SESSION['message'] = "Servizio eliminato correttamente.";
    }
    header("Location: dashboard.php"); // Torna in cima perché il servizio non esiste più
    exit;
}

if (isset($_POST['update_service'])) {
    $id = $_POST['id']; $title = $_POST['title']; $desc = $_POST['description'];
    $srvPath = $_POST['current_image'];
    
    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] === 0) {
        if(!empty($srvPath) && file_exists('../'.$srvPath)) unlink('../'.$srvPath);
        $name = time().'_srv_'.basename($_FILES['service_image']['name']);
        if(move_uploaded_file($_FILES['service_image']['tmp_name'], '../uploads/'.$name)) $srvPath = 'uploads/'.$name;
    }
    
    $pdo->prepare("UPDATE services SET title=?, description=?, image_path=? WHERE id=?")
        ->execute([$title, $desc, $srvPath, $id]);
    
    redirectWithAnchor("service_" . $id, "Servizio aggiornato!");
}

// --- D. GESTIONE GALLERIA GENERALE (Tabella 'gallery') ---
if (isset($_POST['upload_general_gallery'])) {
    $count = count($_FILES['general_images']['name']);
    for ($i=0; $i<$count; $i++) {
        if ($_FILES['general_images']['error'][$i] === 0) {
            $name = time().'_gal_'.$i.'_'.basename($_FILES['general_images']['name'][$i]);
            if(move_uploaded_file($_FILES['general_images']['tmp_name'][$i], '../uploads/'.$name)){
                $pdo->prepare("INSERT INTO gallery (image_path) VALUES (?)")->execute(['uploads/'.$name]);
            }
        }
    }
    redirectWithAnchor("general_gallery", "Foto caricate nella Gallery!");
}

if (isset($_POST['delete_gallery_id'])) {
    $id = $_POST['delete_gallery_id'];
    $path = $_POST['delete_gallery_path'];
    if(file_exists('../'.$path)) unlink('../'.$path);
    $pdo->prepare("DELETE FROM gallery WHERE id = ?")->execute([$id]);
    
    redirectWithAnchor("general_gallery");
}

// =========================================================
// 2. RECUPERO DATI E MESSAGGI
// =========================================================
$products = $pdo->query("SELECT * FROM products ORDER BY sort_order ASC")->fetchAll();
$services = $pdo->query("SELECT * FROM services ORDER BY id ASC")->fetchAll();
$gallery_photos = $pdo->query("SELECT * FROM gallery ORDER BY id DESC")->fetchAll();

// Recupera messaggio dalla sessione e poi pulisci
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; background: #f4f4f4; margin: 0; padding-bottom: 80px; }
        header { background: #1a55e3; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        
        /* Messaggio a scomparsa (opzionale con JS) o fisso */
        .message { background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #c3e6cb; position: sticky; top: 70px; z-index: 999; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }

        h2.section-title { color: #333; border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-top: 50px; scroll-margin-top: 100px; } /* scroll-margin aiuta l'ancoraggio */
        
        /* CARD e ANCORAGGIO */
        .product-card { 
            background: white; border-radius: 8px; margin-bottom: 30px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); border: 1px solid #ddd; overflow: hidden; 
            scroll-margin-top: 100px; /* Spazio extra quando si salta all'ID */
        }
        .product-card.hidden-item { opacity: 0.6; border: 2px dashed #ccc; background: #f9f9f9; }
        .card-header { background: #f1f3f5; padding: 10px 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
        .card-body { padding: 20px; }

        input[type="text"], textarea { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        textarea { height: 80px; resize: vertical; }
        label { font-weight: bold; margin-top: 15px; display: block; color: #555; margin-bottom: 5px; }
        
        .save-btn { width: 100%; background: #28a745; color: white; padding: 12px; border: none; font-weight: bold; cursor: pointer; border-radius: 4px; margin-top: 20px; font-size: 16px; }
        .save-btn:hover { background: #218838; }
        .toolbar { display: flex; gap: 10px; }
        .tool-btn { background: white; border: 1px solid #ccc; width: 32px; height: 32px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .btn-x { position: absolute; top: -8px; right: -8px; background: red; color: white; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; cursor: pointer; font-weight: bold; font-size: 12px; }
        
        .gallery-row { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; align-items: center; }
        .thumb-mini { width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.1); background: white; }
        
        .upload-box { border: 2px dashed #ccc; padding: 20px; text-align: center; background: #fdfdfd; border-radius: 6px; cursor: pointer; margin-bottom: 15px; transition: 0.2s; position: relative; }
        .upload-box:hover { background: #f0f4ff; border-color: #1a55e3; }
        .upload-placeholder { color: #888; font-size: 0.9em; pointer-events: none; }
        .upload-placeholder i { font-size: 24px; display: block; margin-bottom: 5px; }
        .create-box { background: #e3f2fd; padding: 20px; border-radius: 8px; margin-bottom: 30px; display: flex; gap: 10px; align-items: center; border: 1px solid #90caf9; }
    </style>
</head>
<body>

<header>
    <h1>Gestione Contenuti</h1>
    <a href="logout.php" style="color:white; text-decoration:none; font-weight:bold;">Esci <i class="fas fa-sign-out-alt"></i></a>
</header>

<div class="container">
    <?php if($message): ?> <div class="message"><?= $message ?></div> <?php endif; ?>

    <h2 class="section-title">Prodotti</h2>
    
    <div class="create-box">
        <h3 style="margin:0; color:#0d47a1; margin-right:10px;">Nuovo:</h3>
        <form method="POST" style="flex:1; display:flex; gap:10px;">
            <input type="text" name="new_title" placeholder="Nome Prodotto" required style="margin:0;">
            <input type="hidden" name="create_new_product" value="1">
            <button type="submit" style="background:#1a55e3; color:white; border:none; padding:10px 20px; border-radius:4px; cursor:pointer;">+ Aggiungi</button>
        </form>
    </div>

    <?php foreach ($products as $index => $p): ?>
        <div id="card_<?= $p['id'] ?>" class="product-card <?= $p['is_visible'] == 0 ? 'hidden-item' : '' ?>">
            
            <div class="card-header">
                <div>
                    <strong><?= htmlspecialchars($p['title']) ?></strong>
                    <?php if($p['is_visible'] == 0): ?> <span style="color:red; font-size:0.8em;">(NASCOSTO)</span> <?php endif; ?>
                </div>
                <div class="toolbar">
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="toggle_visibility" value="1">
                        <input type="hidden" name="new_status" value="<?= $p['is_visible'] == 1 ? 0 : 1 ?>">
                        <button class="tool-btn" title="Mostra/Nascondi"><?= $p['is_visible'] == 1 ? '👁️' : '🚫' ?></button>
                    </form>
                    <?php if ($index > 0): ?>
                    <form method="POST" style="margin:0;"><input type="hidden" name="product_id" value="<?= $p['id'] ?>"><input type="hidden" name="current_order" value="<?= $p['sort_order'] ?>"><input type="hidden" name="move_order" value="1"><input type="hidden" name="direction" value="up"><button class="tool-btn">⬆️</button></form>
                    <?php endif; ?>
                    <?php if ($index < count($products) - 1): ?>
                    <form method="POST" style="margin:0;"><input type="hidden" name="product_id" value="<?= $p['id'] ?>"><input type="hidden" name="current_order" value="<?= $p['sort_order'] ?>"><input type="hidden" name="move_order" value="1"><input type="hidden" name="direction" value="down"><button class="tool-btn">⬇️</button></form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="save_product" value="1">

                    <label>Titolo:</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($p['title']) ?>">
                    <label>Descrizione:</label>
                    <textarea name="description"><?= htmlspecialchars($p['description']) ?></textarea>

                    <label>Copertina:</label>
                    <div class="gallery-row" style="margin-bottom: 5px;">
                        <?php if($p['image_path']): ?>
                            <div style="text-align:center;">
                                <img src="../<?= $p['image_path'] ?>" class="thumb-mini">
                                <div style="font-size:10px; color:#666;">Attuale</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="upload-box" onclick="triggerUpload(<?= $p['id'] ?>, 'cover')">
                        <input type="hidden" name="current_main_image" value="<?= $p['image_path'] ?>">
                        <input type="file" id="fileInput_cover_<?= $p['id'] ?>" name="main_image" style="display:none" onchange="handleFileSelect(this, <?= $p['id'] ?>, 'cover')">
                        <div class="upload-placeholder" id="placeholder_cover_<?= $p['id'] ?>">
                            <i class="fas fa-camera"></i> Clicca per cambiare
                        </div>
                        <div id="previewContainer_cover_<?= $p['id'] ?>" class="gallery-row" style="justify-content:center;"></div>
                    </div>

                    <label>Galleria Prodotto:</label>
                    <?php $gal = !empty($p['gallery_images']) ? json_decode($p['gallery_images'], true) : []; ?>
                    <?php if($gal): ?>
                        <div class="gallery-row" style="margin-bottom: 10px;">
                            <?php foreach($gal as $img): ?>
                                <div style="position: relative;">
                                    <img src="../<?= $img ?>" class="thumb-mini">
                                    <button type="submit" form="del_gal_prod_<?= $p['id'] ?>_<?= md5($img) ?>" class="btn-x">X</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="upload-box" onclick="triggerUpload(<?= $p['id'] ?>, 'gallery')">
                        <input type="hidden" name="current_gallery_json" value='<?= $p['gallery_images'] ?>'>
                        <input type="file" id="fileInput_gallery_<?= $p['id'] ?>" name="gallery_images[]" multiple style="display:none" onchange="handleFileSelect(this, <?= $p['id'] ?>, 'gallery')">
                        <div class="upload-placeholder" id="placeholder_gallery_<?= $p['id'] ?>">
                            <i class="fas fa-images"></i> Clicca per aggiungere foto (Multiplo)
                        </div>
                        <div id="previewContainer_gallery_<?= $p['id'] ?>" class="gallery-row" style="justify-content:center;"></div>
                    </div>

                    <button type="submit" class="save-btn">SALVA PRODOTTO</button>
                </form>

                <?php foreach($gal as $img): ?>
                    <form id="del_gal_prod_<?= $p['id'] ?>_<?= md5($img) ?>" method="POST" style="display:none;">
                        <input type="hidden" name="delete_product_gallery_image" value="1">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="image_path" value="<?= $img ?>">
                    </form>
                <?php endforeach; ?>

                <div style="text-align:right; margin-top:15px; border-top:1px solid #eee; padding-top:10px;">
                    <form method="POST" onsubmit="return confirm('Sei sicuro di voler eliminare tutto il prodotto?');">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="delete_product_completely" value="1">
                        <button style="color:red; background:none; border:none; cursor:pointer; font-size:12px;">Elimina intero prodotto</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <h2 class="section-title">Servizi</h2>
    
    <div class="create-box">
        <h3 style="margin:0; color:#0d47a1; margin-right:10px;">Nuovo:</h3>
        <form method="POST" style="flex:1; display:flex; gap:10px;">
            <input type="text" name="new_title" placeholder="Nome Servizio" required style="margin:0;">
            <input type="hidden" name="create_new_service" value="1">
            <button type="submit" style="background:#1a55e3; color:white; border:none; padding:10px 20px; border-radius:4px; cursor:pointer;">+ Aggiungi</button>
        </form>
    </div>

    <?php foreach ($services as $s): ?>
    <div id="service_<?= $s['id'] ?>" class="product-card"> <div class="card-header"><strong><?= htmlspecialchars($s['title']) ?></strong></div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                <input type="hidden" name="update_service" value="1">
                
                <label>Titolo:</label>
                <input type="text" name="title" value="<?= htmlspecialchars($s['title']) ?>">
                <label>Descrizione:</label>
                <textarea name="description"><?= htmlspecialchars($s['description']) ?></textarea>

                <label>Immagine Servizio:</label>
                <div class="gallery-row" style="margin-bottom: 5px;">
                    <?php if(!empty($s['image_path'])): ?>
                        <div style="text-align:center;">
                            <img src="../<?= $s['image_path'] ?>" class="thumb-mini">
                            <div style="font-size:10px; color:#666;">Attuale</div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="upload-box" onclick="triggerUpload(<?= $s['id'] ?>, 'service')">
                    <input type="hidden" name="current_image" value="<?= $s['image_path'] ?>">
                    <input type="file" id="fileInput_service_<?= $s['id'] ?>" name="service_image" style="display:none" onchange="handleFileSelect(this, <?= $s['id'] ?>, 'service')">
                    <div class="upload-placeholder" id="placeholder_service_<?= $s['id'] ?>">
                        <i class="fas fa-tools"></i> Clicca per cambiare
                    </div>
                    <div id="previewContainer_service_<?= $s['id'] ?>" class="gallery-row" style="justify-content:center;"></div>
                </div>

                <button type="submit" class="save-btn" style="background:#007bff;">SALVA SERVIZIO</button>
            </form>

            <div style="text-align:right; margin-top:15px; border-top:1px solid #eee; padding-top:10px;">
                <form method="POST" onsubmit="return confirm('Sei sicuro di voler eliminare tutto il servizio?');">
                    <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                    <input type="hidden" name="delete_service_completely" value="1">
                    <button style="color:red; background:none; border:none; cursor:pointer; font-size:12px;">Elimina intero servizio</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <h2 class="section-title">Gallery Generale</h2>
    
    <div id="general_gallery" class="product-card"> <div class="card-header"><strong>Gestione Foto Gallery</strong></div>
        <div class="card-body">
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="upload_general_gallery" value="1">
                <label>Carica Nuove Foto (Multiplo):</label>
                
                <div class="upload-box" onclick="triggerUpload('new', 'general')">
                    <input type="file" id="fileInput_general_new" name="general_images[]" multiple style="display:none" onchange="handleFileSelect(this, 'new', 'general')">
                    <div class="upload-placeholder" id="placeholder_general_new">
                        <i class="fas fa-images"></i> Clicca per caricare foto
                    </div>
                    <div id="previewContainer_general_new" class="gallery-row" style="justify-content:center;"></div>
                </div>
                <button type="submit" class="save-btn" style="background:#6f42c1;">CARICA IN GALLERIA</button>
            </form>

            <hr style="margin:30px 0; border:0; border-top:1px solid #eee;">

            <label>Foto attualmente nella galleria:</label>
            <div class="gallery-row">
                <?php foreach($gallery_photos as $photo): ?>
                    <div style="position: relative;">
                        <img src="../<?= $photo['image_path'] ?>" class="thumb-mini">
                        <form method="POST" onsubmit="return confirm('Cancellare questa foto?');" style="display:inline;">
                            <input type="hidden" name="delete_gallery_id" value="<?= $photo['id'] ?>">
                            <input type="hidden" name="delete_gallery_path" value="<?= $photo['image_path'] ?>">
                            <button type="submit" class="btn-x" style="top:-5px; right:-5px;">X</button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($gallery_photos)): ?> <p style="color:#999; font-style:italic;">Nessuna foto presente.</p> <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script>
function triggerUpload(id, type) {
    document.getElementById('fileInput_' + type + '_' + id).click();
}

function handleFileSelect(input, id, type) {
    const placeholder = document.getElementById('placeholder_' + type + '_' + id);
    const container = document.getElementById('previewContainer_' + type + '_' + id);
    container.innerHTML = ''; 

    if (input.files && input.files.length > 0) {
        placeholder.style.display = 'none'; 
        Array.from(input.files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'thumb-mini';
                    container.appendChild(img);
                }
                reader.readAsDataURL(file);
            }
        });
    } else {
        placeholder.style.display = 'block';
    }
}
</script>

</body>
</html>