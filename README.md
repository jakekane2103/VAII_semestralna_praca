# Kane Veritas 
**Knižný e‑shop**

Semestrálna práca (VAII) postavená na MVC frameworku Vaííčko. Aplikácia simuluje kníhkupectvo: prehliadanie katalógu kníh, detaily titulu, vyhľadávanie, wishlist, košík a vytvorenie objednávky.

---

## Obsah / funkcionalita

### 1) Verejná časť (bez prihlásenia)

- **Domovská stránka**
  - carousel s náhodne vybranými knihami (napr. *Bestsellery*, *Nové vydania*, *Nadchádzajúce vydania*)
- **Katalóg kníh**
  - výpis kníh s pagináciou
  - **vyhľadávanie** (názov / autor / názov série)
  - „klik na autora“ → vyhľadávanie len podľa autora
- **Detail knihy**
  - informácie o titule (názov, autor, popis, cena, obrázok)
  - pridanie do wishlistu / košíka
- **Košík pre hosťa (session-based)**
  - pridanie/odobratie položky
  - zmena množstva  
  - po prihlásení sa položky v košíku hosťa pridajú do db
- **Wishlist pre hosťa (session-based)**
  - pridanie/odobratie položky
  - zmena poradia (drag & drop) — poradie sa ukladá v session
  - po prihlásení sa položky vo wishliste hosťa pridajú do db

### 2) Prihlásený používateľ

- **Registrácia** (tabuľka `zakaznik`, heslo hashované pomocou `password_hash`)
- **Prihlásenie/odhlásenie** (login je riešený primárne cez modál)
- **Účet / Profil**
  - úprava osobných a doručovacích údajov (meno, priezvisko, email, adresa — krajina/mesto/PSČ/ulica/číslo)
  - možnosť zmeniť heslo
- **Košík pre prihláseného (`kosik`, `kosikKniha`)
- **Checkout a vytvorenie objednávky**
  - checkout je dostupný len prihláseným
  - vytvorenie objednávky (`objednavka`) + položky objednávky (`polozkaObjednavky`)
  - po vytvorení sa DB košík vyprázdni
- **Wishlist pre prihláseného (session + DB sync)**
  - wishlist sa drží primárne v session, ale pri prihlásení sa pridania/odobratie synchronizujú aj do DB (`wishlist`, `wishlistKniha`)

### 3) Admin panel

> Prístupný len pre prihláseného admina. 
- **Správa kníh**
  - pridanie novej knihy
  - úprava existujúcej knihy (len vyplnené polia sa aktualizujú)
  - odstránenie knihy:
    - ochrana: knihu nie je možné odstrániť, ak už bola použitá v objednávkach
    - pri odstránení sa vyčistí z košíkov a wishlistov
- **Správa sérií**
  - pridať / upraviť / odstrániť sériu
  - sériu nie je možné odstrániť, ak má priradené knihy

---

## Použité technológie

- **Backend:** PHP 8.3 (MVC architektúra)
- **Databáza:** MariaDB (PDO)
- **Frontend:** HTML/CSS + JavaScript (interakcie pre wishlist/košík/admin)

- **AI** Pri tvorbe tohoto projektu boli naplno využité nástroje AI (ChatGPT, GitHub Copilot) na generovanie časti kódu a pomoc s riešením problémov.

---

## Štruktúra projektu (stručne)

- `public/` — priečinok so súbormi ako sú obrázky, .js a css súbory...
- `App/Controllers/` — kontroléry (Home, Books, Cart, Wishlist, Auth, Account, Admin)
- `App/Models/` — modely pre DB vrstvu (Book, User, Cart, Wishlist, Series, ...)
- `App/Views/` — šablóny stránok (katalóg, detail, košík, wishlist, admin, ...)
- `Framework/` — dodaný VAII MVC framework (Router, View, DB Connection, Session, ...)
- `docker/` — Docker Compose + SQL init skripty
- `snippets/` — pomocné SQL skripty (seed/import/testovacie dáta)

---

## Spustenie projektu (Docker)

V repozitári je pripravený Docker setup v priečinku `docker/`.

---

## Autor

- Jakub Kostiviar (semestrálna práca VAII)
