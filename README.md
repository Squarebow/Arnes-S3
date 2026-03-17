=== Arnes S3 ===
Contributors: SquareBow
Tags: s3, media, arnes, cloud, offload
Requires at least: 6.5
Tested up to: 6.9
Stable tag: 1.1
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Samodejno nalaganje medijskih datotek v Arnes Shrambo (S3) s CDN podporo, WebP/AVIF, varnostnimi kopijami in orodji za obnovitev.

---

# Arnes S3 - Media Offloading and CDN

**Vtičnik za samodejno ali ročno nalaganje medijskih datotek iz WordPressa v Arnes Web Storage (generični AWS S3 bucket). Podpira CDN, sodobne oblike slik (WebP in AVIF), varnostne kopije in obnovitev iz arhivov.**

[![Različica](https://img.shields.io/badge/version-1.0.9-blue.svg)](https://github.com/Squarebow/Arnes-S3)
[![PHP](https://img.shields.io/badge/php-8.0%2B-purple.svg)](https://php.net/)
[![Licence](https://img.shields.io/badge/license-GPL--2.0-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

---

## 📖 Kazalo

- [O vtičniku](#about)
- [Zakaj ta vtičnik?](#why-this-plugin)
- [Ključne funkcije](#key-features)
- [Sistemske zahteve](#system-requirements)
- [Namestitev](#installation)
- [Konfiguracija](#configuration)
- [Navodila za uporabo](#usage-guide)
- [Integracija CDN](#cdn-integration)
- [Pogosta vprašanja](#faq)
- [Podpora](#support)
- [Licenca](#license)

---

## O vtičniku

**Arnes S3** je vtičnik za WordPress, ki samodejno naloži vaše medijske datoteke (slike, PDF-je, videoposnetke, pisave) v **Arnes Shramba**, slovensko nacionalno storitev za shranjevanje objektov, združljivo s S3, ki jo zagotavlja Arnes (Akademsko-raziskovalna mreža Slovenije).

Vtičnik se brezhibno integrira v medijsko knjižnico WordPress, kar vam omogoča:
- ✅ Prenos medijskih datotek v oblak za shranjevanje, da prihranite prostor na disku.
- ✅ Prenos datotek neposredno iz S3 ali prek CDN za hitrejšo dostavo.
- ✅ Množični prenos obstoječe medijske knjižnice v S3.
- ✅ Spremljanje porabe prostora za shranjevanje z uporabno statistiko.
- ✅ Varnostno kopiranje in obnovitev celotne medijske knjižnice.

---

## Zakaj ta vtičnik?

Obstoječi vtičniki za odlaganje medijev WordPress (npr. WP Offload Media) podpirajo le večje ponudnike storitev v oblaku:
- ❌ AWS S3
- ❌ Google Cloud Storage
- ❌ DigitalOcean Spaces ipd.

**NE podpirajo prilagojenih končnih točk S3**, ki zahtevajo:
- Prilagojeno končno točko (`shramba.arnes.si`)
- ID organizacije v strukturi URL-ja
- Organizacija na podlagi map (ne več košaric)

**Ta vtičnik je bil razvit za podporo edinstvene infrastrukture Arnes Shramba.**

---

## Ključne značilnosti

### 🚀 Samodejno nalaganje
- Samodejno nalaganje novih medijskih datotek v Arnes S3, ko so naložene prek WordPressa
- Podpira vse vrste medijev: slike, PDF-je, videoposnetke, avdio datoteke, pisave
- Naloži vse velikosti, ki jih ustvari WordPress (miniature, WebP, AVIF)

### 📁 Množično nalaganje
- Naloži VSE obstoječe datoteke iz medijske knjižnice v S3 z enim klikom
- Sledenje napredku v realnem času z vizualnim kazalnikom napredka
- Filtriraj po datumu, vrsti datoteke ali velikosti
- Prenesite samo manjkajoče datoteke ali prepišite vse
- Funkcija zaustavitve/nadaljevanja

### 🌐 Prepisovanje URL-jev in podpora CDN
- Datoteke se prenašajo neposredno iz Arnes S3
- Izbirna integracija CDN (Cloudflare itd.)
- Samodejno prepisovanje URL-jev za `<img>`, `<picture>` in srcset

### 💾 Upravljanje shranjevanja
- Izberite, ali želite lokalne datoteke po nalaganju obdržati ali izbrisati.
- Prihranite do 90 % prostora na disku z odstranitvijo lokalnih kopij.

### 🛠️ Varnostno kopiranje in obnova
- Ustvarite ZIP varnostne kopije celotne medijske knjižnice.
- Prenesite in obnovite datoteke iz S3 na lokalni strežnik.

### 🔄 Orodja za sinhronizacijo in vzdrževanje
- Ponovno sinhronizirajte metapodatke za datoteke v S3
- Preverite celovitost datotek med lokalno in S3 hrambo
- Množično izbrišite lokalne kopije datotek, ki so že v S3

### 📊 Izčrpne statistike
- Skupno število datotek v WordPressu v primerjavi s S3
- Razčlenitev po vrstah datotek (slike, dokumenti, videi itd.)
- Odstotek pokritosti z vizualnimi kazalniki napredka

### 🎨 Nadzor kakovosti slik
- Prilagajanje stopenj stiskanja JPEG, WebP in AVIF
- Izbiranje prednosti formata (najprej WebP ali najprej AVIF)

### 🌍 Združljivost z WP multisite omrežji
- Deluje z omrežji WordPress Multisite (blog_id)
- Vsaka spletna stran dobi svojo strukturo map

### 🇸🇮 Slovenski vmesnik
- Popolnoma preveden slovenski uporabniški vmesnik
- WordPress privzeto oblikovanje z Dashicons

---

## Sistemske zahteve

### Minimalne zahteve
- **WordPress:** 6.5 ali višja različica
- **PHP:** 8.0 ali višja različica
- **Arnes Shramba:** Aktivni račun s S3 poverilnicami
- **PHP razširitve:** `curl`, `json`, `mbstring`, `xml`, `imagick`

### Priporočeno
- **WordPress:** 6.9+
- **PHP:** 8.1–8.3
- **Knjižnica slik:** Imagick (za podporo WebP/AVIF)
- **Pomnilnik:** 256 MB+ omejitev pomnilnika PHP

### Zahteve strežnika
- Dovoljenja za pisanje v `/wp-content/uploads/`
- Možnost vzpostavitve zunanjih HTTPS povezav
- Omogočen WP-Cron (za množične operacije)

---

## Namestitev

### Metoda 1: WordPress Admin (priporočeno)

1. Prenesite najnovejšo različico vtičnika - Code > Download ZIP ali [kliknite tukaj](https://github.com/Squarebow/Arnes-S3/releases/latest)
2. Pojdite na **WordPress Admin → Plugins → Add New**
3. Kliknite **Upload Plugin**
4. Izberite ZIP datoteko in kliknite **Install Now**
5. Kliknite **Activate Plugin**
6. Nastavite ga v WP Admin pod Media (Predstavnost v slovenščini) > **Arnes S3**

> **Opomba:** ZIP, ki ga prenesete z GitHuba, ima mapo `Arnes-S3-main`. Pred nalaganjem jo preimenujte v `arnes-s3` ali uporabite uradno objavo na strani Releases.

### Metoda 2: Ročna namestitev

1. Prenesite in razširite vtičnik.
2. Naložite mapo `arnes-s3` v `/wp-content/plugins/`.
3. V WordPress adminu odprite **Plugins**.
4. Poiščite »Arnes S3« in kliknite **Activate**.
5. Nastavite ga v WP Admin pod Media > **Arnes S3**.

### Metoda 3: WP-CLI

```bash
wp plugin install arnes-s3.zip --activate
```

---

## Konfiguracija

### Korak 1: Pridobite poverilnice Arnes Shramba

1. Prijavite se v [Arnes Portal članic](https://portal.arnes.si/portal/login)
2. Poiščite nastavitve svoje organizacije pod Arnes Shramba
3. Zapišite si **ID organizacije**, ki je navedeno kot uporabniško ime organizacije (številka)
4. Kopirajte **ključ za dostop** in **skrivni ključ**

### Korak 2: Mapa oziroma pot

**Pomembno:** Vtičnik NE ustvari mape samodejno.

1. Ustvarite novo mapo v spletnem vmesniku [Arnes Spletna shramba](https://spletna.shramba.arnes.si/).
2. Kliknite "Nova mapa" in jo poimenujte (priporočamo ime vaše domene, npr. `moja-domena`).
3. Pomaknite se v novo mapo in ustvarite podmape (npr. `slike` ali `spletna-stran`).
4. Pot vpišete v vtičnik v polje **Mapa/pot** (npr. `moja-domena/slike`).

### Korak 3: Konfigurirajte vtičnik

1. Pojdite na **WordPress Admin → Predstavnost → Arnes S3**
2. Pojdite na zavihek **Povezava**
3. Izpolnite polja:
   - **S3 Endpoint:** `https://shramba.arnes.si` (predizpolnjen, NE SPREMINJAJTE)
   - **Bucket:** `arnes-shramba` (privzeto)
   - **Mapa/pot:** vaša mapa (npr. `moja-domena/slike`)
   - **ID organizacije:** Uporabniško ime organizacije (številka) v Arnes portalu
   - **Ključ za dostop:** Vaš access key
   - **Skrivni ključ:** Vaš secret key
4. Kliknite **Preveri povezavo**
5. Če je uspešna, kliknite **Shrani spremembe**

---

## Navodila za uporabo

### Zavihek 1: Povezava
Nastavitve za vzpostavitev povezave z Arnes Shramba. Vedno preverite povezavo pred shranjevanjem.

### Zavihek 2: Nastavitve
- **Samodejno nalaganje:** Vsaka nova datoteka se takoj naloži v S3. Priporočeno.
- **Ohrani lokalne datoteke:** Datoteke ostanejo na strežniku (varnost, a večja poraba prostora).
- **Dostava datotek:** Direktno iz Arnes S3 ali prek CDN (Cloudflare).
- **Kakovost slik:** JPEG/WebP/AVIF kompresija (1–100%, privzeto 82%).
- **Prioriteta formatov:** WebP → AVIF (priporočeno) ali AVIF → WebP.

### Zavihek 3: Nalaganje
Množično nalaganje obstoječe medijske knjižnice v S3:
1. Preglej knjižnico (z opcijskimi filtri)
2. Preglej rezultate
3. Začni nalaganje (podpira premor, nadaljevanje, predogled)

### Zavihek 4: Orodja
- Arhiviranje medijske knjižnice v ZIP
- Obnova datotek iz Arnes oblaka
- Sinhronizacija metapodatkov
- Brisanje lokalnih kopij
- Preverjanje integritete

### Zavihek 5: Statistika
Pregled pokritosti medijev z S3, razčlenitev po tipih datotek, poraba prostora.

---

## Integracija CDN

### Nastavitev Cloudflare (brezplačni račun)

1. V Cloudflare DNS dodajte CNAME: `cdn.vasa-domena.si` → `shramba.arnes.si` (Proxy: ✅)
2. (Opcijsko) Ustvarite Cache Rule: `cdn.vasa-domena.si/*` → Eligible for Cache
3. V vtičniku (Zavihek 2) izberite **Uporabi CDN** in vnesite `https://cdn.vasa-domena.si`
4. Shranite in preizkusite z nalaganjem testne slike

---

## Pogosta vprašanja

**Ali to deluje z drugimi ponudniki S3?**  
Ne. Vtičnik je posebej zasnovan za Arnes Shramba URL strukturo.

**Kaj se zgodi, če Arnes Shramba ni dosegljiva?**  
Če je "Ohrani lokalne datoteke" vklopljeno, WordPress streže lokalne kopije.

**Ali deluje z WooCommerce, Elementor, Divi?**  
Da. Vtičnik se poveže z WordPress jedrom, ne s posameznimi vtičniki.

**Ali bo naložil slike teme ali vtičnikov?**  
Ne. Samo datoteke, naložene prek medijske knjižnice WordPress.

**Kako se shranjujejo metapodatki?**  
Vsaka datoteka dobi post meta `_arnes_s3_object` s ključem objekta S3.

**Kateri hook se uporablja za nalaganje?**  
`wp_generate_attachment_metadata` (prioriteta 999) – po generiranju vseh velikosti.

---

## Odpravljanje težav

**Test povezave ni uspel:** Preverite endpoint (`https://` je obvezen), access/secret key, ime bucket-a in ID organizacije.

**Datoteke se ne nalagajo:** Preverite ali je samodejno nalaganje vklopljeno, preverite PHP error log, spomin PHP (256 MB+).

**Slike se ne prikažejo:** Preverite S3 pokritost v Statistiki, URL prepisovanje v Nastavitvah, CDN domeno.

**Debug način:**
```php
// wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
// Preverite /wp-content/debug.log
```

---

## Podpora

- **GitHub Issues:** [Prijavite napake](https://github.com/Squarebow/Arnes-S3/issues)
- **E-pošta:** info@squarebow.com
- **Arnes podpora:** Za težave s poverilnicami se obrnite na Arnes.

---

## Licenca

Ta vtičnik je licenciran pod licenco **GPL-2.0-ali-novejšo**.

```
Arnes S3 - Media Offloading za Arnes Shramba
Copyright (C) 2026 SquareBow

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

---

## Izjava o odgovornosti

Ta vtičnik ni na noben način povezan z Arnesom niti ga Arnes ne podpira. Gre za orodje tretje osebe, zasnovano za delovanje s storitvami Arnes Shramba. Pred uporabo vtičnikov za shranjevanje v oblaku vedno naredite varnostno kopijo podatkov.

---

**Narejeno z ❤️ za slovensko WordPress skupnost**

**Različica:** 1.1 | **Zadnja posodobitev:** 17. marec 2026
