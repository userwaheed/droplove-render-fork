# DrofLove.com — multilingual dating site

A pink/dark, VK.com-inspired dating platform for **singles (male/female), couples,
MMF and FFM** communities, with full i18n, account creation, profiles, photo
galleries, likes, mutual messaging, reporting and a moderation console.

- **Stack:** PHP 8.1+ · SQLite (PDO) · vanilla CSS/JS · no build step
- **Host:** any shared hosting with PHP + pdo_sqlite (Hostinger ✅)
- **License:** private / your use

---

## 1. Features

| Area | What's included |
|---|---|
| Landing | Hero, category cards, how-it-works, photo gallery, testimonials, safety, languages, CTA |
| Communities | `single_m`, `single_f`, `couple`, `mmf`, `ffm` — drives discover filtering |
| Accounts | Register, login, logout, password hashing (bcrypt/argon2), rate-limited login |
| Profiles | Avatar, cover, gallery, bio, interests, body type, height, visibility, activate/hide |
| Discover | Filters: category, gender, age range, location; sort: newest/online/oldest/most-liked; pagination |
| Messaging | Mutual-like gating, inbox, threaded conversations, unread counts |
| Safety | Report → admin review, BAN/UNBAN, 18+ age validation, upload re-encoding |
| Admin | Members table, reports queue, ban/unban/activate/delete/reset-password |
| i18n | 14 languages incl. RTL (Arabic, Urdu), `?lang=xx` or footer switcher |
| Responsive | Mobile-first, 980/640 breakpoints, mobile nav |

## 2. Files

```
hostinger/
├── index.php          landing
├── login.php register.php logout.php
├── dashboard.php discover.php profile.php
├── edit_profile.php messages.php
├── admin.php contact.php terms.php privacy.php
├── uploads.php        validated image server (do NOT link /uploads/ directly)
├── config.php         identity, categories, languages, limits
├── db.php             SQLite bootstrap + schema (auto-creates on first hit)
├── helpers.php        CSRF, auth, hashing, uploads, pagination, i18n
├── views/             one file per page
├── assets/css/style.css   ──── assets/js/app.js
├── assets/img/        avatar-placeholder.svg favicon.svg og-image.svg
├── data/              SQLite DB + WAL (create, chmod 775)
├── uploads/           images (create, chmod 775)
└── .htaccess          security headers, PHP hardening, caching
```

## 3. Deploy to Hostinger

1. **Upload** the whole `hostinger/` folder to `public_html/`
   (or into `public_html/vkmatch/` for a subdirectory install — the app works either way).
2. **Make folders writable** via File Manager or SSH:
   ```
   chmod -R 775 data uploads
   chown -R your_user:your_user data uploads
   ```
3. **First visit** to your domain auto-creates `data/vk_match.db`.
4. **Install SSL** → Hostinger → Security → AutoSSL.
5. **Optional — HTTPS redirect**: uncomment the `# Force HTTPS` block in `.htaccess`.
6. **Set APP_NAME / contact email** in `config.php`.
7. **Enable PHP extensions** (Hostinger has these on by default):
   `pdo_sqlite`, `gd`, `fileinfo`, `mbstring`, `openssl`.

### Create your first admin
Sign up normally, then promote yourself:

```
sqlite3 data/vk_match.db "UPDATE users SET is_admin = 1 WHERE email = 'you@example.com';"
```

If `sqlite3` isn't installed, do it via phpMyAdmin or a one-off PHP script:

```php
<?php
require 'config.php';
$pdo = new PDO('sqlite:data/vk_match.db');
$pdo->exec("UPDATE users SET is_admin = 1 WHERE email = 'you@example.com'");
echo "admin set";
```

## 4. PHP requirements

| Extension | Used for |
|---|---|
| `pdo_sqlite` | Database (required) |
| `gd` | Image re-encoding on upload |
| `fileinfo` | MIME sniffing on upload |
| `mbstring` | Unicode-safe strings |
| `openssl` | `random_bytes` / CSRF tokens |
| `sodium` (optional) | argon2id when available |

PHP **8.1+** recommended.

## 5. Security checklist (already implemented)

- ✅ `password_hash()` + auto-rehash on login
- ✅ CSRF tokens on every POST/GET-mutating form
- ✅ `htmlspecialchars()` everywhere user data is printed
- ✅ Prepared statements for all queries (no string concatenation)
- ✅ Login rate limiting (5 fails / 15 min / IP)
- ✅ Session fixation protection (`session_regenerate_id` on login)
- ✅ Uploads: MIME check **and** `getimagesize` deep check, GD re-encode, random names
- ✅ `data/` and `views/` blocked in `.htaccess`
- ✅ Sessions: `HttpOnly`, `SameSite=Lax`, `Secure` when HTTPS
- ✅ Account deletion purges files **and** cascades via FK
- ✅ Age gate (18+) enforced at the DB layer and in PHP
- ✅ 18+ Terms consent on registration

