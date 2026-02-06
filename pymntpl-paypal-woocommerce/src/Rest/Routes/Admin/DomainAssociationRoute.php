<?php

namespace PaymentPlugins\WooCommerce\PPCP\Rest\Routes\Admin;

use PaymentPlugins\WooCommerce\PPCP\Config;
use WP_REST_Server;

class DomainAssociationRoute extends AbstractRoute {

	private Config $config;

	public function __construct( Config $config ) {
		$this->config = $config;
	}

	public function get_path() {
		return 'domain-association-file';
	}

	public function get_routes() {
		return [
			[
				'methods'             => WP_Rest_SERVER::CREATABLE,
				'callback'            => [ $this, 'handle_request' ],
				'permission_callback' => [ $this, 'get_admin_permission_check' ]
			]
		];
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return array|\WP_Error
	 * @throws \Exception
	 */
	public function handle_post_request( \WP_REST_Request $request ) {
		try {
			// try to add domain association file.
			if ( isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
				$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '.well-known';
				$file = $path . DIRECTORY_SEPARATOR . 'apple-developer-merchantid-domain-association';

				require_once( ABSPATH . '/wp-admin/includes/file.php' );
				if ( function_exists( 'WP_Filesystem' ) && ( WP_Filesystem() ) ) {
					/**
					 * @var \WP_Filesystem_Base $wp_filesystem
					 */
					global $wp_filesystem;

					// Create .well-known directory if it doesn't exist
					if ( ! $wp_filesystem->is_dir( $path ) ) {
						$wp_filesystem->mkdir( $path );
					}

					// Get the domain association file from the plugin's files directory
					$source_file = $this->config->get_path( 'files/apple-developer-merchantid-domain-association' );

					// Read the domain association file from the plugin's files directory
					$contents = $wp_filesystem->get_contents( $source_file );

					if ( $contents === false ) {
						throw new \Exception(
							sprintf(
								__( 'The source file %s could not be read.', 'pymntpl-paypal-woocommerce' ),
								$source_file
							)
						);
					}

					// Write the file to the .well-known directory
					if ( $wp_filesystem->put_contents( $file, $contents, 0755 ) ) {
						return [
							'success' => true,
							'message' => __( 'The apple-developer-merchantid-domain-association file has been added to your root folder.', 'pymntpl-paypal-woocommerce' )
						];
					} else {
						throw new \Exception(
							__( 'The %1$sapple-developer-merchantid-domain-association%2$s file could not be added to your root folder. You will need to add the file manually.', 'pymntpl-paypal-woocommerce' ),
						);
					}
				} else {
					throw new \Exception( __( 'WordPress filesystem could not be initialized. You will need to add the file manually.', 'pymntpl-paypal-woocommerce' ) );
				}
			} else {
				throw new \Exception( __( 'DOCUMENT_ROOT is not set. Cannot determine where to place the domain association file.', 'pymntpl-paypal-woocommerce' ) );
			}
		} catch ( \Exception $e ) {
			return new \WP_Error( 'file_error', $e->getMessage(), [ 'status' => 404 ] );
		}
	}
}