# Praktična Simulacija Sistema (Docker + PHP + MySQL)

Ovaj projekat predstavlja kompletnu simulaciju softverskog sistema za praćenje štampe deklaracija u proizvodnji. Arhitektura se oslanja na **Docker Compose** platformu i implementira simulaciju lokalne mreže (LAN) i Firewall bezbednosnog prolaza (Nginx).

---

## 1. Arhitektura Aplikacije i Baza Podataka

Sistem je podeljen na više "kontejnera" koji rade zajedno.

###  Uloge i Aplikativna Logika (PHP)
Aplikacija je napisana u čistom PHP-u i koristi PDO za bezbednu konekciju sa bazom podataka.
*   **Radnik (Worker):** Ulogom `worker` radnik pristupa `dashboard.php` gde bira proizvod i štampa deklaraciju. Aplikacija proverava i skida stanje na zalihama i evidentira štampu.
*   **Šef (Admin):** Ulogom `admin` menadžer pristupa specijalnom novom panelu (`admin.php`) gde ima **CRUD** mogućnosti: dodavanje, brisanje i ažuriranje količina proizvoda. Takođe on može pratiti agregirane statistike.

### Baza Podataka (MySQL)
Šema baze se automatski kreira (`init.sql`) prilikom prvog pokretanja. Pored osnovnih `users` i `products` tabela, postoje i:

1.  **`print_logs`** - Tabela koja beleži **svaku aktivnost**: koji korisnik, kad i koju količinu je odlučio da odštampa. Služi za reviziju (Audit log).
2.  **`printed_quantities`** (*NOVA*) - Tabela namenjena za ubrzanu **agregaciju**. Pri svakoj štampi aplikacija ažurira `total_quantity` (+N) što omogućava brzo vizualizovanje ukupno štampane količine bez potrebe za teškim zbirnim upitima nad logom.

---

##  2. LAN, Firewall i Access Control


### Šta je LAN (Local Area Network) ovde?
U našem `docker-compose.yml` definisana je **`lan_network`** (Bridge Network). 
*   Povezanost uređaja (`sim_app`, `sim_db`, `sim_firewall`) dešava se standsardnim virtuelnim LAN-om unutar računara.
*   Uloga LAN-a je da stvori "poverljivu zonu". `sim_db` nema nikakvih poveznica van mreže (nema otvorenih portova prema ruteru ili Internetu). Samo `sim_app` i `sim_firewall` koji dele `lan_network` sa njom mogu da komuniciraju sa bazom podataka na portu `3306`. Zbog LAN arhitekture smo zaštitili bazu.

### Šta je Nginx Firewall i kako se primenjuje?
**Firewall (sim_firewall)** u arhitekturi postavljen je kao jedina tačka kontakta (Reverse Proxy na portu `8080`) sa spoljnim svetom. 
Aplikacija `sim_app` više ne odgovara uređajima tvoje kućne mreže direktno, nego je skrivena. Njena uloga je **Access Control**. 

*   **Pravila (Access Control):** Pomoću fajla `nginx.conf` sistem proverava IP adresu onog ko pokušava da uđe na port `8080` (np. tvoj telefon nasuprot tvojih gostiju).
*   Koristeći instrukcije `allow IP;` i `deny all;`, Firewall efikasno dropuje TCP mrežni saobraćaj svima koji nisu "Whitelisted".

---

## 3. Vodič za rad sa Bazom Podataka i SQL Upiti

Tabela se lako održava, ali se podaci najviše dobavljaju kroz SQL upite.

### Kako izvršavati upite nad bazom?
1.  **Kroz Terminal (Ugrađeno):**
    Pokrene se komanda:
    docker exec -it sim_db mysql -u worker_app -pworker_password simulacija

2.  **Koristeći Vizuelne Alate (DBeaver / MySQL Workbench):**
    U `docker-compose.yml` fajlu možeš dodati ispod `db` sekcije privremeni port (`ports: - "3306:3306"`) i onda se nakačiti alatom na `localhost:3306` sa nalogom `worker_app` i šifrom `worker_password` za udobnije kucanje.

###  Najkorisniji SQL Upiti (Izveštaji)

Opšte je poznato da baze brzo rastu i da su ovo query-ji za direktorske izveštaje:

**1. Saznaj zbirnu odštampanu količinu za svaki proizvod (Korišćenje nove tabele)**
```sql
SELECT p.name AS 'Proizvod', pq.total_quantity AS 'Ukupno komada', pq.last_updated AS 'Zadnja Akcija'
FROM printed_quantities pq
JOIN products p ON pq.product_id = p.id
ORDER BY pq.total_quantity DESC;
```

**2. Izveštaj aktivnosti po svakom Radniku (Ko štampa najviše komada ukupno)**
```sql
SELECT u.username AS 'Radnik', SUM(pl.quantity) AS 'Ukupno kreiranih deklaracija'
FROM print_logs pl
JOIN users u ON pl.user_id = u.id
GROUP BY u.username
ORDER BY `Ukupno kreiranih deklaracija` DESC;
```

**3. Hronološki revizijski log današnjeg poslovanja**
```sql
SELECT pl.print_time AS 'Vreme', u.username AS 'Radnik', p.name AS 'Proizvod', pl.quantity AS 'Količina'
FROM print_logs pl
JOIN users u ON pl.user_id = u.id
JOIN products p ON pl.product_id = p.id
ORDER BY pl.print_time DESC
LIMIT 50;
```

**4. Koji artikli hitno trebaju nabavku zaliha? (Ispod 200 komada)**
```sql
SELECT name, available_stock 
FROM products 
WHERE available_stock < 200 
ORDER BY available_stock ASC;
```

