# Changelog - Arnes S3

Vse pomembne spremembe tega projekta so dokumentirane v tej datoteki.

---

## [1.0.7] - 2026-02-13

### 🐛 Hotfix - Endpoint Default & Font Awesome CDN

### Popravljeno

**Issue #1: Default Endpoint brez https://** ⚠️ **KRITIČNO**
- **Problem:** Default vrednost v `settings.php` je bila `'shramba.arnes.si'` brez `https://`
- **Rezultat:** Tudi z placeholder-jem je polje pokazalo vrednost brez https://
- **Rešitev:** Spremenjen default v `'https://shramba.arnes.si'`
- **Datoteka:** `includes/settings.php` vrstica 22
- **Zakaj pomembno:** S3 endpoint MORA imeti https:// ali povezava ne bo delovala

**Issue #2: Font Awesome Kit deluje samo na eni domeni** ⚠️ **KRITIČNO**
- **Problem:** Kit `https://kit.fontawesome.com/39890f1c0e.js` je nastavljen samo za razvijalčevo domeno
- **Rezultat:** Ikone se ne prikazujejo na drugih domenah (testiranje, produkcija drugih uporabnikov)
- **Rešitev:** Zamenjava kita s **CDN verzijo** ki deluje povsod:
  ```php
  // PREJ (deluje samo na eni domeni):
  wp_enqueue_script('font-awesome-7', 
      'https://kit.fontawesome.com/39890f1c0e.js', [], '7.0.0', false);
  
  // ZDAJ (deluje povsod):
  wp_enqueue_style('font-awesome-7',
      'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
      [], '6.5.1');
  ```
- **Datoteka:** `includes/admin/admin-page.php` vrstice 39-44
- **Verzija:** Font Awesome 6.5.1 (zadnja stabilna free verzija na CDN)

### Tehnične podrobnosti

