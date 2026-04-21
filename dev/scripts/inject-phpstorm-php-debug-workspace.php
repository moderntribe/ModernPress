<?php declare(strict_types=1);
/**
 * Inject or replace the PhpStorm PhpServers block in .idea/workspace.xml.
 * Intended for Docker/Xdebug path mappings (e.g. $PROJECT_DIR$ -> /app).
 *
 * Run manually: composer run phpstorm-workspace-php-debug
 *
 * If workspace.xml already contains a different PhpServers block, asks for
 * confirmation before overwriting. Non-interactive runs must set:
 *   PHPSTORM_PHP_DEBUG_OVERWRITE=1
 */

$repo_root  = dirname( __DIR__, 2 );
$path       = $repo_root . '/.idea/workspace.xml';
$idea_dir   = $repo_root . '/.idea';

$php_servers = <<<'XML'
  <component name="PhpServers">
    <servers>
      <server host="localhost" id="b8d2165f-0d19-4d3e-a68a-35ca017df33c" name="localhost" use_path_mappings="true">
        <path_mappings>
          <mapping local-root="$PROJECT_DIR$" remote-root="/app" />
        </path_mappings>
      </server>
    </servers>
  </component>
XML;

$snippet_pattern = '/<component\b[^>]*\bname\s*=\s*"PhpServers"[^>]*>.*?<\/component>/s';

$force_raw = getenv( 'PHPSTORM_PHP_DEBUG_OVERWRITE' );
$force     = in_array(
	strtolower( trim( (string) $force_raw ) ),
	[ '1', 'true', 'yes', 'y' ],
	true
);

/**
 * @return bool True to proceed with overwrite.
 */
function confirm_overwrite( string $existing, bool $force ): bool {
	if ( $force ) {
		return true;
	}
	if ( ! stream_isatty( STDIN ) ) {
		fwrite(
			STDERR,
			"Refusing to overwrite an existing PhpServers block without a TTY.\n"
			. "Run this in an interactive terminal, or set PHPSTORM_PHP_DEBUG_OVERWRITE=1.\n"
		);
		exit( 2 );
	}

	echo "Found an existing <component name=\"PhpServers\"> block in workspace.xml.\n";
	echo "Current block:\n\n";
	$preview = trim( $existing );
	if ( strlen( $preview ) > 1200 ) {
		$preview = substr( $preview, 0, 1200 ) . "\n…";
	}
	echo $preview . "\n\n";
	echo 'Replace it with the project default PhpServers configuration? [y/N]: ';
	$answer = strtolower( trim( (string) fgets( STDIN ) ) );

	return in_array( $answer, [ 'y', 'yes' ], true );
}

if ( ! is_dir( $idea_dir ) && ! mkdir( $idea_dir, 0775, true ) && ! is_dir( $idea_dir ) ) {
	fwrite( STDERR, "Could not create directory: {$idea_dir}\n" );
	exit( 1 );
}

if ( ! is_file( $path ) ) {
	$content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
		. "<project version=\"4\">\n"
		. $php_servers . "\n"
		. "</project>\n";
	file_put_contents( $path, $content );
	echo "Created {$path}\n";
	exit( 0 );
}

$original = (string) file_get_contents( $path );

if ( preg_match( $snippet_pattern, $original, $match ) ) {
	$existing = $match[0];
	if ( trim( $existing ) === trim( $php_servers ) ) {
		echo "No changes needed (PhpServers already matches project default): {$path}\n";
		exit( 0 );
	}
	if ( ! confirm_overwrite( $existing, $force ) ) {
		echo "Skipped; workspace.xml was not modified.\n";
		exit( 0 );
	}
	$updated = (string) preg_replace( $snippet_pattern, $php_servers, $original, 1 );
} else {
	$marker = '</project>';
	$idx    = strrpos( $original, $marker );
	if ( $idx === false ) {
		fwrite( STDERR, "Could not find closing '{$marker}' in {$path}\n" );
		exit( 1 );
	}
	$before  = rtrim( substr( $original, 0, $idx ), "\n" );
	$after   = substr( $original, $idx );
	$prefix  = $before !== '' ? "\n" : '';
	$updated = $before . $prefix . $php_servers . "\n" . $after;
}

if ( $updated !== $original ) {
	file_put_contents( $path, $updated );
	echo "Updated {$path}\n";
} else {
	echo "No changes needed: {$path}\n";
}
