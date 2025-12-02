#!/usr/bin/env php
<?php
/**
 * Update version numbers in plugin files.
 *
 * Usage: php scripts/update-version.php <version>
 *
 * @package Another\Plugin\Woo_Settings_MCP
 */

declare( strict_types = 1 );

if ( $argc < 2 ) {
	echo "Usage: php scripts/update-version.php <version>\n";
	exit( 1 );
}

$new_version = $argv[1];

// Validate version format (semver).
if ( ! preg_match( '/^\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?$/', $new_version ) ) {
	echo "Error: Invalid version format. Expected semver (e.g., 1.0.0 or 1.0.0-beta.1)\n";
	exit( 1 );
}

$root_dir = dirname( __DIR__ );

/**
 * Update version in a file using regex replacement.
 *
 * @param string $file    File path.
 * @param string $pattern Regex pattern.
 * @param string $version New version.
 *
 * @return bool Success.
 */
function update_file_version( string $file, string $pattern, string $version ): bool {
	if ( ! file_exists( $file ) ) {
		echo "Warning: File not found: {$file}\n";
		return false;
	}

	$content = file_get_contents( $file );
	if ( false === $content ) {
		echo "Error: Could not read file: {$file}\n";
		return false;
	}

	$updated_content = preg_replace( $pattern, $version, $content );
	if ( null === $updated_content ) {
		echo "Error: Regex replacement failed for: {$file}\n";
		return false;
	}

	if ( $content === $updated_content ) {
		echo "Info: No changes needed in: {$file}\n";
		return true;
	}

	$result = file_put_contents( $file, $updated_content );
	if ( false === $result ) {
		echo "Error: Could not write file: {$file}\n";
		return false;
	}

	echo "Updated: {$file}\n";
	return true;
}

// Files and patterns to update.
$updates = [
	// Main plugin file - Version header.
	[
		'file'    => $root_dir . '/woo-settings-mcp.php',
		'pattern' => '/(Version:\s*)\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?/',
		'replace' => '${1}' . $new_version,
	],
	// Main plugin file - WOO_SETTINGS_MCP_VERSION constant.
	[
		'file'    => $root_dir . '/woo-settings-mcp.php',
		'pattern' => "/(define\(\s*'WOO_SETTINGS_MCP_VERSION',\s*')[^']+(')/",
		'replace' => '${1}' . $new_version . '${2}',
	],
	// composer.json - version field (if present).
	[
		'file'    => $root_dir . '/composer.json',
		'pattern' => '/("version":\s*")[^"]+(")/i',
		'replace' => '${1}' . $new_version . '${2}',
	],
];

$success = true;

foreach ( $updates as $update ) {
	$result = update_file_version(
		$update['file'],
		$update['pattern'],
		$update['replace']
	);

	if ( ! $result ) {
		$success = false;
	}
}

if ( $success ) {
	echo "\nVersion updated to {$new_version}\n";
	exit( 0 );
} else {
	echo "\nVersion update completed with warnings\n";
	exit( 0 ); // Don't fail the release for warnings.
}

