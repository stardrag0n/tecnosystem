document.addEventListener('DOMContentLoaded', () => {

    /* =========================================
       1. SMOOTH SCROLL (LENIS) + HERO ANIMATION
       ========================================= */
    const lenis = new Lenis({
        duration: 1.2,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
        smooth: true
    });

    const heroImg = document.querySelector('#hero-fixed-background img');

    function raf(time) {
        lenis.raf(time);
        
        // Effetto Hero (Zoom e Fade out)
        const scrollY = window.scrollY;
        if (heroImg && scrollY < window.innerHeight) {
            const scale = 1 + (scrollY * 0.0005);
            const opacity = 1 - (scrollY / window.innerHeight);
            heroImg.style.transform = `scale(${scale})`;
            heroImg.style.opacity = opacity > 0 ? opacity : 0;
        }

        requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);

    // Effetto swoosh col bottone
    document.querySelectorAll('a[href="#contatti"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault(); // Blocca il salto standard brutto del browser

            // Usiamo Lenis per pilotare lo scroll
            lenis.scrollTo('#contatti', {
                offset: 350,      // Offset: 0 porta all'inizio esatto della sezione nera
                duration: 2.5,  // DURATA: 2.5 secondi (più alto = più lento/swoosh lungo)
                easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)), // Stessa fluidità del mouse
                immediate: false
            });
        });
    });


    /* =========================================
       2. PINNED GALLERY (LOGICA PARALLASSE)
       ========================================= */
    const pinnedSection = document.querySelector('.gallery-pinned-section');
    const pinnedImages = document.querySelectorAll('.pinned-image');

    if (pinnedSection && pinnedImages.length > 0) {
        
        // SETUP INIZIALE: Distribuzione sparsa
        pinnedImages.forEach((img, index) => {
            // X: Orizzontale casuale (10% - 80%)
            const randomX = Math.floor(Math.random() * 70) + 10;
            
            // Y: Verticale sparsa (da 50vh a 250vh virtuali)
            // Questo assicura che le foto siano distribuite lungo tutto il percorso
            const spreadFactor = 250; 
            const step = spreadFactor / pinnedImages.length;
            const randomStartY = 50 + (index * step) + (Math.random() * 50);

            img.style.left = randomX + '%';
            
            // Posizioniamo inizialmente l'immagine fuori schermo in basso (top: 100%)
            // Useremo randomStartY come offset per calcolare quando farla salire
            img.style.top = '100%'; 
            img.dataset.startOffset = randomStartY * 10; 
        });

        // SCROLL LOOP
        window.addEventListener('scroll', () => {
            const sectionTop = pinnedSection.offsetTop;
            const sectionHeight = pinnedSection.offsetHeight;
            const windowHeight = window.innerHeight;
            const scrollY = window.scrollY;

            // Se la sezione è visibile
            if (scrollY + windowHeight > sectionTop && scrollY < sectionTop + sectionHeight) {
                
                // Progresso scroll (0 a 1)
                let progress = (scrollY - sectionTop) / (sectionHeight - windowHeight);

                pinnedImages.forEach(img => {
                    const speed = parseFloat(img.getAttribute('data-speed'));
                    const startOffset = parseFloat(img.dataset.startOffset);
                    
                    // Calcolo risalita: Offset iniziale - (Progresso * Distanza * Velocità)
                    const moveUp = startOffset - (progress * (windowHeight * 3.5) * speed);
                    
                    img.style.transform = `translateY(${moveUp}px)`;
                });
            }
        });
    }


    /* =========================================
       3. DETTAGLI PRODOTTI (POP-UP & SLIDER)
       ========================================= */
    const detailPanel = document.getElementById('solution-detail-panel');
    const detailTitle = document.getElementById('detail-title');
    const detailText = document.getElementById('detail-desc');
    const detailImg = document.getElementById('detail-img');
    const closeBtn = document.querySelector('.close-detail-btn');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    
    let currentImages = [];
    let currentImageIndex = 0;
    let slideInterval = null;

    function showImage(index) {
        if (currentImages.length > 0) {
            if (index >= currentImages.length) index = 0;
            if (index < 0) index = currentImages.length - 1;
            currentImageIndex = index;
            detailImg.src = currentImages[currentImageIndex];
        }
    }

    function startSlideShow() {
        if (slideInterval) clearInterval(slideInterval);
        slideInterval = setInterval(() => showImage(currentImageIndex + 1), 2500);
    }

    function stopSlideShow() {
        if (slideInterval) clearInterval(slideInterval);
    }

    // Apertura Dettagli (Funzione globale per onclick HTML o listener)
    window.openDetails = function(id) {
        if (typeof productsData === 'undefined' || !productsData[id]) return;
        
        const data = productsData[id];
        currentImages = data.images;
        currentImageIndex = 0;

        detailTitle.textContent = data.title;
        detailText.innerHTML = data.text;
        if(currentImages.length > 0) detailImg.src = currentImages[0];

        // Mostra il pannello
        detailPanel.style.display = 'block';
        startSlideShow();

        // SCROLL: Portiamo l'utente esattamente all'inizio del pannello appena aperto
        setTimeout(() => {
            // Calcoliamo la posizione del pannello rispetto alla pagina
            const panelTop = detailPanel.getBoundingClientRect().top + window.scrollY;
            
            // Usiamo Lenis per uno scroll fluido (o lo scroll nativo se preferisci)
            if (typeof lenis !== 'undefined') {
                lenis.scrollTo(detailPanel, { offset: -100, duration: 1.5 });
            } else {
                window.scrollTo({ top: panelTop - 100, behavior: 'smooth' });
            }
        }, 100);
    };

    function closeDetails() {
        stopSlideShow();
        detailPanel.style.display = 'none';
    }

    // Event Listener per le Card
    document.querySelectorAll('.solution-card').forEach(card => {
        card.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            window.openDetails(id);
        });
    });

    if(closeBtn) closeBtn.addEventListener('click', closeDetails);
    
    if(prevBtn && nextBtn) {
        prevBtn.addEventListener('click', () => { stopSlideShow(); showImage(currentImageIndex - 1); });
        nextBtn.addEventListener('click', () => { stopSlideShow(); showImage(currentImageIndex + 1); });
    }


   /* =========================================
       4. FOOTER REVEAL (SOLO ALLA FINE)
       ========================================= */
    const contactSection = document.querySelector('.contact-section');
    const footer = document.querySelector('.reveal-footer');

    if (contactSection && footer) {
        window.addEventListener('scroll', () => {
            const scrollPosition = window.scrollY + window.innerHeight;
            const documentHeight = document.body.scrollHeight;
            const footerHeight = footer.offsetHeight;
            const distFromBottom = documentHeight - scrollPosition;

            // CONFIGURAZIONE:
            // L'animazione parte solo quando mancano 150px alla fine (molto tardi).
            // Questo garantisce che vedi tutto il form fermo prima che inizi a muoversi.
            const triggerDistance = 150; 

            if (distFromBottom <= triggerDistance) {
                
                let progress = 1 - (distFromBottom / triggerDistance);
                if (progress < 0) progress = 0;
                if (progress > 1) progress = 1;

                // Scala leggera (da 1.0 a 0.96) - stile elegante
                const scale = 1 - (progress * 0.04);
                
                // Arrotondamento deciso
                const radius = progress * 50;

                contactSection.style.transform = `scale(${scale})`;
                contactSection.style.borderRadius = `0 0 ${radius}px ${radius}px`;
            
            } else {
                // Reset completo se sei sopra la zona trigger
                contactSection.style.transform = 'scale(1)';
                contactSection.style.borderRadius = '0';
            }
        });
    }

    /* =========================================
       5. FORM CONTATTI
       ========================================= */
    /* =========================================
       5. FORM CONTATTI (VERSIONE FUNZIONANTE)
       ========================================= */
    const contactForm = document.getElementById('contactForm');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Impedisce il ricaricamento della pagina

            const submitBtn = contactForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerText;
            
            // Feedback visivo: disabilita il tasto durante l'invio
            submitBtn.innerText = "Invio in corso...";
            submitBtn.disabled = true;

            const formData = new FormData(this);

            // Invio dati al server tramite Fetch API
            fetch('contact_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Errore Server: ' + response.status);
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    alert("Richiesta inviata!");
                    contactForm.reset();
                } else {
                    alert("Errore: " + data.message);
                }
            })
            .catch(error => {
                // Questo log ti dirà esattamente cosa non va nella console (F12)
                console.error('Dettaglio Errore:', error);
                alert("Errore di connessione al server. Controlla la console.");
            })
            .finally(() => { submitBtn.disabled = false; });
        });
    }

    /* =========================================
       6. FOOTER HEIGHT FIX (EVITA TAGLI)
       ========================================= */
    function adjustFooterReveal() {
        const footer = document.querySelector('.reveal-footer');
        const wrapper = document.querySelector('.main-content-wrapper');
        const contactSection = document.querySelector('.contact-section');
        
        if (footer && wrapper) {
            // 1. Calcola l'altezza reale del footer (incluso logo e padding)
            const footerHeight = footer.offsetHeight;
            
            // 2. Applica quell'altezza come margine al wrapper
            // Così il sito si ferma esattamente dove inizia il footer
            wrapper.style.marginBottom = footerHeight + 'px';
            
            // 3. Aggiorna anche la logica dell'animazione contatti
            // per farla finire al punto giusto
            if(contactSection) {
               // Aggiorniamo una variabile CSS per usarla se serve, 
               // oppure lasciamo che la logica scroll esistente usi l'altezza ricalcolata
            }
        }
    }

    // Esegui al caricamento e quando ridimensioni la finestra
    window.addEventListener('load', adjustFooterReveal);
    window.addEventListener('resize', adjustFooterReveal);
    
    // Eseguiamo anche subito per sicurezza
    adjustFooterReveal();

    /* =========================================
       7. VISIBILITÀ FOOTER INTELLIGENTE
       ========================================= */
    const smartFooter = document.querySelector('.reveal-footer');

    function checkFooterVisibility() {
        if (!smartFooter) return;

        const scrollY = window.scrollY;
        const triggerPoint = document.body.scrollHeight / 2; // Appare dopo aver superato la prima schermata (Hero)
        
        // Se vuoi che appaia a metà pagina precisa, usa:
        // const triggerPoint = document.body.scrollHeight / 2;

        if (scrollY > triggerPoint) {
            smartFooter.classList.add('footer-visible');
        } else {
            smartFooter.classList.remove('footer-visible');
        }
    }

    // Aggiungiamo il controllo allo scroll
    window.addEventListener('scroll', checkFooterVisibility);
    
    // Controllo iniziale al caricamento
    checkFooterVisibility();

    /* =========================================
       8. FULL GALLERY MODAL & LIGHTBOX LOGIC
       ========================================= */
    const openGalleryBtn = document.getElementById('open-full-gallery');
    const fullModal = document.getElementById('full-gallery-modal');
    const closeModalBtn = document.querySelector('.close-modal-btn');
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.querySelector('.lightbox-img');

    // Apri Modal
    if(openGalleryBtn) {
        openGalleryBtn.addEventListener('click', () => {
            fullModal.classList.add('active');
            lenis.stop(); // Blocca lo scroll del sito principale
        });
    }

    // Chiudi Modal
    if(closeModalBtn) {
        closeModalBtn.addEventListener('click', () => {
            fullModal.classList.remove('active');
            lenis.start(); // Riattiva lo scroll
        });
    }

    // LIGHTBOX: Clicca su una foto della griglia per ingrandirla
    document.querySelectorAll('.grid-item img').forEach(img => {
        img.addEventListener('click', () => {
            lightboxImg.src = img.src;
            lightbox.style.display = 'flex';
        });
    });

    // Chiudi Lightbox
    if(lightbox) {
        lightbox.addEventListener('click', () => {
            lightbox.style.display = 'none';
        });
    }
});