# HotDesk - Office Space Management System


HotDesk to bezpieczna, wielowarstwowa aplikacja internetowa do zarządzania i rezerwacji przestrzeni biurowej ("Hot Desking"). System został zaprojektowany z naciskiem na integralność danych, bezpieczeństwo (Security Bingo) oraz czystą architekturę.

Projekt zrealizowany w ramach przedmiotu *Wstęp do Projektowania Aplikacji Internetowych*.

---

## Moduły Aplikacji

Aplikacja dzieli się na publiczny moduł autoryzacji oraz dwa główne panele dostępowe, zależne od uprawnień użytkownika (RBAC).

### Panel Pracownika (Employee View)
* **Rezerwacje (Bookings):** Przeglądanie własnej historii rezerwacji i nadchodzących rezerwacji.
* **Mapa (Map):** Interaktywny podgląd pięter z możliwością sprawdzania statusu biurek, ich zajętości oraz dokonywania nowych rezerwacji.
* **Zarządzanie kontem:** Zmiana hasła oraz wylogowanie z systemu.

### Panel Administratora (Admin View)
* **Zarządzanie Użytkownikami (Users):** Akceptacja nowo zarejestrowanych pracowników, zmiana ról (Admin/Employee), resetowanie haseł oraz usuwanie kont.
* **Dashboard Analityczny:** Statystyki popularności poszczególnych biurek i udogodnień oraz frekwencja pracowników.
* **Edytor Mapy (Editor):** Interaktywne zarządzanie stanowiskami na wybranym piętrze – dodawanie nowych biurek, przesuwanie ich na mapie, edycja parametrów, oznaczanie biurek jako wymagających naprawy, ich deaktywacja oraz całkowite usuwanie.
* **Ustawienia (Settings):** Zarządzanie słownikiem udogodnień (features) dodawanym do globalnej listy oraz dodawanie i edycja dostępnych pięter (floors).

### Strony Błędów i Bezpieczeństwa
System posiada dedykowane i zunifikowane strony błędów HTTP maskujące komunikaty serwera:
* **400 Bad Request**
* **403 Forbidden**
* **404 Not Found**
* **500 Internal Server Error** (Maskowanie stack trace w środowisku produkcyjnym)

### Widoki aplikacji
#### Logowanie
##### Desktop
![image](docs/img/login-desktop.png)
##### Mobile
<img src="docs/img/login-mobile.png" width="392">

#### Rejestracja
##### Desktop
![image](docs/img/register-desktop.png)
##### Mobile
<img src="docs/img/register-mobile.png" width="392">

#### Mapa piętra
##### Desktop
![image](docs/img/map-desktop.png)
##### Mobile
<img src="docs/img/map1-mobile.png" width="392">
<img src="docs/img/map2-mobile.png" width="392">

#### Rezerwacje
##### Desktop
![image](docs/img/bookings-desktop.png)
##### Mobile
<img src="docs/img/bookings-mobile.png" width="392">

#### Dashboard
##### Desktop
![image](docs/img/dashboard-desktop.png)
##### Mobile
<img src="docs/img/dashboard-mobile.png" width="392">

#### Edytor pięter
##### Desktop
![image](docs/img/editor-desktop.png)
![image](docs/img/maintenance-desktop.png)
##### Mobile
<img src="docs/img/editor1-mobile.png" width="392">
<img src="docs/img/editor2-mobile.png" width="392">
<img src="docs/img/maintenance-mobile.png" width="392">

#### Użytkownicy
##### Desktop
![image](docs/img/users-desktop.png)
##### Mobile
<img src="docs/img/users1-mobile.png" width="392">
<img src="docs/img/users2-mobile.png" width="392">

#### Ustawienia
##### Desktop
![image](docs/img/settings-desktop.png)
##### Mobile
<img src="docs/img/settings-mobile.png" width="392">

---

## Stos Technologiczny i Architektura

Aplikacja została stworzona zgodnie z wytycznymi przedmiotu, całkowicie bez wykorzystania zewnętrznych frameworków PHP oraz bibliotek CSS (jak Bootstrap czy Tailwind). Całość opiera się na autorskich implementacjach wzorców projektowych.

* **Backend**: PHP 8+ (Vanilla), w pełni obiektowe podejście (OOP), zasady SOLID, automatycznie generowana dokumentacja PHPDoc.
* **Wzorce Projektowe**: 
  * **MVC** (Model-View-Controller) do separacji logiki od warstwy prezentacji.
  * **Repository Pattern** do odizolowanej komunikacji z bazą.
  * **DTO (Data Transfer Objects)** do transportu złożonych, łączonych wyników zapytan (np. popularność biurek).
  * **Singleton** do zarządzania współdzieloną instancją połączenia bazy danych.
