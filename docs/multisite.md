# WordPress Multisite

ModernPress supports WordPress Multisite installations. Both subdomain and subdirectory installations may be used.
For more information about WordPress Multisite, see the [WordPress Codex](https://codex.wordpress.org/Create_A_Network).

## Enabling a Subdirectory Multisite

To enable subdirectory multisite:
1. Uncomment the line: `#vhosts: dev/lando/ms-subdirectory.conf` in `.lando.local.yml` at the root of the project.
2. Uncomment or add the line `define( 'WP_ALLOW_MULTISITE', true );` in your `local-config.php` file.
3. Run `lando rebuild -y` to apply the changes.
4. Login to WordPress and navigate to **Tools > Network Setup**.
5. Follow the instructions to set up the network. Choose "Sub-directories" when prompted for the type of network.
6. Uncomment or add the following lines in your `local-config.php`:
```php
define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', false );
define( 'DOMAIN_CURRENT_SITE', '<YOUR-PROJECT-NAME>.lndo.site' );
```

## Enabling a Subdomain Multisite

To enable subdomain multisite:
1. Uncomment the `proxy` section in `.lando.local.yml` at the root of the project. be sure to udpate the domain
   to match your project.
2. Uncomment or add the line `define( 'WP_ALLOW_MULTISITE', true );` in your `local-config.php` file.
3. Run `lando rebuild -y` to apply the changes.
4. Login to WordPress and navigate to **Tools > Network Setup**.
5. Follow the instructions to set up the network. Choose "Sub-domains" when prompted for the type of network.
6. Uncomment or add the following lines in your `local-config.php`:
```php
define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', true );
define( 'DOMAIN_CURRENT_SITE', '<YOUR-PROJECT-NAME>.lndo.site' );
```