## 6. Internationalisation

Add a language in `config.php`:

```php
'ko' => ['한국어', false],   // add under LANGS
```

The footer switcher and `?lang=ko` pick it up automatically.

**String translation is wired.** All UI strings live in `lang/en.php` (the
fallback dictionary, 158 keys) and are rendered through `t('key')` from
`translations.php`, with `:placeholder` interpolation. Pages render in
English by default; to ship another language, add `lang/fr.php` returning
the same `'key' => 'string'` shape — missing keys fall back to English and
unknown languages fall back to the default. RTL (Arabic/Urdu) flips
`dir="rtl"` automatically.

## 7. Customisation

| To change | Edit |
|---|---|
| Site name / tagline | `config.php` → `APP_NAME`, `APP_TAGLINE` |
| Colour palette | `assets/css/style.css` → `:root` variables |
| Categories | `config.php` → `CATEGORIES` |
| Max avatar size | `config.php` → `MAX_AVATAR_MB` |
| Login lockout | `config.php` → `MAX_LOGIN_TRIES`, `LOCKOUT_SECONDS` |
| Gallery images | Replace files in `assets/img/` (keeps same filenames) |
| Contact email | `contact.php` → `$to` |

## 8. Verified

The SQL schema and every query were executed against a real SQLite engine
(Python `sqlite3`): **16/16 checks pass**, covering:

- Schema DDL (6 tables, 7 indexes)
- `CHECK (gender IN (...))` enforcement
- `UNIQUE COLLATE NOCASE` on email and username
- `DEFAULT (datetime('now'))` auto-stamping + column defaults
- Discover filters (category, active, banned, age `BETWEEN`)
- Like subqueries (`likes_count`, `likes_in`, `i_liked`)
- `INSERT OR IGNORE` idempotency
- Mutual-like message gate (rejects one-way, allows mutual)
- Unread counts, mark-read, and thread queries
- Login rate-limit window
- `ON DELETE CASCADE` (photos, messages, likes, reports.target)
- `ON DELETE SET NULL` (reports.reporter)
- Admin members query with like/message/report subqueries
- Age `BETWEEN date()` arithmetic

Additionally: PHP structure scan (brace/paren balance, no duplicate
function definitions), cross-file include-path resolution, CSS brace
balance, and JS syntax — all clean.

### Verification round 2 — real PHP 8.3 runtime + full E2E (this session)

PHP 8.3.35 (NTS, Windows) was installed locally and the app was executed
end-to-end against PHP's built-in web server with real HTTP sessions
(register → login → activate → discover → like → mutual-like → message →
admin ban/unban/report/resolve). **21/21 flow checks pass.**

Bugs found and fixed by actually running the code:

1. `helpers.php` — unparenthesized nested ternary = **fatal parse error**
   on PHP 8 (`compact_number()`); every page that called it 500'd.
2. `helpers.php` — `mb_struppercase()` doesn't exist → fatal in
   `initials()`; corrected to `mb_strtoupper()`.
3. `register.php` — redirected to `/activate.php` which doesn't exist
   (404 right after signup). Now goes to `/dashboard.php`.
4. `discover.php` — the `i_liked` subquery's `?` sits in the SELECT
   clause, so positional binding was off by one: `LIMIT` received the
   user id and `OFFSET` the page size → **discover always returned zero
   profiles**. Rewritten with named placeholders (`:me`, `:per`, `:off`).
5. `edit_profile.php` — called `$delete_upload()` (undefined variable
   function) → fatal error on avatar/cover/photo deletion.
6. `messages.php` — inbox grouping dropped `peer_id`/`peer_username`,
   so conversation rows rendered `with=0` links and PHP warnings; the
   unread badge was overwritten by any older read message of the same
   peer. Grouping now keeps the `peer_*` keys and ORs the unread flag;
   sidebar loop renamed so it no longer clobbers the thread `$peer`.
7. Every controller 500'd on first render (`Call to undefined function
   db()`): `db.php` was never required by the page controllers. Now
   required everywhere.
8. `index.php` — SQLite treats `""` as an identifier; the countries
   COUNT query was rewritten with escaped single quotes.
9. `profile.php` — like/activate/deactivate redirects appended a
   duplicated query string; `datetime("now")` double-quoting fixed
   (SQLite reads `""` as an identifier).
10. `login.php` — now honours `$_SESSION['redirect']` so post-login
    returns the user to the page they wanted.
11. `views/contact.php` — undefined-array-key warnings on GET (error
    guards now use `!empty()`).
12. `.htaccess` — `/uploads/` direct access is now blocked (it only
    ever blocked `views/` and `data/`).

**Note:** PHP 8.3.35 now lives in `~/php-dl/php83/` on the dev machine;
`php -l` passes on all 36 PHP files and the full flow was verified over
real HTTP (cookies, CSRF, redirects) against SQLite.
