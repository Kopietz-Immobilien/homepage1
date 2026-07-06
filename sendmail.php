<?php
/* ============================================================
   Kontaktformular-Versand für Kopietz Immobilien
   Läuft auf Ihrem IONOS-Webhosting (PHP). Kein Drittanbieter.

   >>> BITTE EINMALIG ANPASSEN: <<<
   $absender  – eine E-Mail-Adresse Ihrer EIGENEN Domain bei IONOS
                (z. B. kontakt@ihre-domain.de). WICHTIG: keine
                Fremdadresse wie @gmx.de als Absender verwenden,
                sonst lehnen Mailserver die Nachricht ab (SPF/DMARC).
   ============================================================ */

$empfaenger = 'kopietz-immobilien@gmx.de';          // Ihr Postfach (Ziel)
$absender   = 'kontakt@IHRE-DOMAIN.de';             // <-- HIER Ihre Domain-Adresse eintragen
$absendername = 'Website Kopietz Immobilien';

// ---- nur POST zulassen ----
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'method']);
    exit;
}

// ---- Spam-Schutz (Honeypot): wenn ausgefüllt -> stillschweigend "ok" ----
if (!empty($_POST['_honey'])) {
    echo json_encode(['success' => true]);
    exit;
}

// ---- Eingaben einlesen & säubern ----
function clean($v) { return trim(str_replace(["\r", "\n", "%0a", "%0d"], ' ', (string)$v)); }

$name     = clean($_POST['name']    ?? '');
$email    = clean($_POST['email']   ?? '');
$telefon  = clean($_POST['phone']   ?? '');
$anliegen = clean($_POST['topic']   ?? '');
$nachricht = trim($_POST['msg']     ?? '');

// ---- Pflichtfelder prüfen ----
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'validation']);
    exit;
}

// ---- E-Mail zusammenbauen ----
$betreff = 'Neue Anfrage über die Website – ' . ($anliegen !== '' ? $anliegen : 'Kontakt');

$body  = "Neue Nachricht über das Kontaktformular:\n\n";
$body .= "Name:      $name\n";
$body .= "E-Mail:    $email\n";
$body .= "Telefon:   " . ($telefon !== '' ? $telefon : '-') . "\n";
$body .= "Anliegen:  " . ($anliegen !== '' ? $anliegen : '-') . "\n";
$body .= "----------------------------------------\n";
$body .= ($nachricht !== '' ? $nachricht : '(keine Nachricht eingegeben)') . "\n";

$headers  = 'From: ' . mb_encode_mimeheader($absendername) . ' <' . $absender . ">\r\n";
$headers .= 'Reply-To: ' . $email . "\r\n";          // Antwort geht direkt an den Absender
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";

$betreff_enc = mb_encode_mimeheader($betreff, 'UTF-8');

// ---- Versand (Envelope-Sender -f für bessere Zustellung bei IONOS) ----
$ok = mail($empfaenger, $betreff_enc, $body, $headers, '-f' . $absender);

if ($ok) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'mail']);
}