**Endpoint Default:**
- Če uporabnik ne izpolni polja, se uporabi default vrednost iz `get_option()`
- Ta mora biti pravilna (z https://) za delovanje S3 povezave

**Font Awesome CDN vs Kit:**
- **Kit:** Samo za domene registrirane v Font Awesome računu
- **CDN:** Deluje na VSEH domenah, brez omejitev
- **Kompromis:** CDN je malenkost počasnejši pri prvem nalaganju (cache), ampak univerzalen

### Kako preveriti če deluje

**Endpoint:**
1. Svež install vtičnika
2. Pojdi na Tab Povezava
3. Polje "S3 končna točka" bi moralo prikazovati: `https://shramba.arnes.si`

**Font Awesome:**
1. Pojdi na katerikoli tab
2. Ikone bi morale biti vidne na tabih (🔌 ⚙️ ☁️ 🛠️ 📊)
3. Deluje na VSAKI domeni, ne samo na razvijalčevi

---

## [1.0.6] - 2026-02-13

### 🐛 Critical Fixes - First Install Experience

Pomembne popravke, odkrite pri testiranju na svežih WordPress instalacijah.

### Popravljeno

**Issue #1: Endpoint placeholder manjka https://**
- **Prej:** Polje "S3 končna točka" brez placeholder besedila
- **Zdaj:** Placeholder `https://shramba.arnes.si` viden v polju
- **Lokacija:** Tab Povezava, vrstica 273

**Issue #2: Placeholder organizacije kaže uporabnikov ID**
- **Prej:** Placeholder "26" (razvijalčev organization ID)
- **Zdaj:** Brez placeholder-ja (prazno polje)
- **Razlog:** Vsak uporabnik ima svoj ID, "26" je zavajalo
- **Lokacija:** Tab Povezava, vrstica 305

**Issue #3: Phantom statistika pred konfiguracijo** ⚠️ **KRITIČNO**
- **Prej:** Statistika zavihek je prikazoval obstoječe WordPress datoteke pred vzpostavitvijo S3 povezave
- **Problem:** Za nove uporabnike je to izgledalo kot napaka/halucinirane številke
- **Zdaj:** Če credentials niso konfigurirani, prikaže opozorilo namesto statistik:
  ```
  ⓘ Konfiguracija potrebna
  Statistika bo na voljo, ko boste konfigurirali povezavo z Arnes S3.
  [Pojdi na zavihek Povezava]
  ```
- **Logika:** Preveri ali so `access_key`, `secret_key` in `org_id` izpolnjeni
- **Rezultat:** Prepreči zmedo pri prvi namestitvi

### Tehnične podrobnosti

**Credentials Check:**
```php
$credentials_configured = ! empty( $settings['access_key'] ) && 
                         ! empty( $settings['secret_key'] ) && 
                         ! empty( $settings['org_id'] );

if ( ! $credentials_configured ) {
    // Prikaži opozorilo in gumb za povezavo
    return; // Zaustavi prikaz statistik
}
```

### UX Izboljšave
- Bolj jasen first-time user experience
- Odstranjen zavajujoč placeholder "26"
- Dodan https:// placeholder za jasnost
- Statistika se prikaže šele, ko je vtičnik pravilno konfiguriran

---

## [1.0.5] - 2026-02-13

### 🐛 Bug Fix - Missing Label for Unknown File Types

### Popravljeno
**Tab Statistika - Razčlenitev po tipih:**
- Dodana manjkajoča oznaka "Ostalo" za neznane tipe datotek
- Prej: Samo ikona sponke brez teksta (prazno)
- Zdaj: `📎 Ostalo` (sponka ikona + besedilo)

### Dodano
**Podpora za fonte:**
- Dodana ikona za fonte: `<i class="fa-solid fa-font arnes-icon-sm"></i>`
- Dodana oznaka: "Fonti"
- Fonti se zdaj prikažejo kot `🔤 Fonti` namesto `📎 Ostalo`

### Tehnične podrobnosti
- **Prej:** `ucfirst( $type_data['type'] )` - kapitaliziral MIME tip (lahko prazno)
- **Zdaj:** `'Ostalo'` - jasna privzeta oznaka za vse neznane tipe
- Dodana podpora v `$icon_map` in `$type_labels` za tip `font`

---

## [1.0.4] - 2026-02-13

### ✨ Popolna Font Awesome Integracija - Tab Statistika

### Spremenjeno
**Zamenjava vseh emojiev z Font Awesome ikonami v Tab Statistika:**

**Naslovi sekcij:**
- 📊 → `<i class="fa-solid fa-chart-pie arnes-icon"></i>` Pregled medijske knjižnice
- 📁 → `<i class="fa-solid fa-folder arnes-icon"></i>` Razčlenitev po tipih datotek  
- 💾 → `<i class="fa-solid fa-hard-drive arnes-icon"></i>` Velikost shranjenih datotek
- ⏱️ → `<i class="fa-solid fa-clock arnes-icon"></i>` Zadnje množično nalaganje
- ⚙️ → `<i class="fa-solid fa-gear arnes-icon"></i>` Trenutne nastavitve

**Ikone za tipe datotek:**
- 🖼️ → `<i class="fa-solid fa-image arnes-icon-sm"></i>` Slike
- 📄 → `<i class="fa-solid fa-file-pdf arnes-icon-sm"></i>` Dokumenti
- 🎥 → `<i class="fa-solid fa-video arnes-icon-sm"></i>` Video
- 🎵 → `<i class="fa-solid fa-music arnes-icon-sm"></i>` Zvok
- 📝 → `<i class="fa-solid fa-file-lines arnes-icon-sm"></i>` Besedilo
- 📎 → `<i class="fa-solid fa-paperclip arnes-icon-sm"></i>` Ostalo

**Statusne ikone:**
- ✓ → `<i class="fa-solid fa-circle-check arnes-icon-success"></i>` Vključeno
- ✗ → `<i class="fa-solid fa-circle-xmark arnes-icon-error/warning"></i>` Izključeno

**Dostava datotek ikone:**
- ☁️ → `<i class="fa-solid fa-cloud arnes-icon-sm"></i>` Arnes S3
- 📡 → `<i class="fa-solid fa-network-wired arnes-icon-sm"></i>` CDN

**Obvestila ikone:**
- 💡 → `<i class="fa-solid fa-lightbulb arnes-icon-sm"></i>` Namig
- ⚠️ → `<i class="fa-solid fa-triangle-exclamation arnes-icon-sm"></i>` Pozor

### Rezultat
- Tab Statistika je sedaj 100% brez emojiev
- Profesionalen in dosleden vizualni izgled
- Uporablja uniform CSS classes za enostavno vzdrževanje

---

## [1.0.3] - 2026-02-13

### ✨ Font Awesome 7 Integration

### Dodano
**Font Awesome 7 Icons:**
- Integriran Font Awesome 7 Free kit za profesionalne ikone
- Avtomatsko nalaganje samo na plugin admin straneh (ne na frontend)
- Uniform CSS styling za konsistentne ikone po celotnem UI-ju

**Tab Navigation Icons:**
- Povezava: 🔌 plug icon
- Nastavitve: 🎚️ sliders icon  
- Množično nalaganje: ☁️↑ cloud-arrow-up icon
- Orodja: 🧰 toolbox icon
- Statistika: 📈 chart-line icon

**CSS Classes:**
- `.arnes-icon` - Default ikone (modra, 16px)
- `.arnes-icon-success` - Zelene ikone za uspeh
- `.arnes-icon-error` - Rdeče ikone za napake
- `.arnes-icon-warning` - Oranžne ikone za opozorila
- `.arnes-icon-info` - Modre ikone za informacije
- `.arnes-icon-sm` - Manjše inline ikone (14px)

**Icon System:**
- Enostavna zamenjava `fa-ICON-NAME` za različne ikone
- Konsistenten stil, barva in velikost
- Copy-paste ready templates za hitro uporabo

### Spremenjeno
- Tab navigacija uporablja Font Awesome ikone namesto besedila
- Pripravljena infrastruktura za ikone po celotnem admin vmesniku

### Tehnične podrobnosti
- Font Awesome se naloži samo na `media_page_arnes-s3`
- Crossorigin attribute dodan za varno nalaganje
- ~70KB (gzipped) - nima vpliva na frontend

---

## [1.0.2] - 2026-02-13

### ✨ PHASE 5 - Statistics Tab + Bug Fixes

### Dodano
**Tab Statistika (Phase 5):**
- Pregled medijske knjižnice - skupno število priponk, število datotek v S3, samo lokalno
- Vizualni progress bar za pokritost S3
- Razčlenitev po tipih datotek (slike, dokumenti, video, zvok)
- Statistika pokritosti S3 po tipih s progress bar-i
- Velikost shranjenih datotek (lokalno in v S3)
- Prikaz potencialnega prihraneka prostora
- Statistika zadnjega množičnega nalaganja
- Trenutne nastavitve vtičnika (povezava, način delovanja, kakovost slik)

**Vizualne izboljšave:**
- Ikone za različne tipe datotek (🖼️ slike, 📄 dokumenti, 🎥 video, itd.)
- Barvno kodiranje (zelena za S3, rdeča za lokalno, modra za CDN)
- Progress bar-i za vizualno predstavitev pokritosti
- Obvestila in namigi za uporabnika

### Popravljeno
**JavaScript Bug - CDN Domain Field:**
- Popravljena napaka kjer se CDN domena polje ni prikazalo/skrilo ob preklopu med radio gumbi (Arnes S3 ↔ CDN)
- Napaka: `getElementById('cdn_domain_row')` (podčrtaji)
- Pravilno: `getElementById('cdn-domain-row')` (pomišljaji)
- Zdaj se polje takoj prikaže/skrije ob kliku brez potrebe po shranjevanju nastavitev

### Tehnične podrobnosti
- Statistika se izračuna neposredno iz WordPress baze
- Uporablja `postmeta` tabelo za zaznavanje S3 statusov (`_arnes_s3_object`)
- Optimizirane SQL poizvedbe za hitro pridobivanje podatkov
- Združljivo z obstoječo funkcionalnostjo množičnega nalaganja

---

## [1.0.1] - 2026-02-12

### 🎨 UI Reorganizacija - Tab Nastavitve
Reorganizacija Tab Nastavitve za boljšo logičen vrstni red nastavitev

### Spremenjeno
**Tab Nastavitve - Nov vrstni red sekcij:**
1. **Avtomatsko nalaganje** (prej 3.)
2. **Ohrani lokalne datoteke** (prej 4.)
3. **Način dostave datotek** (prej 5.)
4. **Nastavitve kvalitete slik** (prej 1.)
5. **Prioriteta formatov slik** (prej 2.)

**Razlog za spremembo:**
- Logičnejši potek: Najprej osnovne nastavitve (auto upload, keep local)
- Nato delivery nastavitve (S3/CDN)
- Na koncu napredne nastavitve (image quality, format priority)

**Navodila (desna stran):**
- Preštevilčena navodila (1-5) skladno z novo ureditvijo
- Dodana sekcija "5. Prioriteta formatov slik" v navodilih
- Izboljšane razlage za vsako sekcijo

### Popravljeno
- Odstranjene duplicate sekcije v Tab Nastavitve (stare verzije brez številčenja)
- Konsistentno številčenje (1-5) na levi in desni strani

---

## [1.0.0] - 2026-02-12 🎉 STABLE RELEASE

### ✨ PHASE 4.5 - Image Format Priority + UI Polish
Feature-complete release z vsemi načrtovanimi funkcionalnostmi

### Dodano
**Image Format Priority (Phase 4.5):**
- Nova nastavitev v Tab Nastavitve: "Prioriteta formatov slik"
- Izbira med:
  - **WebP First, AVIF Second** (WordPress privzeto, ~97% kompatibilnost)
  - **AVIF First, WebP Second** (najboljša kompresija, ~90% kompatibilnost)
- Backend filter `arnes_s3_reorder_image_formats()` spreminja vrstni red formatov v srcset
- Uporablja WordPress filter `wp_calculate_image_srcset`
- Browser izbere PRVI format ki ga podpira iz srcset seznama

**Kako deluje:**
- WordPress generira več verzij vsake slike: `original.jpg`, `original.jpg.webp`, `original.jpg.avif`
- Te verzije se vključijo v `<img srcset="...">` atribut
- Browser izbere prvi format ki ga podpira
- **WebP First:** srcset="...webp 800w, ...avif 800w, ...jpg 800w" → Browser vzame WebP (če podpira)
- **AVIF First:** srcset="...avif 800w, ...webp 800w, ...jpg 800w" → Browser vzame AVIF (če podpira)

**Prednosti AVIF First:**
- 30-50% manjše datoteke kot WebP pri isti kvaliteti
- Boljša kompresija = hitrejše nalaganje
- Moderni browserji (Chrome 85+, Firefox 93+, Safari 16+) ga podpirajo

**Prednosti WebP First:**
- Višja kompatibilnost (~97% vs ~90%)
- WordPress privzeta nastavitev
- Vsi moderni browserji ga podpirajo

### Spremenjeno
**UI Izboljšave:**
- Sync & Maintenance sekcija: Popravljene barve leve črte
  - Re-sync S3 Metadata: Modra (#2271b1) ✅
  - Bulk Delete lokalnih kopij: Rdeča (#d63638) ✅
  - Preverjanje integritete: Zelena (#00a32a) ✅
- WordPress native styling za vse elemente
- Konsistenten dizajn across all tabs

**Backend:**
- Nova nastavitev `arnes_s3_format_priority` v `settings.php`
- Registracija nastavitve v `admin-settings.php` z sanitize callback
- Filter funkcija v `image-quality.php`
- Sanitize funkcija `arnes_s3_sanitize_format_priority()` zagotavlja veljavne vrednosti

### Tehnična implementacija
**Files changed:**
- `includes/settings.php` - Dodana format_priority nastavitev
- `includes/admin/admin-settings.php` - Registracija + sanitize callback
- `includes/admin/admin-page.php` - UI v Tab Nastavitve + barve leve črte
- `includes/image-quality.php` - Filter funkcija za srcset reordering

**WordPress Filters:**
- `wp_calculate_image_srcset` - Spreminja vrstni red formatov v srcset
- Filter priority: 10 (default)
- Parameters: sources, size_array, image_src, image_meta, attachment_id

### Testing
**Kako testirati format priority:**
```html
<!-- Preveri srcset vrstni red v HTML-ju -->
<img srcset="
  ...avif 800w,  ← AVIF First mode
  ...webp 800w,
  ...jpg 800w
">
```

**Browser DevTools test:**
1. Odpri sliko na spletni strani
2. Inspect element (F12)
3. Preveri `srcset` atribut
4. Preveri kateri format se dejansko naloži (Network tab)

**Pričakovano obnašanje:**
- **AVIF First + Chrome 85+:** Naloži .avif
- **AVIF First + Firefox 92-:** Naloži .webp (AVIF not supported)
- **WebP First + vsi moderni browserji:** Naloži .webp
- **Stari browserji:** Naloži .jpg (fallback)

---

## 🎉 MILESTONE: Feature-Complete Plugin

Plugin je sedaj **PRODUCTION-READY** z vsemi načrtovanimi funkcionalnostmi:

**Phase 1:** ✅ Basic S3 Upload + Diagnostics
**Phase 2:** ✅ URL Rewriting (S3/CDN serving)
**Phase 3:** ✅ Bulk Upload
**Phase 4.1:** ✅ Image Quality Settings
**Phase 4.2:** ✅ Backup Functionality
**Phase 4.3:** ✅ Restore Functionality
**Phase 4.4:** ✅ Sync & Maintenance
**Phase 4.5:** ✅ Image Format Priority

---

## [0.9.9] - 2026-02-12

### ✨ PHASE 4.4 - Sync & Maintenance orodja DOKONČANO
Implementacija vseh treh Sync & Maintenance funkcionalnosti

### Dodano
**1. Re-sync S3 Metadata:**
- Skeniraj vse attachmente ki nimajo `_arnes_s3_object` post meta
- Preveri če datoteke obstajajo v S3 (HEAD request)
- Prikaži število attachmentov z manjkajočim metadata
- "Popravi metadata" button za avtomatsko popravilo
- Backend funkcije:
  - `arnes_s3_scan_for_metadata_sync()` - Skeniranje
  - `arnes_s3_fix_metadata()` - Popravilo metadata
- AJAX handler-ja: `arnes_s3_ajax_sync_scan`, `arnes_s3_ajax_sync_fix`

**2. Bulk Delete lokalnih kopij:**
- Skeniraj vse attachmente ki imajo S3 meta
- Preveri kateri imajo datoteke lokalno IN v S3
- Prikaži število datotek in prihranjen prostor na disku
- "Izbriši lokalne kopije" button z varnostno potrditvijo
- Vključuje originalne datoteke in vse thumbnails
- Backend funkcije:
  - `arnes_s3_scan_for_local_delete()` - Skeniranje
  - `arnes_s3_delete_local_files()` - Brisanje lokalnih kopij
- AJAX handler-ja: `arnes_s3_ajax_local_delete_scan`, `arnes_s3_ajax_local_delete_process`

**3. Preverjanje integritete:**
- Primerjaj vse attachmente - lokalne datoteke vs S3
- Preveri velikost datotek (size mismatch detection)
- Poročilo o stanju:
  - Skupaj attachmentov
  - V redu (sync) - datoteke obstajajo lokalno IN v S3 z enako velikostjo
  - Brez S3 meta - attachmenti brez `_arnes_s3_object`
  - Manjka v S3 - ima meta ampak datoteka ne obstaja v S3
  - Manjka lokalno - obstaja v S3 ampak ne lokalno
  - Neujemanje velikosti - datoteke obstajajo ampak različne velikosti
- Backend funkcija: `arnes_s3_check_integrity()`
- AJAX handler: `arnes_s3_ajax_integrity_check`

### UI/UX Izboljšave
- **WordPress native styling** za vse elemente
- **Barvno kodiranje** sekcij:
  - Modra (info): Re-sync metadata
  - Rdeča (warning): Bulk delete
  - Zelena (success): Integrity check
- **Loading indicators** za vse dolgotrajne operacije
- **Progress sporočila** med skeniranjem
- **Varnostne potrditve** (confirm dialog) za destruktivne operacije
- **Disabled state management** za gumbe

### Tehnična implementacija
**Backend (`includes/backup-restore.php`):**
- 6 novih funkcij za sync & maintenance operacije
- S3 HEAD request za preverjanje obstoječnosti datotek
- WordPress post meta operacije za metadata sync
- Filesystem operacije za local delete
- Comprehensive error handling

**AJAX (`includes/admin/ajax-backup.php`):**
- 5 novih AJAX handler-jev
- Nonce verification za vse operacije
- Capability check (`manage_options`)
- JSON serialization za prenos podatkov

**Frontend (`assets/js/admin-backup.js`):**
- 6 novih event handler-jev
- Loading state management
- Error handling z user-friendly sporočili
- Helper funkcije za formatiranje podatkov

**UI (`includes/admin/admin-page.php`):**
- Zamenjava placeholder sekcije z dejansko funkcionalnostjo
- 3 sub-sekcije z ločenimi workflow-i
- Responsive layout s flex styling

### Varnostna opozorila
- **Bulk Delete**: Permanent operation - datoteke NE GREDO v trash
- **Integrity Check**: Read-only operacija - ne spreminja datotek
- **Metadata Sync**: Safe operacija - samo dodaja manjkajoče post meta

### Use Cases
**Re-sync Metadata:**
- Po ročnem uploadu datotek v S3 preko MinIO CLI
- Po restoranju database backup-a
- Po migraciji iz drugega S3 plugina

**Bulk Delete:**
- Prihranki prostora na disku po uspešnem S3 offloadu
- Čiščenje lokalnih kopij ko je "Ohrani lokalne datoteke" = OFF
- Server disk space optimization

**Integrity Check:**
- Redna preverjanja po večjih operacijah
- Debugging missing files issues
- Pre-migration verification
- Post-restore verification

---

## [0.9.8] - 2026-02-12

### ✨ UX Izboljšave - Loading indicators za scan operacije
Dodan vizualni feedback med dolgotrajnimi scan operacijami

### Dodano
- **Loading indicator za Restore scan:**
  - WordPress spinner animacija med skeniranjem S3
  - Sporočilo: "Skeniram S3 bucket..."
  - Dodatno opozorilo: "To lahko traja več minut za velike Media Library."
  - Takoj viden ko uporabnik klikne "Skeniraj S3 datoteke"
- **Loading indicator za Backup scan:**
  - WordPress spinner animacija med skeniranjem lokalnih datotek
  - Sporočilo: "Skeniram Media Library..."
  - Takoj viden ko uporabnik klikne "Skeniraj datoteke"

### Spremenjeno
- **Restore scan UX:**
  - Button besedilo se ne spreminja več (ostane "Skeniraj S3 datoteke")
  - Namesto spremembe button teksta se prikaže loading indicator box
  - Loading indicator uporablja WordPress native `.spinner` class
- **Backup scan UX:**
  - Button besedilo se ne spreminja več (ostane "Skeniraj datoteke")
  - Loading indicator uporablja WordPress native styling

### Popravljeno
- **"Plugin appears stuck" problem:**
  - Pri "Vse datoteke" restore mode lahko skeniranje traja >1 minuto
  - Prej: Buttoni so bili greyed out brez vizualnega feedback-a
  - Zdaj: Uporabnik vidi spinner in sporočilo da operacija poteka

### Tehnično
- Uporablja WordPress `.spinner.is-active` class za animacijo
- Loading box uporablja `.notice.notice-info` WordPress styling
- Spinner je float: left z 10px margin za poravnavo
- Error handling izboljšan z jasnimi sporočili

### User Experience
Prej:
```
Klik button → Buttoni greyed out → ... 60 sekund tišina ... → Rezultati
                                     ↑
                              Uporabnik misli da je crashed
```

Zdaj:
```
Klik button → Loading spinner + sporočilo → Rezultati
              "Skeniram S3 bucket..."
              "To lahko traja več minut..."
```

---

## [0.9.7] - 2026-02-12

### 🔧 CRITICAL FIX - JavaScript cache in button ikone
Popravek JavaScript cache problema in odstranitev ikon iz buttonov

### Popravljeno
- **JavaScript cache problem** - GLAVNI POPRAVEK:
  - Vsi JavaScript enqueue-i sedaj uporabljajo `ARNES_S3_VERSION` namesto hardcoded `'1.0'`
  - To zagotavlja da browser naloži novo verzijo JavaScript-a pri vsaki posodobitvi plugina
  - Prej: `wp_enqueue_script(..., '1.0', ...)` → Browser cache nikoli ni izvedel za spremembe
  - Zdaj: `wp_enqueue_script(..., ARNES_S3_VERSION, ...)` → Browser cache se osvežuje z vsako verzijo
- **Button ikone odstranjene**:
  - Dodana CSS pravila da odstrani `::before` pseudo-elemente iz backup in restore scan buttonov
  - Ikone so bile dodane preko WordPress Dashicons CSS-ja
- **Restore scan button neodziven** - Rešitev:
  - Glavni vzrok je bil JavaScript cache problem
  - Browser je nalagal staro verzijo JavaScript-a brez restore event handler-jev
  - Sedaj bo z novo verzijo naložil svež JavaScript

### Spremenjeno
- `admin-page.php`:
  - Vsi JavaScript enqueue-i uporabljajo `ARNES_S3_VERSION` za version number
  - Dodana inline CSS style tag v Tab Orodja za odstranitev button ikon

### Kako testirati
1. **Posodobite plugin** na verzijo 0.9.7
2. **Hard refresh strani** s CTRL+SHIFT+R (ali CMD+SHIFT+R na Mac)
3. **Preveri ikone** - "Skeniraj datoteke" in "Skeniraj S3 datoteke" buttona ne smeta imeti ikon
4. **Testiraj restore scan** - klikni "Skeniraj S3 datoteke" in preveri da deluje
5. **Preveri browser Console (F12)** - ne sme biti JavaScript errors

### Debug če še vedno ne deluje
Če restore scan še vedno ne deluje:
1. **Clear browser cache popolnoma**: Chrome → Settings → Clear browsing data → Cached images and files
2. **Preveri naložen JavaScript**: F12 → Network tab → Filter: admin-backup.js → Preveri da je verzija 0.9.7
3. **Preveri event handler-je**: F12 → Console → vtipkaj: `jQuery('#arnes-s3-restore-scan-btn').data('events')` in preveri da obstaja 'click' event
4. **Preveri AJAX URL**: F12 → Console → vtipkaj: `arnesS3Backup` in preveri da je objekt definiran

---

## [0.9.6] - 2026-02-11

### 🔧 Bugfixes - Restore funkcionalnost in file type filtering
Popravki za restore scan in dodajanje file type filtering v AJAX handler-je

### Popravljeno
- **Restore scan ne deluje** - Popravljeno:
  - Restore scan AJAX handler zdaj pravilno default-a file_types če je prazen array
  - Dodana validacija da je file_types array
  - Če je file_types prazen, se uporabijo vsi tipi (image, application, font, video, other)
- **Backup scan file type filtering** - Dodano:
  - Backup scan AJAX handler zdaj pravilno prejme in uporabi file_types parameter
  - Backup create AJAX handler zdaj pravilno prejme in uporabi file_types parameter
- **JavaScript emojis** - Odstranjeni emojiji:
  - Vsi emojiji odstranjeni iz button tekstov
  - Browser cache lahko prikazuje stare emojije - refreshajte stran s CTRL+F5

### Tehnično
- `arnes_s3_ajax_backup_scan()` - Dodan file_types parameter z default vrednostmi
- `arnes_s3_ajax_backup_create()` - Dodan file_types parameter z default vrednostmi
- `arnes_s3_ajax_restore_scan()` - Izboljšana validacija file_types parametra z default vrednostmi

### Debug nasveti
Če restore scan še vedno ne deluje:
1. Preverite WordPress admin že ima nastavljene S3 credentials (Tab Povezava)
2. Preverite ali obstajajo attachments z `_arnes_s3_object` post meta ključem
3. Odprite browser Console (F12) in preverite JavaScript errors
4. Hard refreshajte stran (CTRL+F5) da izbrišete cache

---

## [0.9.5] - 2026-02-11

### ✨ PHASE 4.3 - Restore funkcionalnost + UI izboljšave
Implementacija restore sistema in odstranitev emojijev iz UI

### Dodano
- **Restore funkcionalnost** v zavihku Orodja:
  - Skeniranje S3 bucket-a za datoteke za restore
  - Izbira restore mode (samo manjkajoče ali vse datoteke)
  - File type filter (slike, dokumenti, fonti, video, ostalo)
  - Batch processing restore-a (5 datotek naenkrat)
  - Progress bar z real-time tracking
  - Kreiranje lokalnih map če ne obstajajo
- **Backend funkcije** v `backup-restore.php`:
  - `arnes_s3_scan_for_restore()` - Skeniranje S3 za restore
  - `arnes_s3_restore_file()` - Restore posamezne datoteke iz S3
  - `arnes_s3_matches_file_types()` - File type filtering helper
- **AJAX handlers** v `ajax-backup.php`:
  - `arnes_s3_ajax_restore_scan` - Scan S3 za restore
  - `arnes_s3_ajax_restore_process` - Batch processing restore-a
- **JavaScript** posodobitve v `admin-backup.js`:
  - Restore scan in process funkcionalnost
  - Batch processing z progress tracking
  - File type filtering za backup in restore

### Spremenjeno
- **UI izboljšave:**
  - Odstranjeni VSI emojiji iz Nastavitve in Orodja tabov
  - Odstranjena opcija "Oboje" pri backup source (ni smiselna)
  - Dodani file type checkboxes (slike, dokumenti, fonti, video, ostalo)
  - WordPress native styling za vse elemente
- **Tab Nastavitve:**
  - Odstranjen emoji iz "Nastavitve kvalitete slik" naslova
- **Tab Orodja:**
  - Odstranjeni emojiji iz vseh naslovov sekcij
  - Odstranjeni emojiji iz vseh gumbov
  - Posodobljena navodila z razlago WebP/AVIF servanja
- **File type filtering:**
  - Dodan v backup scan funkcijo
  - Dodan v restore scan funkcijo
  - Helper funkcija `arnes_s3_matches_file_types()`

### Popravljeno
- Backup scan sedaj podpira file type filtering
- Restore omogoča izbiro restore mode in file types

### Razlaga: WebP/AVIF servanje
Plugin deluje pravilno! Ko je "Ohrani lokalne datoteke" = OFF:
1. WordPress generira WebP/AVIF ob nalogu slike
2. Plugin jih naloži v Arnes S3
3. Plugin zbriše lokalne kopije (prihrani prostor)
4. URL rewriter servira vse datoteke direktno iz S3/CDN

Datoteke so varne v S3, lokalni disk pa je prost.

---

## [0.9.4] - 2026-02-11

### ✨ PHASE 4.2 - Backup funkcionalnost
Implementacija backup sistema za Media Library

### Dodano
- **Backup funkcionalnost** v zavihku Orodja:
  - Skeniranje Media Library za backup
  - Izbira vira datotek (lokalne, S3, oboje)
  - Opcija za vključitev thumbnails in optimiziranih verzij
  - Ustvarjanje ZIP arhiva celotne Media Library
  - Progress bar za ustvarjanje backup-a
  - Seznam obstoječih backupov z download linki
  - Brisanje starih backupov
- **Nova datoteka** `/includes/backup-restore.php`:
  - `arnes_s3_scan_for_backup()` - Skeniranje Media Library
  - `arnes_s3_create_backup_zip()` - Ustvarjanje ZIP arhiva
  - `arnes_s3_get_existing_backups()` - Seznam obstoječih backupov
  - `arnes_s3_delete_backup()` - Brisanje backup datoteke
- **AJAX handlers** `/includes/admin/ajax-backup.php`:
  - `arnes_s3_ajax_backup_scan` - Scan operacija
  - `arnes_s3_ajax_backup_create` - Create ZIP operacija
  - `arnes_s3_ajax_backup_list` - List obstoječih backupov
  - `arnes_s3_ajax_backup_delete` - Delete backup operacija
- **JavaScript** `/assets/js/admin-backup.js`:
  - AJAX procesiranje za scan in create backup
  - Progress tracking z visual feedback
  - Helper funkcije za formatting (formatBytes, number_format)

### Spremenjeno
- **Tab Orodja** - Implementiran delujoči UI namesto placeholder-ja
- **admin-page.php** - Dodana UI sekcija za backup funkcionalnost
- **admin-page.php** - Enqueue admin-backup.js in nonce za AJAX
- **arnes-s3.php** - Vključeni novi files za backup funkcionalnost

### Tehnično
- Backup datoteke se shranjujejo v `/wp-content/uploads/arnes-s3-backups/`
- ZIP arhiv vključuje celotno WordPress uploads strukturo
- Uporablja PHP ZipArchive class za kompresijo
- Podpora za velike Media Library (skeniranje vseh attachment posts)
- Thumbnails in optimizirane verzije (WebP, AVIF) so vključeni

### Načrtovano (prihodnje verzije)
- **Phase 4.3:** Restore funkcionalnost iz S3 nazaj na lokalni strežnik
- **Phase 4.4:** Sync & maintenance orodja (re-sync metadata, bulk delete local, integrity check)

---

## [0.9.3] - 2026-02-11

### ✨ PHASE 4.1 - Image Quality Settings
Implementacija nastavitev kvalitete slik za JPEG, WebP in AVIF formate

### Dodano
- **Image Quality Settings** v zavihku Nastavitve:
  - JPEG kvaliteta (1-100, privzeto: 82)
  - WebP kvaliteta (1-100, privzeto: 82)
  - AVIF kvaliteta (1-100, privzeto: 82)
  - Range sliderji z sinhronizacijo number input polj
  - Vizualna priporočila za različne kvalitete
- **Nova datoteka** `/includes/image-quality.php`:
  - `arnes_s3_set_image_quality()` - WordPress `wp_editor_set_quality` filter
  - `arnes_s3_set_jpeg_quality()` - Fallback za starejše vtičnike
  - `arnes_s3_set_webp_quality()` - Fallback za WebP
- **Sanitize funkcija** `arnes_s3_sanitize_quality()` - Omejitev vrednosti 1-100
- **Number field callback** `arnes_s3_number_field_cb()` za quality input polja

### Spremenjeno
- **settings.php** - Dodane nove nastavitve: `jpeg_quality`, `webp_quality`, `avif_quality`
- **admin-settings.php** - Registracija novih settings z sanitize callback-om
- **admin-page.php** - Implementirana delujoča polja namesto placeholder-ja v Tab 2
- **JavaScript sync** - Range sliderji sinhronizirajo vrednosti z number input polji

### Tehnično
- Kvaliteta slik se nastavlja pred WordPress image processing-om
- Filter deluje za vse formate: JPEG, WebP, AVIF
- Privzete vrednosti (82) so enake WordPress defaults
- Hidden fields v Tab 1 ohranjajo kvaliteto pri shranjevanju povezave
- Nastavitve so takoj aktivne - nova nalaganja uporabljajo nove kvalitete

---

## [0.8.4] - 2026-02-09

### 🔧 MAJOR FIX - Upload All WordPress Sizes
Popravek da plugin uploada VSE WordPress-generirane verzije slik

### Popravljeno
- **Upload handler** - Plugin zdaj uploada VSE WordPress-generirane verzije:
  - Original file
  - Vse thumbnail sizes (150x150, 300x300, 1024x683, itd.)
  - WebP verzije (.jpg.webp, image-1024x576-jpg.webp)
  - AVIF verzije (.jpg.avif)
  - Scaled versions (-scaled.jpg)
- **ACL spremenjen na public-read** - Files so zdaj javno dostopni brez signed URLs
  - Prej: `ACL: 'private'` → potrebni signed URLs
  - Zdaj: `ACL: 'public-read'` → direkten javni dostop
- **Thumbnails v Media Library** - Zdaj delujejo ker vse size verzije obstajajo v S3
- **WebP/AVIF srcset** - Responsive images zdaj delujejo ker vse format verzije obstajajo v S3

### Spremenjeno
- **File naming conflict resolved** - Preimenovano `/includes/admin/settings.php` v `/includes/admin/admin-settings.php`
  - Prej: Dva files z istim imenom (settings.php)
  - Zdaj: Jasna ločitev: `includes/settings.php` (helper) vs `includes/admin/admin-settings.php` (registration)
  - Main plugin file posodobljen: `require_once 'includes/admin/admin-settings.php'`

### Tehnično
- Uploader uporablja `wp_get_attachment_metadata()` za branje vseh WordPress-generiranih sizes
- Glob pattern matching za zaznavanje WebP/AVIF verzij ki niso v metadata
- Tracking uploaded files da preprečimo duplicate uploads
- Verbose error logging s štetjem uploaded files

### Pomembno
- **Bucket policy mora biti public** za delovanje!
- Stare datoteke (uploaded pred v0.8.4) še vedno imajo `private` ACL
- Za bulk re-upload starih datotek čakaj Phase 3 (Bulk Upload)

---

## [0.8.3] - 2026-02-09

### ✅ CRITICAL FIX - Arnes URL Format
Dodana podpora za Arnes Shramba organization ID v URL strukturi

### Dodano
- **Organization ID field** v Tab 1 (Povezava)
  - Arnes uporablja poseben URL format: `https://shramba.arnes.si/ORG_ID:BUCKET/PATH`
  - Uporabnik vnaša svoj organization ID (npr. "26")
  - Navodila za iskanje org ID v Arnes portalu
- **Posodobljen URL building** - `arnes_s3_build_url()` zdaj uporablja pravilen Arnes format

### Popravljeno
- **URL rewriting dela** - URLs zdaj v pravilnem formatu z organization ID
- **Direkten dostop do files** - Slike se prikazujejo v browser-ju (prej 404/NoSuchBucket)

### Tehnično
- Nova nastavitev: `arnes_s3_org_id`
- Hidden field za org_id dodan v Tab 2 form
- URL format: `{endpoint}/{org_id}:{bucket}/{object_key}`

---

## [0.8.2] - 2026-02-09

### 🚨 CRITICAL HOTFIX
Popravek settings reset bug-a kjer so se nastavitve brisale med tab-i

### Popravljeno
- **Settings preservation** - Dodani hidden fields v oba form-a (Tab 1 + Tab 2)
  - Prej: Ko shranješ Tab 1 → izbriše nastavitve iz Tab 2 in obratno
  - Zdaj: Vse nastavitve se ohranjajo med shranjevanjem kateregakoli tab-a
- **404 Not Found error** - Posledica praznega endpoint-a zaradi settings reset-a
  - Error se ne bo več pojavil ker endpoint ostane shranjen

### Tehnično
- Tab 1 (Povezava) form zdaj vsebuje hidden fields za: `keep_local`, `cdn_domain`, `serve_mode`
- Tab 2 (Nastavitve) form zdaj vsebuje hidden fields za: `endpoint`, `bucket`, `prefix`, `access_key`, `secret_key`
- Oba form-a še vedno uporabljata isti `arnes_s3_settings_group` ampak zdaj vedno submittata VSE vrednosti

---

## [0.8.1] - 2026-02-09

### 🔧 HOTFIX
Popravek upload hook-a in plugin ZIP filter-a

### Popravljeno
- **Upload hook registration** - Prestavljen iz file-load time v `plugins_loaded` action
  - Prej: Hook registriran prekmalu, settings morda niso dostopni
  - Zdaj: Hook registriran med proper WordPress initialization
- **Plugin ZIP filter** - Dodana detekcija `/plugins/` path v GUID
  - Prej: Plugin ZIP files se pojavljali v Media Library
  - Zdaj: Plugin uploads se ignorirajo (ne media uploads)

### Tehnično
- Nova funkcija `arnes_s3_register_upload_hook()` registrirana na `plugins_loaded`
- Odstranjen ZIP extension check (line 43-47), dodan GUID path check
- Uploads v S3 zdaj delujejo zanesljivo
