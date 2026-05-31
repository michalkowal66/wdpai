# HotDesk - Office Space Management System


HotDesk to bezpieczna, wielowarstwowa aplikacja internetowa do zarządzania i rezerwacji przestrzeni biurowej ("Hot Desking"). System został zaprojektowany z naciskiem na integralność danych, bezpieczeństwo (Security Bingo) oraz czystą architekturę.

Projekt zrealizowany w ramach przedmiotu *Wstęp do Projektowania Aplikacji Internetowych*.

---

## Prezentacja Interfejsu

### Panel Pracownika (Employee View)
TODO

### Panel Administratora (Admin View)
TODO

### Strony Błędów i Bezpieczeństwa
TODO

---

## Stos Technologiczny i Architektura

Aplikacja nie korzysta z gotowych frameworków PHP ani bibliotek CSS typu Bootstrap/Tailwind. Całość opiera się na autorskich implementacjach wzorców projektowych.

* **Backend**: PHP 8+ (Vanilla), obiektowe podejście (OOP), SOLID.
* **Wzorce Projektowe**: 
  * **MVC** (Model-View-Controller) do separacji logiki.
  * **Repository Pattern** do komunikacji z bazą.
  * **DTO (Data Transfer Objects)** dla złożonych relacji wielotabelowych.
  * **Singleton** do zarządzania instancją bazy i kluczowymi repozytoriami.
* **Baza Danych**: PostgreSQL (Wyzwalacze, Funkcje PL/pgSQL, Widoki Analityczne, Transakcje).
* **Frontend**: HTML5, Vanilla CSS (BEM), JS (ES6+, Vanilla, Fetch API).
* **Infrastruktura**: Docker & Docker Compose.

---

## Architektura Bazy Danych (ERD)

![Diagram ERD](docs/img/erd.png)

[**Zobacz interaktywny diagram**](https://dbdiagram.io/d/6a312c2f9340ecc065aa83dd)

Baza danych spełnia **3 postać normalną** (3NF) i zabezpieczona jest kaskadowym usuwaniem (`ON DELETE CASCADE`). Zawiera relacje `1:N` oraz `N:M` (np. tabela łącznikowa `desk_features`).

### Widoki, Funkcje i Wyzwalacze (Trigger)
Zgodnie z wymaganiami projektu zaimplementowano obiekty chroniące integralność bazy:
1. **Triggery**: `trigger_check_booking_date` chroniący przed wstawieniem rezerwacji z datą w przeszłości.
2. **Funkcje PL/pgSQL**: `prevent_past_bookings()` obsługująca w/w wyzwalacz.
3. **Widoki Analityczne**: `view_desk_popularity`, `view_user_attendance`, `view_feature_popularity`.

*Pełny zrzut struktury bazy (DDL) znajduje się w pliku `docker/db/init/init.sql`.*

---

## Praktyki Bezpieczeństwa

System wdraża rygorystyczne zasady bezpieczeństwa aplikacji webowych:

1. **SQL Injection**: Globalne stosowanie PDO Prepared Statements we wszystkich kwerendach bazy danych.
2. **XSS (Cross-Site Scripting)**: Escapowanie wszelkich danych z użyciem `htmlspecialchars()` w widokach.
3. **CSRF (Cross-Site Request Forgery)**: Autorskie generowanie i ścisła walidacja tokenów jednorazowych (metoda `hash_equals()`) w formularzach autoryzacyjnych POST.
4. **Ochrona Sesji**: Flagi `HttpOnly`, `Secure` i `SameSite=Strict`. Regeneracja ID sesji po zalogowaniu przeciwko atakom *Session Fixation*.
5. **Rate Limiting i Ochrona Logowania**: Limitowanie prób logowania za pomocą zapytań do dedykowanej tabeli bazy danych (`HTTP 429 Too Many Requests` po 5 błędnych logowaniach z danego IP w ciągu 15 min). Zrzut nieudanych logowań do pliku `error_log`. Limit `strlen()` chroniący przed "Bcrypt DoS". Brak enumeracji użytkowników (zawsze zwracane generyczne "Invalid email or password").
6. **HTTPS**: Wymuszenie protokołu HTTPS (301 Redirect) na poziomie konstruktora aplikacji.
7. **Obsługa Błędów**: Globalny `set_exception_handler` maskujący surowe logi (stack trace) w produkcji przykryty stroną `500.html`.

---

## Instalacja i Uruchomienie

Aby uruchomić aplikację w środowisku developerskim:

1. Sklonuj repozytorium na dysk lokalny.
2. Utwórz plik `.env` na podstawie istniejącego pliku przykładowego lub skopiuj plik z przykładowymi zmiennymi środowiskowymi - `cp .env.example .env`.
3. Zbuduj i uruchom kontenery używając Docker Compose:
   ```bash
   docker compose up -d
   ```
4. Aplikacja będzie dostępna pod adresem: [http://localhost:8080](http://localhost:8080).

**Konta Testowe (po załadowaniu ewentualnych danych testowych):**
* **Admin**: `admin@hotdesk.io` / Hasło: `admin123`
* Nowych użytkowników można zarejestrować, ale wymagają ręcznej aktywacji w panelu przez Administratora.

---

## Uruchamianie Testów Automatycznych

W środowisku deweloperskim skonfigurowano przykłady dwóch rodzajów testów potwierdzających niezawodność architektury:

**1. Testy Jednostkowe (PHPUnit)**
Testują instancjonowanie Obiektów Domenowych (Modeli) oraz poprawne działanie metody wbudowanej `JsonSerializable` na potrzeby API.
```bash
docker exec wdpai-php-1 phpunit /app/tests/UserModelTest.php
```

**2. Testy Integracyjne End-to-End (Bash / Curl)**
Prosty skrypt `bash` badający bezpośrednio port HTTP aplikacji w celu weryfikacji poprawnego generowania kodów stanu przez routing (200, 302, 404).
```bash
bash tests/integration.sh
```
