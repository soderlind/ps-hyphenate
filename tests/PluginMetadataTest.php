<?php
declare(strict_types=1);

it( 'keeps plugin header and runtime version in sync', function (): void {
	$plugin_file = file_get_contents( dirname( __DIR__ ) . '/ps-hyphenate.php' );

	expect( $plugin_file )->toMatch( '/Version:\s*1\.0\.5/' )
		->and( $plugin_file )->toContain( "define( 'PS_HYPHENATE_VERSION', '1.0.5' );" );
} );

it( 'uses the renamed GitHub repository for downloads and updates', function (): void {
	$plugin_file = file_get_contents( dirname( __DIR__ ) . '/ps-hyphenate.php' );
	$readme      = file_get_contents( dirname( __DIR__ ) . '/README.md' );

	expect( $plugin_file )->toContain( "github_url:   'https://github.com/soderlind/ps-hyphenate'" )
		->and( $readme )->toContain( 'https://github.com/soderlind/ps-hyphenate/releases/latest/download/ps-hyphenate.zip' )
		->and( $plugin_file . $readme )->not->toContain( 'ps-decompose-word' );
} );