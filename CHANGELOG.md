# Dnevnik sprememb (Changelog)

Vse pomembne spremembe vtičnika Arnes S3 so dokumentirane v tej datoteki.

Format temelji na [Keep a Changelog](https://keepachangelog.com/sl/1.0/),
različice sledijo [Semantic Versioning](https://semver.org/).

---

## [1.1] – 2026-03-17

### Popravki varnosti in kakovosti kode

#### 🔴 Kritični popravki (Errors)

- **Font Awesome CDN odstranjen** – Font Awesome CDN nadomeščen z WP Dashicons.

- **Dodani `sanitize_callback` parametri** – Dodane ustrezne sanitizacijske funkcije za vsak tip nastavitve.

- **SQL poizvedba varno prepisana** – Nova poizvedba nadomeščena z WP pristopom `get_posts()` z `WP_Query` argumenti – brez neposrednega SQL-a.

- **`unlink()` nadomeščen z `wp_delete_file()`** – `wp_delete_file()` namesto `unlink()` za brisanje datotek.

- **`date()` nadomeščena z `gmdate()`** – Sistemska časovna cona je neodvisna od časovnih pasov.

- **Vsi nezaščiteni izhodi (`echo`) zaščiteni** – Pregled vseh izpisov v admin vmesniku in wrapping z ustreznimi funkcijami: `esc_html()` za besedilo, `esc_attr()` za atribute, `esc_url()` za URL-je, `wp_kses()` za HTML z ikonami.

#### 🟡 Opozorila in priporočila (Warnings)

- **Dodani `wp_unslash()` klici** – Vsi `$_POST` in `$_GET` parametri so "unslashani".

- **Varno dekodiranje JSON POST parametrov** – Nadomeščeno z varnejšim `json_decode(wp_unslash(...))` in preverjanjem, ali je rezultat veljavno polje.

- **Dodani `wp_cache_get/set` ovijači za direktne DB poizvedbe** – Vse poizvedbe zdaj predpomnijo rezultate za 300 sekund z `wp_cache_get/set`.

- **`error_log()` pogojno izvajanje** – Odstranjeni klici `error_log()` v produkcijskem okolju.

- **`phpcs:ignore` komentarji za meta_query** – Kjer se `meta_query` ne da nadomestiti (backup, restore, sync skeniranje), so dodani komentarji z utemeljitvijo.

### Združljivost

- **PHP zahteva posodobljena** – `Requires PHP` v glavi vtičnika spremenjena iz `7.4` na `8.0`. Kompatibilnost zagotovljena za PHP različice 8.0 – 8.4.

### Vizualne spremembe

- **Dashicons namesto Font Awesome** – Vse ikone v admin vmesniku (zavihki, naslovi sekcij, statusni indikatorji) zamenjane z ustreznimi Dashicons ikonami.

---

## [1.0.8] – 2026-02-19

### Kritični popravki
- Popravljena privzeta S3 endpoint vrednost – dodano `https://` predpono
- Odstranjen zavajajoč nadomestni znak za ID organizacije
- Zavihek Statistika sedaj zahteva veljavno konfiguracijo pred prikazom podatkov
- Font Awesome premaknjen na CDN (v tej različici – v 1.0.9 nadomeščen z Dashicons)

### Novosti v različici 1.0
- Popoln admin vmesnik s 5 zavihki (Povezava, Nastavitve, Nalaganje, Orodja, Statistika)
- Množično nalaganje s sledenjem napredka v realnem času
- Funkcionalnost varnostnega kopiranja in obnovitve
- Celovit statistični nadzorni panel
- Nadzor kakovosti slik (JPEG, WebP, AVIF)
- Podpora CDN
- Slovenska lokalizacija vmesnika

---

## [0.3.0] – 2026-01-xx

### Popravki
- Odpravljena sesutje ob posodobitvi vtičnika (ZIP upload hook conflict)
- Dodan `arnes_s3_client()` ovijač z preverjanjem poverilnic

---

## [0.2.x] – 2026-01-xx

### Poskusi popravkov
- Večkratne iteracije odpravljanja sesutja ob posodobitvi
- Ugotovljen vzrok: `add_attachment` hook se sproži med nalaganjem plugin ZIP-a

---

## [0.1.0] – 2026-01-xx

### Začetna različica
- Osnovno nalaganje medijev v Arnes S3 ob `add_attachment` hook-u
- Admin stran pod Media → Arnes S3
- Nastavitve S3 (endpoint, bucket, prefix, access/secret key)
- Testiranje povezave prek AJAX
- Možnost ohranjanja lokalnih datotek
- Polje za CDN domeno
