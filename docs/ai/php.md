# PHP – Agent Reference

## Namespaces

| Namespace | Root Path |
|-----------|----------|
| `Tribe\Plugin\` | `wp-content/plugins/core/src/` |
| `Tribe\Theme\` | `wp-content/themes/core/` |

PSR-4 autoloading via Composer. File path mirrors namespace: `Tribe\Plugin\Blocks\Block_Base` → `wp-content/plugins/core/src/Blocks/Block_Base.php`.

## DI Container Pattern

The plugin uses **PHP-DI** for dependency injection. `Core.php` wires together **Definers** (bindings) and **Subscribers** (hook registrations).

### Definers

Implement `Definer_Interface`. Return an array of PHP-DI definitions binding interfaces to implementations.

```php
namespace Tribe\Plugin\MyFeature;

use Tribe\Plugin\Container\Definer_Interface;

class My_Feature_Definer implements Definer_Interface {
    public function define(): array {
        return [
            My_Service_Interface::class => \DI\autowire( My_Service::class ),
        ];
    }
}
```

Register in `Core.php` `$definers` array:
```php
My_Feature_Definer::class,
```

### Subscribers

Extend `Abstract_Subscriber`. Register WP hooks in `register()`.

```php
namespace Tribe\Plugin\MyFeature;

use Tribe\Plugin\Container\Abstract_Subscriber;

class My_Feature_Subscriber extends Abstract_Subscriber {
    public function register(): void {
        add_action( 'init', [ $this, 'my_action' ] );
    }

    public function my_action(): void {
        $service = $this->container->get( My_Service_Interface::class );
        $service->run();
    }
}
```

Register in `Core.php` `$subscribers` array:
```php
My_Feature_Subscriber::class,
```

## PHPCS Rules

- Config: `phpcs.xml.dist`
- PHP version: 8.4
- Indentation: tabs (tab-width 4)
- Scanned paths: `plugins/core`, `themes/core`, `mu-plugins`
- Excluded: `vendor/`, `tests/`, `*-config.php`

Run checks:
```bash
lando composer phpcs          # check
lando composer phpcbf         # auto-fix
```

## Coding Standards Highlights

- Strict types: `<?php declare(strict_types=1);` on every file
- Type hints on all parameters and return types
- No `global` variables — use DI container
- WordPress hooks always added in Subscriber `register()` methods
