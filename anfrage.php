<?php
// sideTwo-Kontaktformular – Standard: Brain/04 Standards/Kontaktformular-Standard.md
// Ademi Detailing: Anfrage aus Festpreis-Rechner und Kontaktformular. Kopie aus _vorlagen/statische-webseite/kontakt.php.
// Hosting bleibt bei Strato: $ABSENDER als Postfach bei Strato anlegen (nicht KAS). Vor Livegang testen.
declare(strict_types=1);

$EMPFAENGER = 'info@ademidetailing.de';
$ABSENDER   = 'website@ademidetailing.de';   // Postfach der EIGENEN Domain – sonst SPF-Fehler/Spam
$BETREFF    = 'Neue Anfrage über die Website';
$DANKE_SEITE = './#anfrage';
$MAX_PRO_STUNDE = 5;
$SPEICHER   = __DIR__ . '/.formular-tmp';
$TOKEN_PFLICHT = false;

$FELDER = [
    'name'        => 'name',
    'email'       => 'email',
    'telefon'     => 'telefon',
    'betreff'     => 'betreff',
    'nachricht'   => 'nachricht',
    'datenschutz' => '',
    'honigtopf'   => 'webseite',
];
$PFLICHT_NACHRICHT = false;            // Beleg reicht als Inhalt
$PFLICHT_TELEFON   = true;
$IGNORIEREN = [];

session_start();
$istAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']);

function antwort(bool $ok, string $text, int $code = 200): void {
    global $istAjax, $DANKE_SEITE;
    if ($istAjax) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'text' => $text], JSON_UNESCAPED_UNICODE);
    } elseif ($ok) { header('Location: ' . $DANKE_SEITE, true, 303); }
    else { http_response_code($code); echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
    exit;
}
function stillSchlucken(): void { antwort(true, 'Danke, Ihre Anfrage ist eingegangen.'); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { antwort(false, 'Methode nicht erlaubt.', 405); }

function feld(string $n, int $max = 200): string {
    $w = trim((string)($_POST[$n] ?? ''));
    return mb_substr(str_replace(["\r", "\n", "\t"], ' ', $w), 0, $max);
}
function schluessel(string $n): string { return str_replace([' ', '.'], '_', $n); }
$F = array_map('schluessel', $FELDER);

$name = feld($F['name']); $email = feld($F['email']); $telefon = feld($F['telefon'], 50);
$betreff = feld($F['betreff'], 120);
$nachricht = mb_substr(trim((string)($_POST[$F['nachricht']] ?? '')), 0, 5000);

$bekannt = array_merge(array_values($F), ['ts', 'token'], array_map('schluessel', $IGNORIEREN));
$extra = [];
foreach ($_POST as $k => $v) {
    if (in_array($k, $bekannt, true) || !is_string($v) || str_starts_with((string)$k, '_')) { continue; }
    $w = feld((string)$k, 600);
    if ($w !== '' && count($extra) < 15) { $extra[ucfirst(str_replace(['_', '-'], ' ', (string)$k))] = $w; }
}

if ($F['honigtopf'] !== '' && feld($F['honigtopf']) !== '') { stillSchlucken(); }
$ts = (int)($_POST['ts'] ?? 0);
if ($ts > 0 && (time() - $ts) < 3) { stillSchlucken(); }

function rateLimit(string $ordner, int $max): bool {
    if (!is_dir($ordner)) {
        @mkdir($ordner, 0700, true);
        @file_put_contents($ordner . '/.htaccess', "Require all denied\n");
    }
    $datei = $ordner . '/' . hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '0') . date('YmdH')) . '.cnt';
    foreach (glob($ordner . '/*.cnt') ?: [] as $alt) {
        if (filemtime($alt) < time() - 3600) { @unlink($alt); }
    }
    $n = (int)@file_get_contents($datei);
    if ($n >= $max) { return false; }
    @file_put_contents($datei, (string)($n + 1), LOCK_EX);
    return true;
}
if (!rateLimit($SPEICHER, $MAX_PRO_STUNDE)) {
    antwort(false, 'Es wurden bereits mehrere Anfragen gesendet. Bitte rufen Sie uns kurz an.', 429);
}

$alles = mb_strtolower($name . ' ' . $betreff . ' ' . $nachricht . ' ' . implode(' ', $extra));
if (preg_match_all('~https?://|www\.~i', $alles) > 1) { stillSchlucken(); }
if (preg_match('~\[url|<a\s+href~i', $alles)) { stillSchlucken(); }
foreach (['seo service','guest post','backlink','crypto','casino','viagra','binary option','loan offer','bitcoin'] as $w) {
    if (str_contains($alles, $w)) { stillSchlucken(); }
}
if ($nachricht !== '' && !str_contains(trim($nachricht), ' ')) { stillSchlucken(); }

$fehler = [];
if ($name === '')                     $fehler[] = 'Bitte geben Sie Ihren Namen an.';
if ($PFLICHT_NACHRICHT && $nachricht === '') $fehler[] = 'Bitte schreiben Sie uns kurz Ihr Anliegen.';
if ($PFLICHT_TELEFON && $telefon === '')     $fehler[] = 'Bitte geben Sie Ihre Telefonnummer an.';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $fehler[] = 'Die E-Mail-Adresse sieht nicht richtig aus.';
if ($fehler) { antwort(false, implode(' ', $fehler), 422); }

$text = "Neue Anfrage über die Website\n" . str_repeat('-', 40) . "\n\n"
      . "Name:     $name\nE-Mail:   " . ($email ?: '—') . "\nTelefon:  " . ($telefon ?: '—')
      . "\nBetreff:  " . ($betreff ?: '—') . "\n"
      . implode('', array_map(fn($k, $v) => str_pad($k . ':', 10) . "$v\n", array_keys($extra), $extra))
      . "\nNachricht:\n" . ($nachricht ?: '—') . "\n\n"
      . str_repeat('-', 40) . "\nGesendet am " . date('d.m.Y \u\m H:i') . " Uhr\n";

$header = [
    'From: ' . mb_encode_mimeheader('Website Ademi Detailing') . ' <' . $ABSENDER . '>',
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
];
if ($email !== '') { $header[] = 'Reply-To: ' . mb_encode_mimeheader($name) . ' <' . $email . '>'; }

$ok = @mail($EMPFAENGER, mb_encode_mimeheader($BETREFF . ($betreff ? ': ' . $betreff : '')),
            $text, implode("\r\n", $header), '-f' . $ABSENDER);

unset($_SESSION['form_token']);

if (!$ok) { antwort(false, 'Das Formular konnte gerade nicht gesendet werden. Bitte rufen Sie uns kurz an.', 500); }
antwort(true, 'Danke, Ihre Anfrage ist eingegangen. Wir melden uns so schnell wie möglich.');
