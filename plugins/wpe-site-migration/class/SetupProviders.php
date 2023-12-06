<?php

namespace DeliciousBrains\WPMDB;


class SetupProviders
{

	public $providers = [];
	public $classes = [];

	function setup()
	{
		$potential_classes = [];
		//load in this order Pro/Migrations/Lite
		if ( defined("WPMDB_PRO") && WPMDB_PRO ) {
			$potential_classes[] = \DeliciousBrains\WPMDB\Pro\ClassMap::class;
		} elseif (defined("WPE_MIGRATIONS") && WPE_MIGRATIONS) {
			$potential_classes[] = \DeliciousBrains\WPMDB\SiteMigration\ClassMap::class;
		} else {
			$potential_classes[] = \DeliciousBrains\WPMDB\Free\ClassMap::class;
		}

		foreach ( $potential_classes as $class ) {
			$this->maybeAddProvider( $class );
		}

		if ( !empty( $this->providers ) ) {
			$classes = $this->classes;
			foreach ( $this->providers as $provider ) {
				$vars = get_object_vars( $provider );
				foreach ( $vars as $prop => $var ) {
					if ( !\in_array( $var, $classes, true ) ) {
						$classes[$prop] = $var;
					}
				}
			}

			$this->classes = $classes;
		}
	}

	function maybeAddProvider( $class )
	{
		if ( class_exists( $class ) ) {
			$this->providers[] = new $class;
		}
	}

}

