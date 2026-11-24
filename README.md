# Ademi Detailing – Website-Entwurf

Entwurf der neuen Startseite für **Ademi Detailing**, Fahrzeugaufbereitung in Reutlingen (Olgastraße 49).
Gebaut von [sideTwo](https://sidetwo.de). Stand: Entwurf, nicht live, `noindex`.

## Inhalt

| Datei | Zweck |
|---|---|
| `index.html` | Startseite: Video-Hero, Preis-Rechner Schritt für Schritt, Leasing-Rückgabe, Social, Kontakt |
| `anfrage.php` | Versand der Rechner-Anfrage per PHP (Standard sideTwo, nie `mailto:`) |
| `bilder/hero-politur-quer.mp4` / `-hoch.mp4` | KI-Hintergrundvideo (Kling), 10 s Loop, auf der Seite als „KI-Video“ gekennzeichnet |
| `bilder/ademi-logo-hell.png` | Logo für dunklen Hintergrund (aus dem Original abgeleitet) |
| `schrift/mona-sans.woff2` | einzige Schrift, lokal (keine Google Fonts) |

Preise im Rechner stammen aus dem bisherigen Konfigurator des Kunden und sind **noch nicht bestätigt**.
Scheibenversiegelung fehlt bewusst (stand dort auf 0 €).

## Vorschau lokal

`preview_start ademi` (Port 4900, Wurzel = Kundenordner) → `http://localhost:4900/ademi-detailing/`

## Vor dem Livegang

- Preise und Wortlaut „Festpreis“ mit dem Kunden bestätigen
- Postfach `website@ademidetailing.de` **bei Strato** anlegen, Anfrage testen
- Impressum und Datenschutz als eigene Seiten (Hosting Strato)
- SSL-Zertifikat bei Strato der Domain zuweisen
- `noindex` entfernen