* **Baza Danych**: PostgreSQL (Wyzwalacze, Funkcje PL/pgSQL, Widoki Analityczne, Transakcje).
* **Frontend**: HTML5, Vanilla CSS, JS (ES6+, Vanilla, mechanizm Fetch API dla zapytań asynchronicznych asynchronicznych API).
* **Infrastruktura**: Docker & Docker Compose.

### Diagram Warstwowy Architektury

```mermaid
graph TD
    classDef layer fill:#f8f9fa,stroke:#dee2e6,stroke-width:2px,rx:5px,ry:5px;
    classDef client fill:#e3f2fd,stroke:#90caf9,stroke-width:2px;
    classDef backend fill:#e8f5e9,stroke:#a5d6a7,stroke-width:2px;
    classDef db fill:#fff3e0,stroke:#ffcc80,stroke-width:2px;

    subgraph Warstwa Prezentacji / Frontend
        Views["Widoki (public/views)<br/><i>Pliki PHP / HTML</i>"]:::client
        Assets["Assety (public/scripts, styles)<br/><i>Vanilla JS (Fetch API), CSS</i>"]:::client
    end

    subgraph Warstwa Routing'u
        Router["Router (index.php)<br/><i>Kierowanie żądań HTTP</i>"]:::backend
    end

    subgraph Warstwa Aplikacji - Controllers
        Controllers["Kontrolery (src/controllers)<br/><i>Logika żądań, walidacja, autoryzacja</i>"]:::backend
    end

    subgraph Warstwa Domenowa i DTO
        Models["Modele (src/models)<br/><i>Encje reprezentujące struktury z bazy</i>"]:::backend
        DTOs["DTO (src/dto)<br/><i>Złożone obiekty do transferu danych</i>"]:::backend
    end

    subgraph Warstwa Dostępu do Danych - Repositories
        Repositories["Repozytoria (src/repositories)<br/><i>Bezpośrednia komunikacja z PDO, SQL</i>"]:::backend
    end

    subgraph Baza Danych
        DB[("PostgreSQL<br/><i>Widoki, Triggery, Funkcje</i>")]:::db
    end

    %% Przepływ
    Views -- "Żądania HTTP / Fetch API" --> Router
    Assets -. "Obsługa interfejsu (JS/CSS)" .-> Views
    
    Router -- "Mapowanie na" --> Controllers
    Controllers -- "Zwraca wyrenderowany widok / JSON" --> Views
    
    Controllers -- "Wywołuje operacje na" --> Repositories
    
    Repositories -. "Mapuje wyniki na" .-> Models
    Repositories -. "Tworzy złożone" .-> DTOs
    Controllers -. "Operuje na" .-> Models
    Controllers -. "Zwraca" .-> DTOs
    
    Repositories <--"Zapytania (Prepared Statements)"--> DB
```

---

## Architektura Bazy Danych (ERD)

![Diagram ERD](docs/img/erd.png)

