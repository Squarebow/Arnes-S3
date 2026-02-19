# Arnes S3 - WordPress Media Offloading vtičnik

**Vtičnik za samodejno ali ročno nalaganje medijskih datotek iz WordPressa v Arnes Web Storage (generični AWS S3 bucket). Podpira CDN, sodobne oblike slik (WebP in AVIF), varnostne kopije in obnovitev iz arhivov.**

[![Različica](https://img.shields.io/badge/version-1.0.8-blue.svg)]()
[![WordPress](https://img.shields.io/badge/wordpress-6.5%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/php-7.4%2B-purple.svg)](https://php.net/)
[![Licence](https://img.shields.io/badge/license-GPL--2.0-green.svg)](LICENCE)

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
- ✅ Spremljanje porabe prostora za shranjevanje s celovitimi statistikami.
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
- Sledenje napredka v realnem času z vizualnim kazalnikom napredka
- Filtriraj po datumu, vrsti datoteke ali velikosti
- Prenesite samo manjkajoče datoteke ali prepišite vse
- Funkcija zaustavitve/nadaljevanja
- Podrobne statistike prenosa

### 🌐 Prepisovanje URL-jev in podpora CDN
- Datoteke se prenašajo neposredno iz Arnes S3
- Izbirna integracija CDN (Cloudflare itd.)
- Samodejno prepisovanje URL-jev za `<img>`, `<picture>` in srcset
- Deluje z orodji za ustvarjanje strani (page builders) in prilagojenimi temami

### 💾 Upravljanje shranjevanja
- Izberite, ali želite lokalne datoteke po nalaganju obdržati ali izbrisati.
- Prihranite do 90 % prostora na disku z odstranitvijo lokalnih kopij.
- Datoteke so varno shranjene v Arnes S3.

### 🛠️ Varnostno kopiranje in obnova
- Ustvarite ZIP varnostne kopije celotne medijske knjižnice.
- Varnostno kopirajte iz lokalnih datotek ali neposredno iz S3.
- Prenesite in obnovite datoteke iz S3 na lokalni strežnik
- Nujno za obnovo po nezgodi

### 🔄 Orodja za sinhronizacijo in vzdrževanje
- Ponovno sinhronizirajte metapodatke za datoteke v S3
- Preverite celovitost datotek med lokalno in S3 hrambo
- Množično izbrišite lokalne kopije datotek, ki so že v S3
- Samodejno popravite manjkajoče metapodatke

### 📊 Izčrpne statistike
- Skupno število datotek v WordPressu v primerjavi s S3
- Razčlenitev po vrstah datotek (slike, dokumenti, videi itd.)
- Izračuni velikosti shranjevanja
- Odstotek pokritosti z vizualnimi kazalniki napredka
- Statistični podatki o zadnjem množičnem nalaganju

### 🎨 Nadzor kakovosti slik
- Prilagajanje stopenj stiskanja JPEG, WebP in AVIF
- Izbiranje prednosti formata (najprej WebP ali najprej AVIF)
- Obdelava slik v WordPressu
- Uravnoteženje kakovosti in velikosti datotek

### 🌍 Združljivost z WP multisite omrežji
- Deluje z omrežji WordPress Multisite (blog_id)
- Vsaka spletna stran dobi svojo strukturo map
- Ločene statistike za vsako spletno stran

### 🇸🇮 Slovenski vmesnik
- Popolnoma preveden slovenski uporabniški vmesnik
- Profesionalni administrativni vmesnik z ikonami Font Awesome
- WordPress privzeto oblikovanje

---

## Sistemske zahteve

### Minimalne zahteve
- **WordPress:** 6.5 ali višja različica
- **PHP:** 7.4 ali višja različica
- **Arnes Shramba:** Aktivni račun s S3 poverilnicami
- **PHP razširitve:** `curl`, `json`, `mbstring`, `xml`, `imagick`

### Priporočeno
- **WordPress:** 6.9+
- **PHP:** 8.0+
- **Knjižnica slik:** Imagick (za podporo WebP/AVIF)
- **Pomnilnik:** 256 MB+ omejitev pomnilnika PHP

### Zahteve strežnika
- Dovoljenja za pisanje v `/wp-content/uploads/`
- Možnost vzpostavitve zunanjih HTTPS povezav
- Omogočen WP-Cron (za množične operacije)

---

## Namestitev

### Metoda 1: WordPress Admin (priporočeno)

1. Prenesite najnovejšo različico vtičnika - Public stable release oziroma pod gumbom Code > Download ZIP ali [kliknite tukaj](https://github.com/Squarebow/Arnes-S3/archive/refs/heads/main.zip)
2. Pojdite na **WordPress Admin → Plugins → Add New**
3. Kliknite **Upload Plugin**
4. Izberite ZIP datoteko in kliknite **Install Now**
5. Kliknite **Activate Plugin**
6. Nastavite ga v WP Admin pod Media (Predstavnost v slovenščini) > **Arnes S3**

### Metoda 2: Ročna namestitev

1. Prenesite in razširite vtičnik.
2. Naložite mapo `arnes-s3` v `/wp-content/plugins/`.
3. V WordPress adminu odprite **Plugins**.
4. Poiščite »Arnes S3« in kliknite **Activate**.
5. Nastavite ga v WP Admin pod Media > **Arnes S3**.

### Metoda 3: WP-CLI

```bash
wp plugin install arnes-s3-v108.zip --activate
```

---

## Konfiguracija

### Korak 1: Pridobite poverilnice Arnes Shramba

1. Prijavite se v [Arnes Portal članic](https://portal.arnes.si/portal/login)
2. Poiščite nastavitve svoje organizacije pod Arnes Shramba
3. Zapišite si **ID organizacije**, ki je navedeno kot uporabniško ime organizacije (številka)
4. Kopirajte **ključ za dostop** in **skrivni ključ**

### Korak 2: mapo oziroma pot

**Pomembno:** Vtičnik NE ustvari zbirke ali mape samodejno.

1. Ustvarite novo mapo v spletnem vmesniku [Arnes Spletna shramba](https://spletna.shramba.arnes.si/). Za vpis uporabite access in secret key.
2. Kliknite "Nova mapa" in jo poimenujte (priporočamo ime vaše domene ali organizacije, npr. `moja-domena`).
3. Pomaknite se v novo mapo in ustvarite novo mapo, kamor se bodo shranjevale medijske datoteke (npr. slike ali spletna-stran)
4. Pot, kamor se bodo nalagale datoteke, vpišete v vtičnik v polje mapa/pot (npr. moja-domena/slike)

### Korak 3: Konfigurirajte vtičnik

1. Pojdite na **WordPress Admin → Predstavnost → Arnes S3**
2. Pojdite na zavihek **Povezava (Connection)**
3. Izpolnite polja:
   - **S3 Endpoint:** `https://shramba.arnes.si` (predizpolnjen, NE SPREMINJAJTE)
   - **Bucket:** `arnes-shramba` (privzeto, pustite ime, če niste ustvarjali novega bucketa)
   - **Mapa/pot:** `moja-domena` (mapa, ki ste jo ustvarili)
   - **ID organizacije:** Uporabniško ime organizacije (številka) v Arnes portalu članic / Arnes Shramba
   - **Ključ za dostop:** Vaš ključ za dostop do S3
   - **Skrivni ključ:** Vaš skrivni ključ za S3
4. Kliknite **Preveri povezavo**
5. Če je povezava uspešna, kliknite **Shrani spremembe**

---

### Zavihek 2: Nastavitve (Settings)

**Namen:** Nadzor delovanja vtičnika

#### Samodejno nalaganje
- ✅ **Omogoči:** Nove datoteke se samodejno naložijo v S3.
- ⬜ **Onemogoči:** Datoteke ostanejo lokalno (ročno nalaganje prek množičnega nalaganja).

#### Ohrani lokalne datoteke
- ✅ **Omogoči:** Datoteke obstajajo tako v S3 kot na lokalnem strežniku (podvajanje).
- ⬜ **Onemogoči:** Datoteke so SAMO v S3 (prihrani prostor na disku)

**Priporočilo:**
- Vklopite med ročno migracijo zaradi varnosti.
- Izklopite po potrditvi, da vse deluje, da prihranite prostor.

#### Način dostave datotek
- **Iz Arnes S3:** Datoteke se prenašajo neposredno iz `shramba.arnes.si`
- **Prek CDN:** datoteke se prenašajo prek vaše domene CDN (hitrejše, prilagojena domena)

#### Kakovost slike
Prilagodite stopnje stiskanja:
- **Kakovost JPEG:** 1–100 % (privzeto: 82 %)
- **Kakovost WebP:** 1–100 % (privzeto: 82 %)
- **Kakovost AVIF:** 1–100 % (privzeto: 82 %)

**Smernice:**
- 90–100 %: odlična kakovost, velike datoteke
- 80–89 %: odlična kakovost, razumna velikost ✅ **Priporočeno**
- 70–79 %: dobra kakovost, manjše datoteke
- <70 %: vidna izguba kakovosti

#### Prednostni vrstni red slikovnih formatov
- **WebP → AVIF:** privzeta nastavitev WordPressa, največja združljivost (~97 % brskalnikov)
- **AVIF → WebP:** boljša kompresija (~90 % brskalnikov)

---

### Zavihek 3: Nalaganje (Bulk Upload)

**Namen:** nalaganje obstoječih datotek iz medijske knjižnice v S3

#### Kako deluje
1. **Pregled knjižnice:** Vtičnik pregleda vse datoteke medijev WordPress
2. **Filtriranje datotek:** Izberite datoteke, ki jih želite naložiti
3. **Nalaganje:** Datoteke se nalagajo v paketih (po 10 naenkrat)
4. **Sledenje napredku:** Napredek in statistika v realnem času

#### Možnosti filtriranja
- **Časovno obdobje:** Naložite datoteke iz določenega časovnega obdobja
- **Vrsta datoteke:** Slike, PDF-ji, videi, avdio datoteke itd.
- **Velikost datoteke:** minimalna/maksimalna velikost v MB
- **Način nalaganja:**
  - **Samo manjkajoče:** naložite samo datoteke, ki niso v S3 (priporočeno)
  - **Vse datoteke:** naložite vse (prepiše obstoječe)

#### Predogled (dry-run)
Preizkusite nalaganje brez dejanskega nalaganja datotek:
- ✅ Prikaže, katere datoteke bi bile naložene
- ✅ Datoteke dejansko niso naložene
- ✅ Varen način za testiranje filtrov

#### Spremljanje napredka
- Napredek v realnem času
- Trenutno ime datoteke
- Hitrost nalaganja (datoteke/sekundo)
- Predvideni preostali čas
- Števci uspešnosti/napak
- **Premor/Nadaljevanje:** Zaustavite in nadaljujte kasneje
- **Prekliči:** Prekini nalaganje

#### Funkcija nadaljevanja
Če pride do prekinitve (zaprtje brskalnika, izpad interneta):
1. Vrnite se na zavihek Bulk Upload (Množično nalaganje)
2. Kliknite gumb **Resume** (Nadaljuj)
3. Nalaganje se nadaljuje od tam, kjer se je ustavilo

---

### Zavihek 4: Orodja

**Namen:** Napredna orodja za upravljanje

#### 1. Varnostna kopija medijske knjižnice

Ustvarite ZIP arhiv celotne medijske knjižnice.

**Možnosti:**
- **Vir:**
  - Iz lokalnih datotek
  - Iz Arnes S3
- **Vrste datotek:** Slike, dokumenti, pisave, videi, drugo

**Postopek:**
1. Izberite vir in vrste datotek.
2. Kliknite **Preglej datoteke**.
3. Preverite število in velikost.
4. Kliknite **Ustvari varnostno kopijo**.
5. Ko je pripravljeno, prenesite ZIP.

**⚠️ Opozorilo:** Varnostne kopije so shranjene na strežniku (uporabljajo prostor na disku). Za resnično varnost jih prenesite na lokalni disk.

#### 2. Obnovitev iz Arnes S3 oblaka

Prenesite datoteke iz S3 nazaj na lokalni strežnik.

**Načini:**
- **Samo manjkajoče:** prenesite samo datoteke, ki lokalno ne obstajajo
- **Vse datoteke:** prenesite vse (prepiše obstoječe)

**Kdaj uporabiti:**
- Po brisanju lokalnih datotek, da prihranite prostor
- Pri selitvi na nov strežnik
- Pred deaktiviranjem vtičnika
- Po naključnem izbrisu datotek

#### 3. Sinhronizacija in vzdrževanje

**Ponovna sinhronizacija metapodatkov:**
- Popravi datoteke v S3 brez metapodatkov WordPressa
- Posodobi bazo podatkov z lokacijami datotek S3

**Izbriši lokalne kopije:**
- Množično izbriše lokalne datoteke, ki obstajajo v S3
- Preveri obstoj S3 pred izbrisom
- Prihrani prostor na disku

**Preveri integriteto:**
- Preveri skladnost med lokalnim in S3
- Preveri obstoj datotek in velikost
- Prepozna poškodovane datoteke

---

### Zavihek 5: Statistika

**Namen:** Spremljanje porabe prostora za shranjevanje in pokritosti

**Opomba:** Statistika se prikaže šele PO konfiguraciji poverilnic v zavihku nastavitve.

#### Pregled
- Skupno število datotek v WordPressu
- Datoteke, naložene v S3 (število + odstotek)
- Datoteke, ki so samo na lokalnem strežniku
- Vizualni indikator napredka, ki prikazuje pokritost S3

#### Razčlenitev po vrsti datotek
Tabela s statističnimi podatki po vrsti datotek:
- Slike, dokumenti, videi, avdio, besedilo, pisave, drugo
- Skupno število, v S3, samo lokalno
- Odstotek pokritosti z barvnimi indikatorji:
  - 🟢 Zelena (≥80 %): odlično pokritost
  - 🟡 Rumena (50–79 %): dobra pokritost
  - 🔴 Rdeča (<50 %): slaba pokritost
  
  #### Velikost pomnilnika
- Skupna velikost lokalnih datotek
- Približna velikost v S3
- Možni prihranki prostora (če je možnost »ohrani lokalno« izklopljena)

#### Zadnje množično nalaganje
Statistika zadnjega množičnega nalaganja:
- Datum in čas
- Skupno število obdelanih datotek
- Uspešno naloženo
- Napake (če obstajajo)
- Čas izvedbe

#### Trenutne nastavitve
Hiter pregled aktivne konfiguracije:
- Podrobnosti povezave S3
- Stanje samodejnega nalaganja
- Stanje ohranjanja lokalno
- Način dostave (S3/CDN)
- Nastavitve kakovosti slike

---

## Integracija CDN

### Zakaj uporabljati CDN?

**Prednosti:**
- ⚡ Hitrejše nalaganje (datoteke iz najbližje lokacije)
- 🌍 Globalni doseg (CDN vozlišča po vsem svetu)
- 📉 Nižji stroški S3 (caching)
- 🔒 Zaščita pred DDoS
- 🎯 Prilagojena domena (`cdn.yourdomain.com`)

### Nastavitev Cloudflare (brezplačni račun)

#### Korak 1: Dodajte domeno v Cloudflare
1. Registrirajte se na [Cloudflare](https://cloudflare.com)
2. Dodajte svojo domeno
3. Posodobite imenske strežnike pri registratorju domen
4. Počakajte na razširitev DNS (5–60 minut)

#### Korak 2: Ustvarite poddomeno CDN
1. V Cloudflare odprite **DNS**
2. Dodajte zapis **CNAME**:
   - **Ime:** `cdn` (`cdn` je le primer. Uporabite lahko `assets`, `media` ... karkoli želite)
   - **Cilj/Kaže na:** `shramba.arnes.si`
   - **Stanje proxyja:** ✅ **Proxied** (oranžni oblak)
   - **TTL:** Samodejno

#### Korak 3: Konfigurirajte pravila za predpomnilnik (NEOBVEZNO, vendar priporočljivo)
1. Pojdite na **Caching → Cache Rules**
2. Ustvarite pravilo:
   - **Name:** "Cache Media Files"
   - **Match:** `cdn.yourdomain.com/*`
   - **Rules:** Eligible for Cache, Respect origin TTL
   
   #### Korak 4: Konfigurirajte vtičnik
1. Pojdite na **Zavihek 2 (Nastavitve)**.
2. Izberite **Uporabi CDN**.
3. Vnesite: `https://cdn.yourdomain.com`.
4. Shranite spremembe.

#### Korak 5: Preizkusite
Naložite testno sliko in preverite, ali URL-ji uporabljajo `cdn.yourdomain.com`.

**Prej:**
```html
<img src="https://shramba.arnes.si/73:arnes-shramba/folder/image.jpg">
```

**Po:**
```html
<img src="https://cdn.yourdomain.com/folder/image.jpg">
```

---

## Pogosta vprašanja

### Splošna vprašanja

**V: Ali to deluje tudi z drugimi ponudniki storitev v oblaku?**  
O: Ne. Ta vtičnik je posebej zasnovan za strukturo URL-jev Arnes Shramba. Ne deluje z AWS S3, Google Cloud, DigitalOcean itd.

**V: Ali bo to upočasnilo mojo spletno stran?**  
O: Ne. Prenos datotek iz S3/CDN je običajno HITREJŠI kot prenos iz vašega strežnika, zlasti za obiskovalce, ki so daleč od lokacije vašega strežnika.

**V: Kaj se zgodi, če Arnes Shramba ne deluje?**  
O: Če je omogočena možnost »Ohrani lokalne datoteke«, bo WordPress prenašal lokalne kopije. Če je ta možnost onemogočena, se mediji ne bodo nalagali, dokler S3 ne bo ponovno na voljo.

**V: Ali se lahko vrnem na lokalno shranjevanje?**  
O: Da. Uporabite zavihek Orodja → Obnova arhiva iz Arnes oblaka, da prenesete vse datoteke, nato pa deaktivirajte vtičnik.

**V: Ali to deluje s programi za izdelavo strani?**  
O: Da. Deluje z Gutenbergom, Elementorjem, Divijem, Beaver Builderjem itd. Poveže se z jedrom WordPressa.

**V: Ali bo naložil moje slike teme?**  
O: Ne. Samo datoteke, naložene prek knjižnice medijev WordPress. Datoteke teme in vtičniki ostanejo nespremenjeni.

**V: Ali lahko to uporabljam na skupnem gostovanju?**  
O: Da, če vaš gostitelj dovoljuje zunanje povezave in izpolnjuje zahteve PHP.

**V: Ali deluje z WooCommerce?**  
O: Da. Slike izdelkov, naložene prek knjižnice medijev, se obdelajo samodejno.

**V: Kaj pa sličice (thumbnails) slik?**  
O: Vse velikosti, ki jih ustvari WordPress (sličice, srednje, velike, WebP, AVIF), se naložijo samodejno.

**V: Ali lahko po nastavitvi spremenim poverilnice S3?**  
O: Da. Posodobite polja na zavihku Povezava, preverite povezavo in shranite. Obstoječe datoteke ostanejo v S3.

**V: Ali obstaja omejitev velikosti datotek?**  
O: Odvisno od vaših nastavitev PHP (`upload_max_filesize`, `post_max_size`) in omejitev Arnes Shramba.

**V: Ali podpira video datoteke?**  
O: Da. Podpira MP4, WebM in druge video formate.

**V: Ali lahko izključim določene datoteke iz prenosa?**  
O: Trenutno ne. Prenesejo se vse datoteke iz medijske knjižnice (na podlagi nastavitve samodejnega prenosa).

### Tehnična vprašanja

**V: Kako deluje prepisovanje URL-jev?**  
O: Vtičnik uporablja filtre WordPress (`wp_get_attachment_url`, `wp_calculate_image_srcset`) za prepisovanje URL-jev medijev na ravni PHP. Konfiguracija nginx ni potrebna.

**V: Kakšna je struktura URL-ja S3?**  
O: `https://shramba.arnes.si/{org_id}:{bucket}/{prefix}/{blog_id}/{year}/{month}/filename.ext`

**V: Kako se shranjujejo metapodatki?**  
O: Vsaka naložena datoteka dobi post meta `_arnes_s3_object` s ključem objekta S3.

**V: Kateri hook se uporablja za nalaganje?**  
O: `wp_generate_attachment_metadata` (prioriteta 999) – se sproži PO tem, ko so ustvarjene vse velikosti slik.

**V: Ali lahko to uporabljam s plugini za optimizacijo slik?**  
O: Da, vendar pazljivo konfigurirajte. Hook za nalaganje se sproži ZADNJI, da zajame optimizirane datoteke. Nekateri plugini za optimizacijo shranjujejo datoteke v ločenih imenikih, ki se ne bodo naložili.

---

## Odpravljanje težav

### Preizkus povezave ni uspel

**Napaka:** „Povezava s S3 ni mogoča“

**Rešitve:**
1. Preverite končno točko: `https://shramba.arnes.si` (vključno z `https://`)
2. Preverite ključ za dostop in skrivni ključ.
3. Potrdite ime skladišča: `arnes-shramba`
4. Preverite, ali mapa obstaja v Arnes Shramba.
5. Preverite, ali je ID organizacije pravilen.
6. Preverite, ali strežnik lahko vzpostavi HTTPS povezave.

### Datoteke se ne nalagajo

**Problem:** Datoteke se nalagajo v WordPress, vendar ne v S3.

**Rešitve:**
1. Preverite, ali je omogočeno samodejno nalaganje (zavihek 2).
2. Preverite nastavitve povezave (zavihek 1).
3. Preverite dnevnik napak PHP
4. Preverite, ali je imenik za nalaganje datotek mogoče zapisovati
5. Preverite omejitev pomnilnika PHP (256 MB+)

**Debug:**
```php
// V wp-config.php
define(‚WP_DEBUG‘, true);
define(‚WP_DEBUG_LOG‘, true);
// Preverite /wp-content/debug.log
```

### Slike se ne prikažejo

**Problem:** Slike se na uporabniškem vmesniku prikažejo poškodovane.

**Rešitve:**
1. Preverite, ali so datoteke v S3 (zavihek 5 – Statistični podatki).
2. Preverite, ali je prepisovanje URL-jev aktivno (zavihek 2).
3. Preverite, ali je domena CDN pravilna (če uporabljate CDN).
4. Izbrišite predpomnilnik brskalnika in predpomnilnik CDN (purge cache)
5. Preizkusite URL S3 neposredno v brskalniku.

### Zastajanje množičnega nalaganja

**Problem:** Nalaganje se ustavi ali poteče časovna omejitev.

**Rešitve:**
1. Povečajte `max_execution_time` (300+).
2. Povečajte `memory_limit` (256 MB+).
3. Preverite internetno povezavo
4. Poskusite z manjšo velikostjo serije
5. Uporabite funkcijo Nadaljuj

### WebP/AVIF se ne generira

**Problem:** Naloženi so samo JPG/PNG

**Rešitve:**
1. Preverite zavihek 5 → Sistemska diagnostika
2. Namestite Imagick: `sudo apt-get install php-imagick`
3. Preverite WordPress 6.5+

### Ikone Font Awesome se ne prikazujejo

**Problem:** Ikone manjkajo v administrativnem vmesniku.

**Rešitev:**
Ikone se zdaj nalagajo iz CDN in delujejo na vseh domenah. Če se še vedno ne prikazujejo:
1. Izbrišite predpomnilnik brskalnika.
2. Preverite, ali v konzoli brskalnika ni napak.
3. Preverite internetno povezavo (dostop do CDN).

---

## Podpora

### Pomoč

- **Dokumentacija:** Ta README
- **GitHub Issues:** [Prijavite napake ali zahtevajte funkcije](https://github.com/yourusername/arnes-s3/issues)
- **E-pošta:** info@squarebow.com
- **Podpora Arnes:** Za težave s poverilnicami S3 se obrnite neposredno na Arnes.

### Preden zaprosite za pomoč

Prosimo, navedite:
1. Različico WordPressa.
2. Različico PHP.
3. Različico vtičnika (trenutno 1.0.8).
4. Napake (iz debug.log).
5. Korake za ponovitev težave.
6. Napake v konzoli brskalnika (če gre za težavo v frontendu).

### Znane omejitve

- Deluje samo z Arnes Shramba (ne podpira drugih ponudnikov S3)
- Ne ustvarja samodejno bucketov/skladišč in map
- Ni vgrajene optimizacije slik (uporablja WordPress core)
- Ne more izključiti določenih vrst datotek iz prenosa
- Funkcija nadaljevanja nalaganja poteče po 24 urah

---

## Dnevnik sprememb

Za podrobno zgodovino različic glejte [CHANGELOG.md](CHANGELOG.md).

### Najnovejša različica: 1.0.8 (19. 2. 2026)

**Kritične popravke:**
- ✅ Popravljena privzeta končna točka, da vključuje `https://`
- ✅ Odstranjen je bil zavajajoč nadomestni znak za ID organizacije
- ✅ Zavihek Statistika zdaj zahteva konfiguracijo, preden prikaže podatke
- ✅ Font Awesome je prešel na CDN (brez nalaganja lokalne knjižnice)

**Novosti v različici 1.0:**
- ✨ Popoln administrativni vmesnik s 5 zavihki
- ✨ Množično nalaganje s spremljanjem napredka
- ✨ Funkcija varnostnega kopiranja in obnovitve
- ✨ Celovit nadzorni panel s statističnimi podatki
- ✨ Nadzor kakovosti slik
- ✨ Podpora CDN
- ✨ Ikone Font Awesome
- ✨ Slovenska lokalizacija

---

## Avtor

- **Avtor:** Aleš Lednik, SquareBow
- **Sodelavci:** Dobrodošli sodelavci iz skupnosti
- **AWS SDK:** Amazon Web Services (optimizirano samo za S3)
- **Arnes:** Akademsko-raziskovalna mreža Slovenije
- **Font Awesome:** Knjižnica ikon

---

## Licenca

Ta vtičnik je licenciran pod licenco **GPL-2.0-ali-novejšo**.

```
Arnes S3 - WordPress Media Offloading Plugin
Copyright (C) 2026 SquareBow

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

---

## Izjava o odgovornosti

Ta vtičnik ni na noben način povezan z Arnesom niti ga Arnes ne podpira. Gre za orodje tretje osebe, ki je zasnovano za delovanje s storitvami Arnes Shramba. Uporabljajte ga na lastno odgovornost. Pred uporabo vtičnikov za shranjevanje v oblaku vedno naredite varnostno kopijo podatkov.

---

## Načrt za nadgradnjo

### Načrtovane funkcije
- [ ] Pravila za izključitev datotek (izključitev določenih vrst ali velikosti datotek)
- [ ] Prenos statistik iz S3 (prek CloudFront ali analitike)
- [ ] Samodejno čiščenje osirotelih datotek S3
- [ ] Napredno filtriranje v statistikah
- [ ] Izvoz/uvoz konfiguracije
- [ ] Načrtovane samodejne varnostne kopije

### Prispevki so dobrodošli

1. Uporabite fork repozitorija
2. Ustvarite features branch
3. Izvedite spremembe
4. Pošljite pull request

Upoštevajte [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/).

---

**Narejeno z ❤️ za slovensko WordPress skupnost**

**Različica:** 1.0.8  
**Zadnja posodobitev:** 19. februar 2026  
**Stanje:** Public stable release. Pripravljeno za produkcijo ✅