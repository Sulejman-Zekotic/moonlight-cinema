# Moonlight Cinema - Azure deploy

Ovaj projekat nije odvojen React/Vue frontend + API backend. Frontend i backend su zajedno u jednom PHP MVC projektu:

- `index.php`, `app/` = backend/routing/API/server render
- `assets/` = frontend CSS/JS/slike
- `app/Installer.php` = automatski pravi tabele i seed podatke pri prvom startu

## Preporučeni Azure setup

1. Azure App Service za PHP aplikaciju
2. Azure Database for MySQL Flexible Server za bazu
3. GitHub Actions preko Azure Deployment Center-a za auto deploy na svaki push

## App Service postavke

U Azure App Service idi na **Settings > Environment variables** i dodaj:

```text
MC_DB_DRIVER=mysql
MC_DB_HOST=ime-servera.mysql.database.azure.com
MC_DB_PORT=3306
MC_DB_NAME=moonlight_cinema
MC_DB_USER=tvoj_mysql_user
MC_DB_PASS=tvoj_mysql_password
MC_MAIL_FROM=info@moonlightcinema.ba
```

Nemoj ove podatke stavljati u GitHub repo.

## Seed podaci

Seed je automatski. Kada se aplikacija prvi put otvori na serveru i baza je prazna, `Installer.php` kreira tabele i ubaci:

- filmove
- žanrove
- sale
- sjedišta
- projekcije
- test korisnike/admina
- test recenzije/rezervacije

Admin login:

```text
admin@moonlightcinema.ba
Admin123!
```

## GitHub auto deploy

U Azure App Service:

1. Deployment Center
2. Source: GitHub
3. Repository: tvoj repo
4. Branch: `main` ili `master`, zavisno koji koristiš
5. Save

Nakon toga svaki `git push` automatski updateuje hostovanu stranicu.

## Bitno

Za produkciju koristi MySQL. SQLite baza i session/log fajlovi nisu za produkcijski deploy i zato su ignorisani u `.gitignore`.
