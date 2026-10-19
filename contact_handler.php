<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Configurazione (Cambia questa email con la tua)
    $to = "1tecnosystem@libero.it"; 
    $subject = "Nuovo messaggio dal sito Tecno System";

    // 2. Recupero e sanificazione dati
    $nome = strip_tags(trim($_POST["nome"]));
    $cognome = strip_tags(trim($_POST["cognome"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $messaggio = strip_tags(trim($_POST["messaggio"]));

    // 3. Validazione semplice
    if (empty($nome) || empty($messaggio) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Dati non validi. Controlla i campi."]);
        exit;
    }

    // 4. Costruzione del corpo email
    $email_content = "Hai ricevuto una nuova richiesta di preventivo:\n\n";
    $email_content .= "Nome: $nome $cognome\n";
    $email_content .= "Email: $email\n\n";
    $email_content .= "Messaggio:\n$messaggio\n";

    $server_host = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'tecnosystem.altervista.org';
    $headers = "From: Tecno System Web <noreply@{$server_host}>\r\n";
    $headers .= "Reply-To: $nome $cognome <$email>\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // 5. Invio
    if (mail($to, $subject, $email_content, $headers)) {
        echo json_encode(["status" => "success", "message" => "Messaggio inviato con successo!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Errore nell'invio. Riprova più tardi."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Accesso non consentito."]);
}
?>