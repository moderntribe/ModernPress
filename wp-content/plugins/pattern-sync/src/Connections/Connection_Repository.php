<?php declare(strict_types=1);

namespace PatternSync\Connections;

class Connection_Repository {

	private const OPTION = 'pattern_sync_connections';

	private const CIPHER = 'aes-256-gcm';

	/**
	 * @return array<int, array{id: string, name: string, url: string, username: string, app_password_encrypted: string}>
	 */
	public function get_all(): array {
		$value = get_option( self::OPTION, [] );
		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values( $value );
	}

	/**
	 * @return array{id: string, name: string, url: string, username: string, app_password: string}|null
	 */
	public function get_by_id( string $id ): ?array {
		$all = $this->get_all_indexed();
		$raw = $all[ $id ] ?? null;
		if ( $raw === null ) {
			return null;
		}
		$decrypted = $this->decrypt( $raw['app_password_encrypted'] ?? '' );

		return [
			'id'           => $raw['id'],
			'name'         => $raw['name'],
			'url'          => $raw['url'],
			'username'     => $raw['username'],
			'app_password' => $decrypted,
		];
	}

	/**
	 * Get connection by id without decrypting password (for display).
	 *
	 * @return array{id: string, name: string, url: string, username: string}|null
	 */
	public function get_by_id_metadata_only( string $id ): ?array {
		$all = $this->get_all_indexed();
		$raw = $all[ $id ] ?? null;
		if ( $raw === null ) {
			return null;
		}

		return [
			'id'       => $raw['id'],
			'name'     => $raw['name'],
			'url'      => $raw['url'],
			'username' => $raw['username'],
		];
	}

	/**
	 * @param array{name: string, url: string, username: string, app_password: string} $connection
	 */
	public function save( array $connection ): string {
		$id         = $connection['id'] ?? $this->generate_id();
		$encrypted  = $this->encrypt( $connection['app_password'] ?? '' );
		$all        = $this->get_all_indexed();
		$all[ $id ] = [
			'id'                     => $id,
			'name'                   => $connection['name'],
			'url'                    => untrailingslashit( $connection['url'] ),
			'username'               => $connection['username'],
			'app_password_encrypted' => $encrypted,
		];
		update_option( self::OPTION, $all, true );

		return $id;
	}

	public function delete( string $id ): bool {
		$all = $this->get_all_indexed();
		if ( ! isset( $all[ $id ] ) ) {
			return false;
		}
		unset( $all[ $id ] );
		update_option( self::OPTION, $all, true );

		return true;
	}

	private function get_all_indexed(): array {
		$value = get_option( self::OPTION, [] );
		if ( ! is_array( $value ) ) {
			return [];
		}
		$indexed = [];
		foreach ( $value as $conn ) {
			if ( ! isset( $conn['id'] ) ) {
				continue;
			}

			$indexed[ $conn['id'] ] = $conn;
		}

		return $indexed;
	}

	private function encrypt( string $plain ): string {
		$key = $this->get_encryption_key();
		if ( $plain === '' ) {
			return '';
		}
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return base64_encode( $plain );
		}
		$iv_len = openssl_cipher_iv_length( self::CIPHER );
		$iv     = $iv_len ? random_bytes( $iv_len ) : '';
		$tag    = '';
		$cipher = openssl_encrypt( $plain, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16 );
		if ( $cipher === false ) {
			return base64_encode( $plain );
		}

		return base64_encode( $iv . $tag . $cipher );
	}

	private function decrypt( string $encoded ): string {
		$key = $this->get_encryption_key();
		if ( $encoded === '' ) {
			return '';
		}
		$raw = base64_decode( $encoded, true );
		if ( $raw === false ) {
			return base64_decode( $encoded, true ) ?: '';
		}
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return $raw;
		}
		$iv_len = openssl_cipher_iv_length( self::CIPHER );
		if ( strlen( $raw ) < $iv_len + 16 ) {
			return $raw;
		}
		$iv   = substr( $raw, 0, $iv_len );
		$tag  = substr( $raw, $iv_len, 16 );
		$data = substr( $raw, $iv_len + 16 );
		$out  = openssl_decrypt( $data, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag );

		return $out !== false ? $out : $raw;
	}

	private function get_encryption_key(): string {
		if ( defined( 'AUTH_KEY' ) && AUTH_KEY !== '' ) {
			return hash( 'sha256', AUTH_KEY, true );
		}

		return hash( 'sha256', 'pattern-sync-fallback', true );
	}

	private function generate_id(): string {
		return 'conn_' . wp_generate_password( 12, false );
	}

}
