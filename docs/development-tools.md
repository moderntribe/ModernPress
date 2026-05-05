# Development tools

This page covers debugging PHP with **Xdebug** in the Lando environment, and how to connect **PhpStorm**, **Visual Studio Code**, or **Cursor**.

## Xdebug in this project (Lando)

The WordPress recipe keeps Xdebug **off** by default (`xdebug: false` in `.lando.yml`) so normal page loads stay fast. The appserver sets [`XDEBUG_MODE`](https://xdebug.org/docs/all_settings#mode) to `debug,develop` when the extension is loaded.

### Enable and disable

From the project root:

* **`lando xdebug-on`** — loads the Xdebug extension and reloads PHP-FPM (matches the nginx-based stack).
* **`lando xdebug-off`** — removes the Xdebug ini snippet and reloads PHP-FPM.

Run **`lando xdebug-on`** after **`lando start`** or **`lando rebuild`** whenever you want step debugging. Run **`lando xdebug-off`** when you are done to reduce overhead.

### Local URL and path mapping

Use **`lando info`** to see the URLs and ports for your app. In the container, the project is typically mounted at **`/app`**. Your IDE must map your **local project root** (the repository root) to **`/app`** on the server, or breakpoints will not bind.

### Browser trigger (optional but common)

For web requests, Xdebug usually starts only when a **trigger** is present (for example a browser extension or `XDEBUG_TRIGGER`). That avoids connecting on every request. Popular options:

* [Xdebug Helper for Chrome](https://chromewebstore.google.com/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc) (Chrome Web Store)
* [Xdebug Helper for Firefox](https://addons.mozilla.org/en-US/firefox/addon/xdebug-helper-for-firefox/) (Mozilla Add-ons)

Xdebug’s behavior is described in the official docs: [Step Debugging](https://xdebug.org/docs/step_debug) and [Triggering the start of a debug session](https://xdebug.org/docs/step_debug#triggering).

### Xdebug 3 port

Xdebug **3** uses **port 9003** by default (not 9000). Configure your IDE to listen on **9003** unless you have changed PHP’s settings.

### If the IDE never connects

1. Confirm Xdebug is on: `lando xdebug-on`.
2. Inspect settings: `lando php -i | grep -E 'xdebug\.(client_host|client_port|mode|start_with_request)'`.
3. With **`xdebug: false`**, Lando does not inject its full Xdebug ini; if `client_host` does not reach your machine, see [Lando PHP configuration](https://docs.lando.dev/plugins/php/config.html) (for example toggling recipe **`xdebug: true`** or adjusting overrides). The Lando team also publishes [Lando + PhpStorm + Xdebug](https://docs.lando.dev/guides/lando-phpstorm.html) and [Using Lando with VS Code](https://docs.lando.dev/guides/lando-with-vscode.html).

---

## PhpStorm

Official reference: [Debug PHP with PhpStorm](https://www.jetbrains.com/help/phpstorm/debugging-php.html) and [Zero-configuration debugging](https://www.jetbrains.com/help/phpstorm/zero-configuration-debugging.html).

### Install

PhpStorm includes PHP and Xdebug support; install PhpStorm from [JetBrains](https://www.jetbrains.com/phpstorm/download/). No separate Xdebug install is required on your Mac—Xdebug runs **inside** the Lando PHP container.

### Configure

1. **PHP interpreter (optional check)**  
   **Settings → PHP**. You can use a local PHP or skip remote CLI if you only debug web requests via Lando.

2. **Debug port**  
   **Settings → PHP → Debug → Xdebug**: ensure the debug port is **9003** (Xdebug 3).

3. **Listen for connections**  
   Start **Listen for PHP Debug Connections** (toolbar telephone icon, or **Run → Start Listening for PHP Debug Connections**).

4. **Server and path mappings**  
   **Settings → PHP → Servers**: add a server whose **host** and **port** match the URL you open in the browser (from `lando info`). Enable **Use path mappings** and map your project root to **`/app`**.

5. **IDE key (if you use a browser extension)**  
   **Settings → PHP → Debug → DBGp Proxy**: set the **IDE key** (for example `PHPSTORM`) and enter the same value in the browser extension so the session matches.

### Typical workflow

1. `lando start` then `lando xdebug-on`.
2. Turn on **Listen for PHP Debug Connections** in PhpStorm.
3. Set breakpoints in PHP files under your project root.
4. Enable debug in the browser extension (or otherwise send a trigger), then reload the page.

---

## Visual Studio Code

### Install

1. Install [Visual Studio Code](https://code.visualstudio.com/).
2. Install the **PHP Debug** extension by Xdebug ([Visual Studio Marketplace](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug)). Extension documentation: [vscode-php-debug](https://github.com/xdebug/vscode-php-debug).

Xdebug itself still runs only in the **Lando** container; the extension connects from VS Code to that debug session.

### Configure `launch.json`

Add a configuration that listens for Xdebug and maps paths. Example **Listen for Xdebug** setup (adjust **`pathMappings`** if your workspace root differs):

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug (Lando)",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/app": "${workspaceFolder}"
      }
    }
  ]
}
```

Official guidance for path mappings and troubleshooting: [PHP Debug on GitHub](https://github.com/xdebug/vscode-php-debug/blob/main/README.md).

Lando-specific notes and examples: [Using Lando with VS Code](https://docs.lando.dev/guides/lando-with-vscode.html).

### Typical workflow

1. `lando start` then `lando xdebug-on`.
2. In VS Code, run the **Listen for Xdebug (Lando)** (or equivalent) configuration from the Run and Debug panel.
3. Set breakpoints in your PHP sources.
4. Use a browser extension or trigger so the request starts a debug session, then reload the page.

---

## Cursor

There is no separate Xdebug stack for Cursor. Cursor is a [VS Code–compatible editor](https://cursor.com/), so you use the **same** approach as in [Visual Studio Code](#visual-studio-code): install the **PHP Debug** extension ([Open VSX listing](https://open-vsx.org/extension/xdebug/php-debug), same extension id `xdebug.php-debug`), add the same **`launch.json`** listen configuration (port **9003**, `pathMappings` from **`/app`** to **`${workspaceFolder}`**), run **`lando xdebug-on`**, start the listener, then trigger a request from the browser.

Install extensions from Cursor’s **Extensions** view (Cursor may pull from its own marketplace or Open VSX depending on version). If an extension is missing from search, see [Cursor documentation](https://docs.cursor.com/) (including migration / troubleshooting for VS Code parity).

---

## Further reading

* [Xdebug documentation](https://xdebug.org/docs/)
* [Lando documentation](https://docs.lando.dev/)
* [Lando PHP plugin — configuration](https://docs.lando.dev/plugins/php/config.html)
* [Lando + PhpStorm + Xdebug](https://docs.lando.dev/guides/lando-phpstorm.html)
* [Using Lando with VS Code](https://docs.lando.dev/guides/lando-with-vscode.html)
