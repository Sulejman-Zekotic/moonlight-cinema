# Moonlight Cinema

Standalone kino aplikacija napravljena bez WordPress zavisnosti.

## Struktura

- `index.php` front controller
- `app/` backend logika, rutiranje i prikazi
- `assets/` CSS, JavaScript i medijski fajlovi
- `storage/` lokalna baza, sesije, logovi i uploadi
- `vendor/tcpdf/` generisanje PDF karata
- `web.config` Azure / IIS pravila
- `.htaccess` Apache pravila

## Lokalno pokretanje

Ako koristiš XAMPP Apache, postavi document root na folder:

`C:\xampp\htdocs\moonlight\moonlight-cinema-app`

Za PHP built-in server:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8010 -t C:\xampp\htdocs\moonlight\moonlight-cinema-app
```

## Podrazumijevana lokalna baza

Aplikacija lokalno koristi SQLite fajl:

`storage/moonlight.sqlite`

Prvi start automatski pravi šemu i demo podatke.

## Test nalozi

- Admin: `admin@moonlightcinema.ba` / `Admin123!`
- Korisnik: `sulejman@moonlightcinema.ba` / `Moonlight123!`

## Azure postavke

Za Azure App Service možeš ostati na ovom front controller pristupu. `web.config` je već dodat za IIS rewrite.

Preporuka za produkciju je MySQL baza umjesto SQLite. Potrebni App Settings:

- `MC_DB_DRIVER=mysql`
- `MC_DB_HOST=...`
- `MC_DB_PORT=3306`
- `MC_DB_NAME=...`
- `MC_DB_USER=...`
- `MC_DB_PASS=...`
- `MC_MAIL_FROM=info@moonlightcinema.ba`

Ako ostavljaš SQLite i na serveru, `storage/` mora biti upisiv direktorij.

## Napomene

- Guest rezervacije upisuju link za otkazivanje u `storage/logs/mail.log`
- Plaćene karte se preuzimaju kao PDF
- Admin dio podržava statistiku, pregled projekcija, ručno dodavanje i auto-generisanje rasporeda
