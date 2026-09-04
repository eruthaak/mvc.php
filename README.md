# PHP MVC Framework

A lightweight, dependency-free PHP MVC framework built around a **front controller**, **segment-based routing**, **procedural controllers**, **plain-PHP views**, and a **PDO-based database query builder**. It also ships with helper functions, an AES encryption utility, a cURL HTTP client, a WebSocket client, and a standalone WebSocket server.

Created by **Beresyus**.

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Directory Structure](#directory-structure)
- [Request Lifecycle](#request-lifecycle)
- [Routing](#routing)
- [Controllers](#controllers)
- [Views & Components](#views--components)
- [Helper Functions](#helper-functions)
- [Database Layer](#database-layer)
- [Other Classes](#other-classes)
- [WebSocket Server](#websocket-server)
- [Configuration Reference](#configuration-reference)
- [Security Features](#security-features)
- [Known Issues & Limitations](#known-issues--limitations)
- [Extending the Framework](#extending-the-framework)

---

## Overview

This is a **"no-frills" MVC** implementation. There is no routing table, no controller classes, no template engine, and no ORM — instead:

| Layer | Implementation |
|---|---|
| **Model** | The `Database` class (a PDO wrapper with a small query builder) |
| **View** | Plain PHP template files under `app/view/`, with reusable `components/` partials |
| **Controller** | Simple procedural PHP files under `app/controller/`, one file per route segment |

Everything is wired together by two files: **`index.php`** (the front controller) and **`app/init.php`** (the bootstrap). Helpers are loaded automatically as global functions, and classes are autoloaded from `app/classes/`.

---

## Features

- **Segment-based routing** — `example.com/controller/arg1/arg2/...`
- **Front controller pattern** — all requests funnel through `index.php`
- **Autoloading** for classes (`app/classes/class.<name>.php`)
- **Automatic helper loading** — every file in `app/helper/` is loaded as global functions
- **PDO database wrapper** with `select`, `insert`, `update`, `delete`, and `raw` methods plus a small array-based query builder
- **CSRF protection** — token generated per request and injected into forms
- **Input filtering** — `get()` / `post()` sanitize HTML special characters
- **AES-128-CBC encryption** helpers (`encrypt()` / `decrypt()`)
- **cURL HTTP client** (`HTTP::GET` / `HTTP::POST`)
- **WebSocket client** class plus a **standalone WebSocket server** script
- **HTTP error handling** with HTTP status codes (via `abort()`)
- **Turkish-aware URL slugging** (`encode()`)
- **Optional API / RSS / SSE endpoints** (ready but disabled by default)

---

## Requirements

- PHP **8.0+** (uses union return types such as `int|array`, constructor property features of PDO, etc.)
- A web server with **mod_rewrite** (Apache) or equivalent URL rewriting
- **PDO** with the MySQL driver, if you use the database layer
- `ext-openssl` for encryption helpers
- `ext-curl` for the `HTTP` class
- `ext-sockets` for the WebSocket server

> **Note:** No `composer.json` is present — the framework is plain PHP with zero external dependencies.

---

## Installation

1. **Place the project** in your web server's document root (or a virtual host root).

2. **Configure the application** — edit `app/system/config.php`:

   ```php
   const APP_URL = 'https://project.test';   // must match your domain
   const DB_NAME = 'test';
   const DB_USERNAME = 'root';
   const DB_PASSWORD = NULL;                  // NULL => no password
   ```

3. **Make sure URL rewriting works**. The included `.htaccess` handles Apache:

   ```
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^([0-9a-zA-Z-_/]+)$ index.php?0=$1 [QSA,L]
   RedirectMatch 403 /app/.*$
   RedirectMatch 403 /uploads/.*$
   ```

   All paths are rewritten to `index.php?0=<path>`, while direct access to `app/` and `uploads/` is forbidden.

4. **Create the database** referenced in the config (or skip DB usage — the connection is created at bootstrap, so a database must exist for the app to boot).

5. **Start the WebSocket server** (optional) from the CLI:

   ```bash
   php app/server/websocket.php
   ```

6. **Visit your site** — the default route (`index`) should render.

---

## Directory Structure

```
mvc.php-main/
├── index.php                     # Front controller (entry point)
├── .htaccess                     # URL rewriting + directory protection
├── robots.txt                    # Search-engine rules
├── sitemap.xml                   # Sitemap
├── favicon.ico
└── app/
    ├── init.php                  # Bootstrap: autoloader, helpers, config, DB
    ├── system/
    │   └── config.php            # All application constants
    ├── controller/               # Procedural controllers (one file per route)
    │   ├── index.php             #   Default controller (route 0)
    │   ├── api.php               #   JSON endpoint (disabled by default)
    │   ├── rss.php               #   RSS endpoint (disabled by default)
    │   └── sse.php               #   Server-Sent Events endpoint (disabled by default)
    ├── view/
    │   ├── index.php             # Example view
    │   └── components/
    │       ├── header.php        # Shared HTML header partial
    │       └── footer.php        # Shared HTML footer partial
    ├── helper/                   # Global functions, auto-loaded
    │   ├── url.php               #   route(), encode()
    │   ├── system.php            #   controller(), view(), img(), site(), asset()...
    │   ├── request.php           #   get(), post(), request(), csrf(), method()...
    │   ├── response.php          #   redirect(), redirectAfter(), abort()
    │   ├── session.php           #   session(), sessionKill()
    │   ├── server.php            #   server()
    │   ├── files.php             #   files(), extension()
    │   ├── data.php              #   encrypt(), decrypt()
    │   ├── lang.php              #   __()
    │   ├── assets.php            #   svg()
    │   └── event.php             #   (placeholder for SSE event handlers)
    ├── classes/                  # Autoloaded classes (class.<name>.php)
    │   ├── class.Helper.php          # Loads all helper files
    │   ├── class.Database.php        # PDO wrapper + query builder
    │   ├── class.HTTP.php            # cURL client
    │   ├── class.WebSocket_Client.php# WebSocket client
    │   ├── interface.BasicQueries.php       # select/insert/update/delete contract
    │   ├── interface.AlterQueries.php       # (empty placeholder)
    │   └── interface.BroadcastListeners.php # WebSocket listener contract
    └── server/
        └── websocket.php         # Standalone WebSocket server (CLI)
```

---

## Request Lifecycle

When a request arrives, this is the exact flow:

```
Browser
   │  GET /about/team/1
   ▼
.htaccess  ──►  rewrites to  index.php?0=about/team/1
   ▼
index.php (front controller)
   │  1. require 'app/init.php'          ← bootstrap
   │  2. $_ROUTE = explode('/', get(0))  ← ["about", "team", "1"]
   │  3. session_start() + $csrfToken    ← CSRF token generated
   │  4. route(0) === false → default to 'index'
   │  5. controller file exists?  if not → abort(404)
   │  6. require controller file         ← controller executes
   │  7. session('csrfToken', $csrfToken)
   ▼
app/init.php (bootstrap)
   │  1. spl_autoload_register()  → loads app/classes/class.<Name>.php
   │  2. Helper::Load()           → requires every file in app/helper/
   │  3. require 'system/config.php'     ← constants defined
   │  4. $db = new Database(...)         ← PDO connection
   ▼
Controller file (app/controller/<segment>.php)
   │  performs logic, may call $db, helpers, etc.
   ▼
View file (app/view/<name>.php)  ← usually required from the controller
   │  renders HTML, uses helpers like csrf(), __(), etc.
   ▼
Response sent to the browser
```

---

## Routing

Routing is **segment-based** and extremely simple.

1. `.htaccess` turns the request path into a `0` query parameter.
2. `index.php` splits it on `/` and stores it in the global `$_ROUTE` array.
3. The **first segment** is the controller name.

| URL | `$_ROUTE` | Controller loaded |
|---|---|---|
| `/` | `["index"]` (defaulted) | `app/controller/index.php` |
| `/about/team/1` | `["about", "team", "1"]` | `app/controller/about.php` |
| `/api` | `["api"]` | `app/controller/api.php` |

### `route()` helper

```php
route($index, ?string $url = null)
```

Reads (or writes) a route segment:

```php
$controller = route(0);       // "about"
$arg1       = route(1);       // "team"
$arg2       = route(2);       // "1"
route(0, 'index');            // overwrite segment 0
```

If the requested controller file does not exist, the app calls `abort(404)`.

### Activating special endpoints

`api`, `rss`, and `sse` controllers exist but are **disabled** by default. To enable them, uncomment the corresponding lines in `index.php`:

```php
// if( route(0) === 'rss' ) require controller('rss');
// if( route(0) === 'api' ) require controller('api');
// if( route(0) === 'sse' ) require controller('sse');
```

---

## Controllers

Controllers are **plain PHP files** under `app/controller/`, named after the route segment. They are `require`d directly, so they run in the global scope and can use every helper function and the `$db` object.

Example — `app/controller/index.php`:

```php
<?php
if (method() == 'POST') {
  $websocket_client = new WebSocket_Client('192.168.1.191', 9000);
  $websocket_client->send(json_encode(['data' => 'Hi, I\'m Yusuf!']));
}
require view('index');   // render the view
```

The example controller sends a message to a WebSocket server on POST and then renders the `index` view.

> **Convention:** a controller usually ends by `require view('<name>');`. Because views are included in the global scope, variables set in the controller are visible in the view.

---

## Views & Components

Views are **plain PHP template files** under `app/view/`. You reference them with the `view()` helper:

```php
require view('index');               // app/view/index.php
require view('blog/detail');         // app/view/blog/detail.php
```

Shared markup lives in `app/view/components/` and can be included from any view:

```php
<?php $title = 'Main Page'; require 'components/header.php'; ?>
  ... page content ...
<?php require 'components/footer.php'; ?>
```

The header component reads `$title`, `APP_NAME`, and `$csrfToken`:

```php
<title><?= isset($title) ? APP_NAME . " | " . $title : APP_NAME; ?></title>
<meta name="csrfToken" content="<?=$csrfToken?>">
```

Views can freely use helpers, e.g. `csrf()`, `method()`, `__()`, `site()`, `asset()`.

---

## Helper Functions

Helpers are **global functions** loaded automatically by `Helper::Load()` from `app/helper/`. Adding a new file there makes its functions available app-wide.

### Routing & URLs

| Function | Description |
|---|---|
| `route($index, $url = null)` | Read or write a route segment |
| `encode($title)` | Convert a string into a URL slug (Turkish-aware) |
| `controller($name)` | Absolute path of a controller file |
| `view($name)` | Absolute path of a view file |
| `img($name)` | Path to an image under `ASSETS` |
| `uploads($name)` | Path to a file under `UPLOADS` |
| `site($url = null)` | Absolute URL: `APP_URL . '/' . $url` |
| `asset($url = null)` | Absolute asset URL: `APP_URL . '/assets/' . $url` |

### Request & Input

| Function | Description |
|---|---|
| `get($name)` | Sanitized `$_GET` value (handles arrays too) |
| `post($name)` | Sanitized `$_POST` value (handles arrays too) |
| `request($name)` | **Raw** value (no sanitization) from POST then GET |
| `requestHeaders($name)` | Read an HTTP request header |
| `filterUrl($str)` | `htmlspecialchars(trim($str))` |
| `csrf()` | Renders a hidden CSRF input field |
| `method($method = null)` | With argument: renders `_method` hidden input; without: returns the effective request method |

### Session & Server

| Function | Description |
|---|---|
| `session($key, $value = null)` | Get or set a session value |
| `sessionKill($key)` | Remove a session value |
| `server($key, $value = null)` | Get or set a `$_SERVER` value (key is uppercased) |

### Responses

| Function | Description |
|---|---|
| `redirect($href)` | Send a `Location` header |
| `redirectAfter($time, $href)` | Redirect after N seconds (`Refresh` header) |
| `abort($code)` | Send an HTTP status code and halt |

Supported status codes in `abort()`: `400`, `401`, `403`, `404`, `410`, `411`, `429`.

### Security & Data

| Function | Description |
|---|---|
| `encrypt($data)` | AES-128-CBC encrypt (Base64 output) |
| `decrypt($hash)` | AES-128-CBC decrypt |

### Internationalization

| Function | Description |
|---|---|
| `__($langCode)` | Translate a key using a global `$lang` array (intended to come from a language file) |

### File Uploads & Assets

| Function | Description |
|---|---|
| `files($name)` | Get an uploaded file from `$_FILES` (as object if array) |
| `extension($name)` | File extension via `pathinfo()` |
| `svg($name, $height, $width, $x, $y)` | Read an SVG file from `ASSETS/svg/` and inject attributes |

---

## Database Layer

`app/classes/class.Database.php` extends **PDO** and implements the `BasicQueries` interface. A ready-to-use instance is created at bootstrap:

```php
$db = new Database(DB_CREDENTIALS, DB_USERNAME, DB_PASSWORD);
```

### Methods

| Method | Returns |
|---|---|
| `select($table, $columns = [], $options = null)` | `array` — single associative array when one row, otherwise a list |
| `insert($table, $datas)` | `int` last insert id, or `array` error info |
| `update($table, $columns, $options = null)` | `int` affected row count |
| `delete($table, $options = null)` | `int` affected row count |
| `raw($query)` | `array` for SELECT, `int` last id for INSERT, `int` row count for UPDATE/DELETE |
| `setQuery($query)` / `getQuery()` | Set / inspect the last built query |
| `getStatement()` | Access the underlying `PDOStatement` |

### Basic usage

```php
// Select all
$users = $db->select('users');

// Select with aliased columns
$users = $db->select('users', ['id', 'name' => 'user_name']);

// Select with conditions
$user = $db->select('users', ['id', 'email'], [
  'AND' => ['=' => ['id' => 42]],
]);

// Insert — returns last insert id
$id = $db->insert('users', ['name' => 'Yusuf', 'age' => 30]);

// Update — returns affected rows
$affected = $db->update('users', ['name' => 'Beresyus'], [
  'AND' => ['=' => ['id' => $id]],
]);

// Delete
$affected = $db->delete('users', ['OR' => ['=' => ['age' => 0]]]);

// Raw query
$result = $db->raw("SELECT * FROM users WHERE status = 'active'");
```

### Query-builder options format

The `$options` argument accepts three keys (all optional):

| Key | Format | SQL output |
|---|---|---|
| `'AND'` | `['=' => ['col' => val], '>' => ['col' => val]]` | `col = val AND col > val` |
| `'OR'` | `['=' => ['col' => val]]` | `col = val` (chained with `OR`) |
| `'RAW'` | string, e.g. `" LIMIT 5"` | appended verbatim |

String values are quoted, numeric values are not, and `NULL` becomes `NULL`.

> ⚠️ **Warning:** the `select()` method currently contains an uncommented debug statement
> (`echo $this->query; exit;` on line 72 of `class.Database.php`) that prints the SQL and
> **terminates the script**. Every call to `select()` will stop execution until this line
> is removed. See [Known Issues](#known-issues--limitations).

---

## Other Classes

All classes live in `app/classes/` and are autoloaded as `class.<lowercased-name>.php`.

### `Helper`

```php
Helper::Load();
```

Iterates `app/helper/` and `require`s every file — the mechanism behind the global helper functions.

### `HTTP`

A minimal **cURL** client:

```php
$res = HTTP::GET('https://api.example.com/v1/users');
$res = HTTP::GET('https://api.example.com/v1/users', ['page' => 2]);
$res = HTTP::POST('https://api.example.com/v1/users', ['name' => 'Yusuf']);
$res = HTTP::POST('https://api.example.com/v1/users', '{"name":"Yusuf"}'); // JSON body
```

When a string is passed as data, `Content-Type: application/json` is set automatically.

### `WebSocket_Client`

A WebSocket **client** implementing the RFC 6455 handshake and framing (masked client frames, ping/pong handling, text/binary modes):

```php
$ws = new WebSocket_Client('127.0.0.1', 9000);
$ws->send('hello', final: true, binary: false);
$response = $ws->get();
```

### Interfaces

| Interface | Contract |
|---|---|
| `BasicQueries` | `select`, `insert`, `update`, `delete` (implemented by `Database`) |
| `AlterQueries` | Placeholder — currently empty |
| `BroadcastListeners` (`MustListenTheseActions`) | `onConnect`, `onMessage`, `onClose`, `onError` — intended contract for WebSocket broadcast handlers |

---

## WebSocket Server

`app/server/websocket.php` is a **standalone socket-based WebSocket server** built on `ext-sockets`. It is meant to run from the **CLI**, not through the web server:

```bash
php app/server/websocket.php
```

Behavior:

- Listens on `WS_HOST` / `WS_PORT` (defaults `192.168.1.191:9000`)
- Accepts new connections and performs the WebSocket **handshake**
- Unmasks incoming frames (`unmask()`), re-encodes them, and **broadcasts** to all connected clients (`send()`)
- Detects disconnections and removes dead sockets
- Debug output can be enabled with `WS_DEBUG`

The bundled controller example pairs with it: submitting the form on the index page opens a `WebSocket_Client` to the server and pushes a message, which is then broadcast to every connected client.

---

## Configuration Reference

All settings are constants in **`app/system/config.php`**.

| Constant | Default | Purpose |
|---|---|---|
| `DIR` | project root | Absolute project path |
| `CONTROLLER` | `DIR/app/controller` | Controllers directory |
| `VIEW` | `DIR/app/view` | Views directory |
| `ASSETS` | `DIR/assets/img` | Images directory |
| `UPLOADS` | `DIR/uploads` | Uploads directory |
| `APP_CREATOR` | `'Beresyus'` | Shown in page metadata |
| `APP_NAME` | `''` | Site name (used in `<title>`) |
| `APP_URL` | `'https://project.test'` | Canonical site URL — **must match your domain** |
| `APP_TIMEZONE` | `'Europe/Istanbul'` | Default PHP timezone |
| `APP_DEBUG` | `true` | Debug flag |
| `ENCRYPTION_CIPHER` | `'AES-128-CBC'` | Cipher for `encrypt()` / `decrypt()` |
| `ENCRYPTION_KEY` | `'berestest.104884'` | **Replace in production!** |
| `DB_HOST` / `DB_PORT` | `localhost` / `3306` | Database host / port |
| `DB_NAME` | `test` | Database name |
| `DB_CHARSET` | `utf8` | Connection charset |
| `DB_USERNAME` | `root` | Database user |
| `DB_PASSWORD` | `NULL` | Database password |
| `DB_CREDENTIALS` | built DSN | PDO DSN string |

---

## Security Features

The framework provides a few built-in protections:

- **CSRF tokens** — `index.php` generates `$csrfToken` on every request; `csrf()` renders a hidden field; the header component also exposes it in a `<meta>` tag for JavaScript use.
- **Input sanitization** — `get()` and `post()` pass values through `htmlspecialchars()` (arrays are mapped recursively).
- **Directory protection** — `.htaccess` returns `403` for any direct access under `/app/` and `/uploads/`.
- **Encryption helpers** — AES-128-CBC via OpenSSL for sensitive payloads.

---

## Known Issues & Limitations

Since the project is intentionally minimal, several rough edges exist:

1. **`Database::select()` is broken** — it contains `echo $this->query; exit;` (line 72 of `app/classes/class.Database.php`). Every `select()` call prints the SQL and halts execution. **Remove that line** to make `select()` work.

2. **`abort()` references a missing view** — it includes `view('error')`, but no `app/view/error.php` exists, so error pages render blank (just the HTTP status header). Create `app/view/error.php` to show error content.

3. **`__()` has no language file loader** — the `$lang` global is never populated anywhere in the project, so `__()` always returns its input. A language-file mechanism under `/app/language` is described in the docblock but not implemented.

4. **`method()` does not detect real HTTP methods** — it returns the `_method` form field or defaults to `'GET'`. A genuine POST request *without* a hidden `_method=POST` field will be seen as `GET`. Always include `<?= method('POST') ?>` in forms that must be detected as POST.

5. **Database uses string interpolation, not bindings** — the query builder embeds values directly into SQL. Values are minimally handled (strings quoted, numbers passed raw), but this is not a substitute for parameterized queries. Prefer `raw()` with bound parameters for user input.

6. **CSRF token is stored in the session *after* the controller runs** — during controller/view execution the session value is not yet set (only the `$csrfToken` global exists). Verification against the session must be implemented by the caller.

7. **`APP_URL` is hardcoded** — it must match your deployment domain; otherwise `site()` and `asset()` generate wrong links.

8. **Default encryption key** is public in this repository — replace `ENCRYPTION_KEY` for anything non-toy.

9. **No model layer** — data access is centralized in `Database`, but there are no per-entity model classes; controllers talk to `$db` directly.

10. **`robots.txt` and `sitemap.xml`** contain placeholder values from a previous project (`imugenoteam`) — update them for production.

---

## Extending the Framework

Because the design is intentionally simple, adding functionality is straightforward:

- **New controller** → drop a file in `app/controller/` named after the URL segment.
- **New view** → drop a file in `app/view/` and `require view('<name>')` from a controller.
- **New helper** → drop a file in `app/helper/`; it is loaded automatically.
- **New class** → drop `class.<Name>.php` in `app/classes/`; it is autoloaded when referenced.
- **WebSocket broadcast listeners** → implement the `MustListenTheseActions` interface (`onConnect`, `onMessage`, `onClose`, `onError`).
- **API / RSS / SSE endpoints** → uncomment the routing lines in `index.php`.

---

*Documented from the current source tree. See the file headers and inline docblocks for author-level details.*