<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Icegram_Cache' ) ) {

/*
// Example usage
$cache = new Icegram_Cache('category', 14 * 86400);
$val = $cache->get( $key );
if ( $val === null ) {
	// Not in cache, compute
	$val = doyourprocess();
	$cache->set( $key, $val );
}
// Now use the $val

function cleanup() {
	$cache = new Icegram_Cache('category', 14 * 86400);
	$cache->cleanup();
}

// For cleanup
wp_schedule_event( time(), 'daily', array($this, 'cleanup') );
*/

class Icegram_Cache {

	var $base_dir;
	var $expire_after;
	var $enabled;
	var $_group;
	var $_hashes;

	public function set( $key, $data ) {
		if (!$this->enabled) return false;
		$res = file_put_contents($this->_file( $key ), serialize($data));
		return ( $res === false ) ? false : true;
	}

	public function get( $key ) {
		if (!$this->enabled) return null;
		if ($this->_exists( $key )) {
			return unserialize( file_get_contents( $this->_file( $key )));
		}
		return null;
	}

	public function delete( $key ) {
		if (!$this->enabled) return true;
		if ($this->_exists( $key )) {
			return unlink( $this->_file( $key ) );
		}
		return true;
	}

	public function cleanup( ) {
		if (!$this->enabled) return true;
		foreach (glob($this->base_dir . $this->_group . "*") as $filename) {
    		if (filemtime($filename) < time() - $this->expire_after) {
    			@unlink($filename);
    		}
		}
		return true;
	}

	private function _exists( $key ) {
		return (is_file( $this->_file( $key ) ) );
	}

	private function _file( $key ) {
		return $this->base_dir . $this->_group . '_' . $this->_hash( $key );
	}

	private function _hash( $key ) {
		if (!array_key_exists($key, $this->_hashes)) {
			$this->_hashes[ $key ] = md5($key);
		}
		return $this->_hashes[ $key ];
	}


	public function __construct( $group = '', $expire_after = 86400, $base_dir = '' ) {

		$this->_group = sanitize_key($group);
		$this->base_dir = $base_dir;
		$this->expire_after = $expire_after;
		$this->_hashes = array();

		if (empty($this->base_dir)) {
			$uploads = wp_upload_dir();
			$uploads_base_dir = trailingslashit( $uploads['basedir'] );
			$this->base_dir = $uploads_base_dir . 'igcache/';
		}

		if (!is_dir( $this->base_dir )) {
			if ( false === mkdir( $this->base_dir ) ) {
				$this->enabled = false;
				return;
			}
		}
		$this->base_dir = trailingslashit( $this->base_dir );
		$this->enabled = true;
	}
}

}