<?php

class parserBios {

	public function parse( $filename ) {
		$f = file($filename);
		$output = array();

		if( $this->line( 0, $f ) == "SerialNumber" ) {
			$output['SerialNumber'] = $this->line( 1, $f );
		}
		if( $this->line( 2, $f ) == "Name" ) {
			$output['Name'] = $this->line( 3, $f );
		}

		if( $output ) {
			return $output;
		}

		return NULL;

	}

	private function line($line, $file) {
		if(isset($file[$line])) {
			$r = preg_quote("\t\n\r\0\x0B\xFF\xFE\x0A");
			return trim(preg_replace( "/[$r]/", "", $file[$line]));
		}
		return NULL;
	}

}