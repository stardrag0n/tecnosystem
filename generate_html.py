import sqlite3
import json
import html
import os
import random

def generate_index_html():
    conn = sqlite3.connect('data/database.sqlite')
    conn.row_factory = sqlite3.Row

    products = [dict(r) for r in conn.execute('SELECT * FROM products WHERE is_visible = 1 ORDER BY sort_order ASC').fetchall()]
    services = [dict(r) for r in conn.execute('SELECT * FROM services ORDER BY id ASC').fetchall()]
    gallery_raw = [dict(r) for r in conn.execute('SELECT * FROM gallery ORDER BY id DESC').fetchall()]

    # Mapping for gallery items
    gallery = []
    photo_map = {
        'Finestra 1': 'photos/Finestra 1.png',
        'Gazebo 1': 'photos/Gazebo 1.png',
        'Pergolati 1': 'photos/Pergolati 1.png',
        'Porta 1': 'photos/Porta 1.png',
        'Portafinestra 1': 'photos/Portafinestra 1.png',
    }

    for item in gallery_raw:
        path = item.get('image_path', '')
        resolved = None
        if path and os.path.exists(path):
            resolved = path
        else:
            for key, target in photo_map.items():
                if key in path:
                    resolved = target
                    break
            if not resolved or not os.path.exists(resolved):
                resolved = 'photos/Finestra 1.png'
        gallery.append({'image_path': resolved})

    # Fallback product images
    product_img_map = {
        1: 'photos/Porta 1.png',
        2: 'photos/Finestra 1.png',
        3: 'photos/Gazebo 1.png',
        4: 'photos/Portafinestra 1.png',
        5: 'photos/Finestra 1.png',
        6: 'photos/Pergolati 1.png',
        7: 'photos/Porta 1.png'
    }

    service_img_map = {
        1: 'photos/Finestra 1.png',
        2: 'photos/Portafinestra 1.png',
        3: 'photos/Porta 1.png'
    }

    js_data = {}
    for p in products:
        p_id = p['id']
        img_db = p.get('image_path')
        if img_db and os.path.exists(img_db):
            main_img = img_db
        else:
            main_img = product_img_map.get(p_id, 'photos/Finestra 1.png')
        
        # Build gallery for modal
        all_imgs = [main_img]
        raw_gal = p.get('gallery_images')
        gallery_list = json.loads(raw_gal) if raw_gal else []
        for g_file in gallery_list:
            if g_file and os.path.exists(g_file) and g_file not in all_imgs:
                all_imgs.append(g_file)
        
        if len(all_imgs) == 1:
            for f in ['photos/Porta 1.png', 'photos/Finestra 1.png', 'photos/Portafinestra 1.png']:
                if f not in all_imgs and os.path.exists(f):
                    all_imgs.append(f)

        js_data[str(p_id)] = {
            'title': p['title'],
            'text': p['description'] or f"Soluzioni e finiture d'eccellenza per {p['title']}. Realizzazione artigianale su misura.",
            'images': all_imgs
        }

    # Products HTML
    products_cards_html = []
    for p in products:
        p_id = p['id']
        title = html.escape(p['title'])
        img_db = p.get('image_path')
        if img_db and os.path.exists(img_db):
            img = img_db
        else:
            img = product_img_map.get(p_id, 'photos/Finestra 1.png')
        products_cards_html.append(f'''                    <div class="solution-card" data-id="{p_id}">
                        <div class="solution-bg" style="background-image: url('{img}');"></div>
                        <div class="solution-content">
                            <h3>{title}</h3>
                        </div>
                    </div>''')

    # Services HTML
    services_cards_html = []
    for s in services:
        s_id = s['id']
        title = html.escape(s['title'])
        desc = html.escape(s['description']).replace('\n', '<br>')
        srv_db = s.get('image_path')
        if srv_db and os.path.exists(srv_db):
            bg = srv_db
        else:
            bg = service_img_map.get(s_id, 'photos/Finestra 1.png')
        services_cards_html.append(f'''                    <div class="service-card">
                        <div class="service-bg" style="background-image: url('{bg}');"></div>
                        <div class="service-content">
                            <h3>{title}</h3>
                            <p>{desc}</p>
                        </div>
                    </div>''')

    # Gallery HTML
    gallery_pinned_html = []
    layers = ['layer-front', 'layer-mid', 'layer-back']
    speeds = [0.9, 1.2, 0.8, 1.1, 1.0]
    for i, g in enumerate(gallery):
        layer = layers[i % len(layers)]
        speed = speeds[i % len(speeds)]
        img_src = html.escape(g['image_path'])
        gallery_pinned_html.append(f'''                        <div class="pinned-image {layer}" data-speed="{speed}">
                            <img src="{img_src}" alt="Progetto Tecno System">
                        </div>''')

    # Modal grid HTML
    gallery_modal_html = []
    for g in gallery:
        img_src = html.escape(g['image_path'])
        gallery_modal_html.append(f'''                    <div class="grid-item">
                        <img src="{img_src}" alt="Lavoro Tecno System" loading="lazy">
                    </div>''')

    full_html = f'''<!DOCTYPE html>
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
                <a href="index.html" class="logo">
                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <style>
                            .cube-line {{ fill: none; stroke: black; stroke-width: 5; stroke-linejoin: round; stroke-linecap: round; }}
                            .cube-fill {{ fill: #1a55e3; stroke: none; }}
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
{chr(10).join(products_cards_html)}
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
{chr(10).join(services_cards_html)}
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
{chr(10).join(gallery_pinned_html)}
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

</div>

<footer class="reveal-footer">
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
                <p style="opacity: 0.5;">© 2026 Tecno System Snc <br>di Borgogelli Simone e Andrea<br>P.IVA 00000000<br>by Federico Borgogelli</p>
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

<script>
    // Dati dinamici del catalogo
    const productsData = {json.dumps(js_data, ensure_ascii=False, indent=4)};
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
{chr(10).join(gallery_modal_html)}
            </div>
        </div>
    </div>
</div>

<div id="lightbox" class="lightbox">
    <span class="lightbox-close">&times;</span>
    <img class="lightbox-img" src="" alt="Ingrandimento">
</div>

</body>
</html>'''

    with open('index.html', 'w', encoding='utf-8') as f:
        f.write(full_html)
    print("Successfully generated index.html!")

if __name__ == '__main__':
    generate_index_html()
