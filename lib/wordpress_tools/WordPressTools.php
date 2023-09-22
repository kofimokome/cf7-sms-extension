<?php

/**
 * @author kofimokome
 */

if ( ! class_exists( 'WordPressTools' ) ) {
	class WordPressTools {
		public $env;
		private $route_manager;
		public $migration_manager;
		private $plugin_path;
		private $plugin_basename;
		private $context;
		private static $instances = [];


		public function __construct( string $context ) {
			$this->env                                                    = ( new KMEnv( $context ) )->getEnv();
			$this->route_manager                                          = new KMRouteManager( $context );
			$this->plugin_path                                            = plugin_dir_path( $context );
			$this->plugin_basename                                        = plugin_basename( $context );
			$this->context                                                = $context;
			$this->migration_manager                                      = new KMMigrationManager( $this->getPluginDir(), $context );
			self::$instances[ explode( '/', $this->plugin_basename )[0] ] = $this;

		}

		/**
		 * @author kofimokome
		 */
		public static function getInstance( string $context ): WordPressTools {
			$plugin_basename = plugin_basename( $context );
			$plugin          = explode( '/', $plugin_basename )[0];

			if ( ! isset( self::$instances[ $plugin ] ) ) {
				throw new Exception( 'WordPressTools instance not found' );
			}

			return self::$instances[ $plugin ];
		}

		/**
		 * @author kofimokome
		 */
		public function routes(): KMRoute {
			$route = new KMRoute( $this->route_manager );

			return $route;
		}

		/**
		 * @author kofimokome
		 */
		public function renderView( string $view, $echo = true ) {
			return $this->route_manager->renderView( $view, $echo );
		}

		/**
		 * @author kofimokome
		 */
		public function route( string $name, array $params = [] ): string {
			return $this->route_manager->route( $name, $params );
		}


		/**
		 * @throws Exception
		 */
		public function getPluginDir() {
			preg_match( "/.+\/wp-content\/plugins\//", $this->plugin_path, $matches );
			if ( sizeof( $matches ) > 0 ) {
				$plugin_path = $matches[0];
				$chars       = explode( '/', $this->plugin_basename );
				if ( sizeof( $chars ) > 0 ) {
					$plugin_basename = $chars[0];

					return $plugin_path . $plugin_basename;
				}
			}

			throw new Exception( 'Could not get plugin directory' );
		}

		/**
		 * @author kofimokome
		 */
		public function getPluginURL(): string {
			return rtrim(plugin_dir_url($this->context),'/');
		}
	}

}