[**Zobacz interaktywny diagram**](https://dbdiagram.io/d/6a312c2f9340ecc065aa83dd)

Baza danych spełnia **3 postać normalną** (3NF) i zabezpieczona jest kaskadowym usuwaniem (`ON DELETE CASCADE`). Zawiera relacje `1:N` oraz `N:M` (np. tabela łącznikowa `desk_features`).

### Widoki, Funkcje i Wyzwalacze (Triggery)
Zgodnie z wymogami projektowymi zaimplementowano własne obiekty chroniące spójność wbudowane w strukturę PostgreSQL:
1. **Triggery**: `trigger_check_booking_date` weryfikujący logikę biznesową przed wstawieniem rezerwacji (blokada wstecznych rezerwacji).
2. **Funkcje PL/pgSQL**: `prevent_past_bookings()` obsługująca w/w wyzwalacz.
3. **Widoki Analityczne**: `view_desk_popularity`, `view_user_attendance`, `view_feature_popularity`.

*Pełny zrzut struktury bazy (DDL) znajduje się w pliku `docker/db/init/init.sql`.*

---

## Praktyki Bezpieczeństwa

System wdraża rygorystyczne zasady bezpieczeństwa aplikacji webowych, w tym:

1. **SQL Injection**: Pełne stosowanie PDO Prepared Statements we wszystkich kwerendach bazodanowych.
2. **XSS (Cross-Site Scripting)**: Escapowanie wszelkich danych z użyciem `htmlspecialchars()` przy renderowaniu widoków.
3. **CSRF (Cross-Site Request Forgery)**: Autorskie generowanie i walidacja tokenów jednorazowych we wszystkich formularzach mutujących stan (porównywane odporną na timing-attacks metodą `hash_equals()`).
4. **Ochrona Sesji**: Ciasteczka sesyjne z flagami `HttpOnly`, `Secure` i `SameSite=Strict`. Wykorzystanie `session_regenerate_id(true)` po uwierzytelnieniu chroni przed podatnością *Session Fixation*. Zrzucanie sesji do zera po wylogowaniu.
5. **Rate Limiting i Brute Force**: Limitowanie zapytań chroniące przez przeciążeniem. Po przekroczeniu progu nieudanych prób logowania konto jest czasowo blokowane. Aplikacja unika rzucania wyjątkiem przy weryfikacji haseł, aby nie powodować enumeracji i "Bcrypt DoS" (zawsze zwraca "Invalid email or password").
6. **HTTPS**: Wymuszenie komunikacji szyfrowanej przez przekierowanie 301.
7. **Bezpieczeństwo połączenia**: Pula połączeń zarządzana przez Singleton z wymuszonym odpinaniem referencji by nie dławić demona bazodanowego otwartymi rzutami połączeń.

---

## Skrypty Administracyjne (CLI)

W celu zachowania wygody deweloperskiej wprowadzono dedykowany skrypt CLI pozwalający na stworzenie pierwszego konta administratora:

**Tworzenie konta Administratora:**
Skrypt `create-admin.php` z poziomu CLI (wymaga działającego kontenera i bazy):
```bash
docker exec <nazwa_kontenera_php> php create-admin.php <email> <haslo> ["Pelne Imie"]
```
*Przykład:*
```bash
docker exec <nazwa_kontenera_php> php create-admin.php admin@hotdesk.io SuperTajneHaslo123 "Jan Kowalski"
```

---

## Instalacja i Uruchomienie

Aby uruchomić aplikację w środowisku deweloperskim:

1. Sklonuj repozytorium na dysk lokalny.
2. Konfiguracja środowiska (`.env`):
   Skopiuj plik z przykładowymi zmiennymi i dostosuj je do swojego środowiska:
   ```bash
   cp .env.example .env
   ```
   *Opis przykładowych zmiennych w pliku:*
   * `POSTGRES_DB`, `POSTGRES_USER`, `POSTGRES_PASSWORD` - dane uwierzytelniające do bazy PostgreSQL.
   * `PGADMIN_DEFAULT_EMAIL`, `PGADMIN_DEFAULT_PASSWORD` - dane logowania do interfejsu webowego pgAdmin.
   * `APP_DEBUG` - włącza (`true`) lub wyłącza (`false`) tryb deweloperski.

3. Inicjalizacja danych testowych (Opcjonalnie):
   Jeśli chcesz uruchomić system z przykładowymi użytkownikami, piętrami, biurkami oraz historią rezerwacji, przygotuj plik inicjujący (seed):
   ```bash
   cp docker/db/init/seed.sql.example docker/db/init/seed.sql
   ```
   *Skrypt zostanie automatycznie zaczytany przez kontener bazy danych przy jego pierwszym uruchomieniu.*

4. Zbuduj obrazy i uruchom kontenery używając Docker Compose:
   ```bash
   docker compose up -d
   ```
5. Aplikacja zostanie powiązana pod adresem: [http://localhost:8080](http://localhost:8080).
6. Jeśli nie korzystasz z danych testowych (`seed.sql`), wygeneruj początkowego administratora systemu za pomocą opisanego wyżej skryptu administracyjnego CLI.

*Uwaga: Nowo rejestrujący się z formularza strony użytkownicy są domyślnie tworzeni jako nieaktywni. Wymagają oni ręcznego zatwierdzenia (aktywacji) z poziomu interfejsu Administratora.*

---

## Automatyczne Testowanie Aplikacji

W środowisku deweloperskim utworzono próbne testy weryfikujące architekturę:

**1. Testy Jednostkowe (PHPUnit)**
Weryfikacja metod Modelu oraz interfejsu `JsonSerializable`.
```bash
docker exec <nazwa_kontenera_php> phpunit /app/tests/UserModelTest.php
```

**2. Testy End-to-End Routingu (Bash / Curl)**
Skrypt integracyjny sprawdzający na poziomie nagłówków HTTP odpowiednie wywoływanie kodów dla stron autoryzowanych (przekierowania 302 na chronionych stronach, 403 dla braku autoryzacji panelu administracyjnego i 200).
```bash
bash tests/integration.sh
```

---

## Scenariusz Testowy

Poniższy scenariusz pozwala przetestować kluczowe funkcjonalności i zabezpieczenia aplikacji zgodnie z wymaganiami.

### 1. Rejestracja, Logowanie i Autoryzacja
1. **Rejestracja:** Przejdź na `/register`, utwórz nowe konto.
2. **Logowanie (Inactive):** Spróbuj zalogować się na nowo utworzone konto. System powinien przekierować na stronę `/inactive` (użytkownik wymaga akceptacji).
3. **Logowanie Admina:** Zaloguj się na główne konto administratora (stworzone przez CLI).
4. **Zarządzanie (Aktywacja):** W panelu administratora przejdź do "Users", znajdź nowe konto, zmień jego status na "Active" (możesz też przypisać mu rolę Admina lub zostawić Employee).

### 2. Role i Ograniczenia dostępu
1. Zaloguj się na aktywne konto z rolą **Employee**.
2. **Test 403 (Forbidden):** Jako pracownik spróbuj ręcznie wpisać w pasku adresu URL: `/dashboard` lub `/editor`. Powinien wyświetlić się zunifikowany ekran błędu **403 Forbidden**.
3. **Test wylogowania:** Wyloguj się z aplikacji (zakładka profilu -> Logout).
4. **Test 401/Przekierowania:** Będąc wylogowanym spróbuj wejść na `/map` lub `/bookings`. System przekieruje Cię na ekran logowania.

### 3. Zarządzanie Zasobami
Zaloguj się na konto **Admin**:
1. **Dodawanie piętra (Create):** Przejdź do Settings -> Add Floor. Wypełnij dane i wgraj plik ze zdjęciem mapy piętra.
2. **Edycja udogodnień:** W Settings dodaj nowe udogodnienie (np. "Dwa Monitory", wybierając ikonę).
3. **Edytor Mapy (Create/Update):** Przejdź do Editor. Wybierz nowo utworzone piętro. Kliknij na mapie, aby dodać biurko. Wpisz identyfikator (np. "A-12"), dodaj stworzone udogodnienie. Biurko pojawi się na mapie. Przesuń je w inne miejsce.
4. **Maintenance / Deaktywacja (Update):** W Edytorze kliknij przycisk, aby wyłączyć je z użytku (Set Maintenance).
5. **Usuwanie (Delete):** Dodaj testowe biurko, a następnie użyj przycisku (Hard Delete), aby je trwale usunąć.

### 4. Proces Rezerwacji
Zaloguj się na konto **Employee**:
1. Przejdź na widok `/map`. Wybierz dzisiejszą datę.
2. Zlokalizuj dostępne biurko i kliknij na nie, a następnie "Confirm Booking".
3. **Blokada wyzwalacza (Database Trigger):** Zmień datę w kalendarzu na **datę wczorajszą** i spróbuj dokonać rezerwacji. System (i wyzwalacz `trigger_check_booking_date`) odrzuci to zapytanie, wyświetlając komunikat, że nie można rezerwować biurka w przeszłości.
4. **Zarządzanie Rezerwacją:** Przejdź do `/bookings`. Twoja dzisiejsza rezerwacja będzie w zakładce "Upcoming".
5. **Check-in:** Ponieważ rezerwacja jest na dzisiaj, kliknij "Check-in". Status zmieni się na "Checked In".
6. **Odwołanie:** Zrób rezerwację na dzień jutrzejszy i użyj przycisku "Cancel". Przejdzie do zakładki "Cancelled".

---

## Checklista Wymagań

- [x] **Aplikacja napisana obiektowo** zgodnie z zasadami SOLID i MVC (brak frameworka).
- [x] **Relacyjna Baza Danych (PostgreSQL)** z relacjami 1:N i N:M, spełniająca 3 postać normalną.
- [x] **Minimum 2 widoki bazodanowe** (złączenia tabel): Utworzono np. `view_desk_popularity`, `view_user_attendance`.
- [x] **Minimum 1 wyzwalacz (Trigger) i 1 funkcja**: Utworzono `trigger_check_booking_date` chroniący przed rezerwacjami wstecznymi.
- [x] **Transakcje i blokady**: Używane transakcje bazodanowe.
- [x] **Zabezpieczenia webowe**: Zabezpieczenia przed SQLi (Prepared Statements), XSS (htmlspecialchars), CSRF (tokeny hash_equals), bezpieczne sesje.
- [x] **Dokumentacja (Readme):** Diagram ERD, link do źródła, screeny (Web i Mobile), architektura (diagram warstwowy), instrukcja uruchomienia.
- [x] **Scenariusz testowy:** Powyższy przewodnik krok po kroku.
- [x] **Automatyczne testowanie:** Utworzono test PHPUnit oraz skrypt w bashu (test end-to-end endpointów HTTP).
- [x] **Strony błędów:** Obsługa błędów 400, 403, 404, 500.
- [x] **Design:** Responsywność, estetyka, RWD (Media Queries), brak gotowych szablonów CSS.