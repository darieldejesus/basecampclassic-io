<?php

/**
 * This class contains the functions to handle the settings used by the page.
 * @author Dariel de Jesus darieldejesus@gmail.com
 * @version 1.0
 */

class SettingsHandler {
	private $fileName;
	private $fileLocation;
	private $settings;
	private $loaded;

	/**
	 * Define default values
	 */
	function __construct() {
		$this->fileName = 'settings.db';
		$this->fileLocation = realpath('../');
		$this->settings = ['token' => '', 'home_id' => '', 'domain' => 'basecamphq.com'];
		$this->loaded = FALSE;
	}

	/**
	 * Get string from readFile and unserialize it.
	 * @return array Settings array
	 */
	public function getSettings() {
		$settings = $this->readFile();
		if ( $settings === FALSE ) {
			return $this->settings;
		}
		$this->settings = unserialize( $settings );
		return $this->settings;
	}

	/**
	 * Given a value with its settings key, calls saveFile to update settings.db.
	 * @return array Settings array
	 */
	public function setSetting( $key, $value ) {
		if ( !array_key_exists( $key, $this->settings ) ) {
			return FALSE;
		}
		$this->settings[ $key ] = $value;
		return $this->saveFile();
	}

	/**
	 * Verify and load setting file if exists.
	 * @return bool|array Setting as array if exists.
	 */
	private function readFile() {
		$filePath = $this->fileLocation . DIRECTORY_SEPARATOR . $this->fileName;
		if ( !file_exists( $filePath ) ) {
			return FALSE;
		}
		$content = file_get_contents( $filePath );
		return $content;
	}

	/**
	 * Based on array settings, creates/update settings file.
	 * @return bool True/False if file is saved.
	 */
	private function saveFile() {
		$filePath = $this->fileLocation . DIRECTORY_SEPARATOR . $this->fileName;
		$saved = file_put_contents( $filePath, serialize( $this->settings ) );
		return ( $saved > 0 );
	}
}