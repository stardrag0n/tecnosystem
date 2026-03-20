<?php
// Connessione al database
// Assicurati che il percorso sia corretto. Se index.php è nella root, admin/db.php è giusto.
if (file_exists('admin/db.php')) {
    require 'admin/db.php';
} elseif (file_exists('db.php')) {
    require 'db.php';
} else {
    die("Errore critico: Non trovo il file di connessione al database.");
}

try {
    // 1. CARICAMENTO PRODOTTI
    $stmt = $pdo->query("SELECT * FROM products WHERE is_visible = 1 ORDER BY sort_order ASC");
    $all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Preparazione dati JS per i prodotti (Pop-up dettagli)
    $js_data = [];
    foreach ($all_products as $p) {
        $cat_id = $p['category_id']; // Assicurati che nel DB la colonna sia 'category_id' o usa 'id'
        $main_img = !empty($p['image_path']) ? $p['image_path'] : 'photos/placeholder.jpg';
        $gallery = !empty($p['gallery_images']) ? json_decode($p['gallery_images'], true) : [];
        
        // Uniamo copertina + gallery
        $carousel_images = array_merge([$main_img], $gallery);
        // Puliamo array da valori vuoti o duplicati
        $carousel_images = array_values(array_filter(array_unique($carousel_images)));

        // Usiamo l'ID numerico come chiave per sicurezza
        $js_data[$p['id']] = [
            'title' => $p['title'],
            'text' => $p['description'],
            'images' => $carousel_images
        ];
    }

    // 2. CARICAMENTO SERVIZI
    // Controllo se la colonna is_visible esiste, altrimenti prendiamo tutto
    try {
        $stmt_serv = $pdo->query("SELECT * FROM services ORDER BY id ASC");
    } catch (Exception $e) {
        // Fallback se la tabella services è diversa
        $stmt_serv = $pdo->query("SELECT * FROM services");
    }
    $all_services = $stmt_serv->fetchAll(PDO::FETCH_ASSOC);

    // 3. CARICAMENTO GALLERY (SPOSTATO QUI IN ALTO PER EVITARE ERRORI)
    $stmt_gal = $pdo->query("SELECT * FROM gallery ORDER BY id DESC");
    $general_gallery = $stmt_gal->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // In caso di errore DB, inizializziamo array vuoti per non rompere il sito
    $all_products = [];
    $all_services = [];
    $general_gallery = [];
    // Decommenta sotto per vedere l'errore a video se serve
    // die("Errore database: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tecno System Snc | Infissi e Serramenti Pesaro Fano</title>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="photos/logo.png">
</head>
<body>
    
    <div id="hero-fixed-background">
        <img src="photos/sfondo.png" alt="Sfondo">
        <div class="overlay-scura"></div>
    </div>
    
    <header>
        <div class="container">
            <nav>
                <a href="index.php" class="logo">
                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <style>
                            .cube-line { fill: none; stroke: black; stroke-width: 5; stroke-linejoin: round; stroke-linecap: round; }
                            .cube-fill { fill: #1a55e3; stroke: none; }
                        </style>
                        <polygon class="cube-fill" points="20,35 70,35 70,85 20,85" /> 
                        <polygon class="cube-fill" points="20,35 45,15 95,15 70,35" /> 
                        <polygon class="cube-fill" points="70,35 95,15 95,65 70,85" /> 
                        <line x1="45" y1="15" x2="45" y2="65" class="cube-line" /> 
                        <line x1="45" y1="65" x2="95" y2="65" class="cube-line" /> 
                        <line x1="20" y1="85" x2="45" y2="65" class="cube-line" /> 
                        <rect x="20" y="35" width="50" height="50" class="cube-line" /> 
                        <polyline points="20,35 45,15 95,15 95,65 70,85" class="cube-line" /> 
                        <line x1="70" y1="35" x2="95" y2="15" class="cube-line" /> 
                    </svg>
                    Tecno System <span class="logo-suffix">snc</span>
                </a>
            </nav>
        </div>
    </header>

    <section class="hero" id="home">
        <div class="container hero-content">
            <h1>Infissi all'avanguardia<br>in tutta la provincia di Pesaro e Urbino</h1>
            <p>La qualità artigianale incontra la tecnologia.<br>Produzione propria dal 1989.</p>
            <a href="#contatti" class="btn">Richiedi Preventivo</a>
        </div>
    </section>

<div class="main-content-wrapper">

    <section class="solutions" id="prodotti">
        <div class="container" style="max-width: 98%;">
            <h2 class="section-title">Soluzioni su Misura</h2>
            <p class="intro-text" style="text-align: center; margin-bottom: 40px; color: #aaa;">
                Clicca su una categoria per scoprire i dettagli.
            </p>
            
            <div class="solutions-grid">
                <?php foreach ($all_products as $product): ?>
                    <?php 
                        // Recuperiamo ID e Immagine per la card
                        $p_id = $product['id']; // ID univoco
                        $img_path = !empty($product['image_path']) ? $product['image_path'] : 'photos/placeholder.jpg';
                        $title = htmlspecialchars($product['title']);
                    ?>
                    <div class="solution-card" data-id="<?= $p_id ?>">
                        <div class="solution-bg" style="background-image: url('<?= $img_path ?>');"></div>
                        <div class="solution-content">
                            <h3><?= $title ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
            
        <div id="solution-detail-panel" class="detail-panel">
            <button class="close-detail-btn">×</button>
            <div class="detail-content-wrapper">
                <div class="detail-gallery">
                    <div class="carousel-container">
                        <img id="detail-img" src="" alt="Dettaglio Prodotto">
                        <div class="carousel-nav">
                            <button id="prev-btn">❮</button>
                            <button id="next-btn">❯</button>
                        </div>
                    </div>
                </div>
                <div class="detail-info">
                    <div class="detail-header-area">
                        <h3 id="detail-title" class="section-title detail-title-style">Titolo Sezione</h3>
                    </div>
                    <div class="detail-body-area">
                        <p id="detail-desc">Descrizione...</p>
                        <a href="#contatti" class="btn">Richiedi Info</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="services" id="servizi">
        <div class="container">
            <h2 class="section-title">I Nostri Servizi</h2>
            <div class="services-grid">
                <?php foreach ($all_services as $s): ?>
                    <?php 
                        $bg_img = !empty($s['image_path']) ? $s['image_path'] : 'photos/default_service.jpg'; 
                    ?>
                    <div class="service-card">
                        <div class="service-bg" style="background-image: url('<?= $bg_img ?>');"></div>
                        <div class="service-content">
                            <h3><?= htmlspecialchars($s['title']) ?></h3>
                            <p><?= nl2br(htmlspecialchars($s['description'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="gallery-pinned-section" id="gallery">
        
        <div class="gallery-sticky-wrapper">
            
            <div class="gallery-center-title">
                <h2 id="open-full-gallery" style="cursor: pointer;">I Nostri Lavori</h2>
                <p style="color: #000000; font-size: 14px; margin-top: 10px;">Clicca per vedere l'archivio completo</p>
            </div>

            <div class="gallery-moving-images">
                <?php 
                if (!empty($general_gallery)) {
                    $i = 0;
                    foreach ($general_gallery as $row): 
                        $path = $row['image_path'];
                        
                        // Creiamo 3 livelli di profondità
                        // Front: passa SOPRA il titolo
                        // Back: passa SOTTO il titolo
                        $layers = ['layer-front', 'layer-mid', 'layer-back'];
                        $current_layer = $layers[$i % 3];
                        $i++;

                        // Velocità: Più bassa = più lenta ed elegante
                        $speed = 0.8 + (rand(1, 4) / 10); 
                    ?>
                        <div class="pinned-image <?= $current_layer ?>" data-speed="<?= $speed ?>">
                            <?php if(file_exists($path)): ?>
                                <img src="<?= htmlspecialchars($path) ?>" alt="Project">
                            <?php else: ?>
                                <img src="photos/placeholder.jpg" alt="Project">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; 
                }
                ?>
            </div>
        </div>
    </section>

    <section class="contact-section" id="contatti">
        <div class="container">
            <h2 class="section-title">Parliamo del tuo Progetto</h2>
            <div class="contact-grid">
                <div class="contact-info-col">
                    <div class="info-box">
                        <h3>Chiamaci Ora</h3>
                        <a href="tel:+390721855218" class="phone-number">0721.855218</a>
                        <p class="address">Via Avogadro, 2 - 61032 Fano (PU)</p>
                        <p class="email-text"><a href="mailto:1tecnosystem@libero.it" class="contact-link">1tecnosystem@libero.it</a></p>
                    </div>
                    <div class="map-container">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2892.657373576785!2d13.00762331549454!3d43.83489897911558!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x132d10590a3a002b%3A0x6a0a0a0a0a0a0a0a!2sVia%20Amedeo%20Avogadro%2C%202%2C%2061032%20Fano%20PU!5e0!3m2!1sit!2sit!4v1620000000000!5m2!1sit!2sit" 
                            width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy">
                        </iframe>
                    </div>
                </div>
                <div class="contact-form-col">
                    <form class="contact-form" id="contactForm">
                        <h3>Inviaci una richiesta</h3>
                        <p style="color: #888; margin-bottom: 20px;">Compila il modulo, ti risponderemo il prima possibile.</p>
                        <div class="form-row">
                            <div class="form-group"><label for="nome">Nome</label><input type="text" id="nome" name="nome" placeholder="Il tuo nome" required></div>
                            <div class="form-group"><label for="cognome">Cognome</label><input type="text" id="cognome" name="cognome" placeholder="Il tuo cognome" required></div>
                        </div>
                        <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" placeholder="latuamail@esempio.com" required></div>
                        <div class="form-group"><label for="messaggio">Messaggio</label><textarea id="messaggio" name="messaggio" rows="5" placeholder="Descrivi il tuo progetto..." required></textarea></div>
                        <button type="submit" class="btn btn-full">Invia Messaggio</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

</div> <footer class="reveal-footer">
    <div class="footer-content">
        <div class="footer-top">
            <div class="footer-col">
                <h4>Sede</h4>
                <p>Via Avogadro, 2<br>61032 Fano (PU)</p>
            </div>
            <div class="footer-col">
                <h4>Contatti</h4>
                <p>+39 0721.855218<br>
                <a href="mailto:1tecnosystem@libero.it" class="contact-link">1tecnosystem@libero.it</a>
            </p>
            </div>
            <div class="footer-col">
                <p style="opacity: 0.5;">© <?= date("Y") ?> Tecno System Snc <br>di Borgogelli Simone e Andrea<br>P.IVA 00000000<br>by Federico Borgogelli</p>
            </div>
        </div>
        
        <div class="footer-big-logo">
            <svg viewBox="0 0 1000 120" preserveAspectRatio="xMidYMid meet">
                <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" 
                      fill="white" font-family="'Helvetica Neue', sans-serif" font-weight="900" font-size="110">
                    TECNO SYSTEM
                </text>
            </svg>
        </div>
    </div>
</footer>
    
</div> <script>
    // Passiamo i dati PHP a JS in modo sicuro
    const productsData = <?= json_encode($js_data); ?>;
</script>
<script src="script.js"></script>

<div id="full-gallery-modal" class="full-modal">
    <div class="modal-nav">
        <button class="close-modal-btn">CHIUDI ×</button>
    </div>
    <div class="modal-scroll-area" data-lenis-prevent>
        <div class="container">
            <h2 class="section-title">Archivio Progetti</h2>
            <div class="full-grid">
                <?php foreach ($general_gallery as $row): ?>
                    <div class="grid-item">
                        <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="Lavoro Tecno System" loading="lazy">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div id="lightbox" class="lightbox">
    <span class="lightbox-close">&times;</span>
    <img class="lightbox-img" src="" alt="Ingrandimento">
</div>

</body>
</html>