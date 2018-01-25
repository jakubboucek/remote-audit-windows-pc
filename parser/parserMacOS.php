<?php

class parserMacOS {

	private $requiredDataTypes = array(
		"SPHardwareDataType",
		"SPSoftwareDataType",
		"SPStorageDataType",
		"SPApplicationsDataType",
	);
	private $excludedKeys = array(
		"_properties",
	);
	private $excludedPaths = array(
		"^/usr/local/Cellar/",
		"/Library/",
	);

	public function parse( $file ) {
		$xml_string = file_get_contents($file);
		libxml_use_internal_errors(true);
		echo "$file\n";
		$xml = new SimpleXMLElement( $xml_string, LIBXML_NOCDATA );
		if ($xml === false) {
			$e = array();
		    foreach(libxml_get_errors() as $error) {
		        $e[] = $error->message;
		    }
		    throw new Exception("XML parse error: " . join(",", $e), 1);
		}

		foreach($xml as $x) {
			$res = $this->processTraversable($x);
			//Yep, we interest only first record
			$res = $this->filterOutput($res);
			$res = $this->filterApps($res);
			return $res;
		}
	}

	private function filterApps( $data ) {
		$excludePathRegular = join('|', $this->excludedPaths );

		foreach( $data as $key => $value ) {
			if( $value['_dataType'] == 'SPApplicationsDataType' ) {
				foreach( $data[ $value['_dataType'] ][ '_items' ] as $subkey => $subvalue ) {
					if( (! isset($subvalue['obtained_from']) || $subvalue['obtained_from'] == 'apple') || preg_match("~$excludePathRegular~i", $subvalue['path']) ) {
						unset( $data[ $value['_dataType'] ][ '_items' ][ $subkey ] );
					}
				}
			}
		}
		return $data;

	}

	private function filterOutput( $data ) {
		$output = array();
		foreach( $data as $key => $value ) {
			if( in_array($value['_dataType'], $this->requiredDataTypes ) ) {
				$output[ $value['_dataType'] ] = $value;
			}
		}
		return $output;
	}

	private function processTraversable( $xml ) {
		$n = $xml->getName();
		switch( $n ) {
			case "array":
				return $this->parseArray( $xml );
				break;
			case "dict":
				return $this->parseDict( $xml );
				break;
			default:
				throw new Exception("Mac OS XML pare error - unknown Traversable value type: $n", 1);
		}
	}

	private function parseItem( $xml ) {
			$n = $xml->getName();
			switch( $n ) {
				case "array":
				case "dict":
					return $this->processTraversable( $xml );
					break;
				case "real":
				case "string":
				case "integer":
				case "date":
					return (string) $xml;
					break;
				case "true":
					return TRUE;
					break;
				case "false":
					return FALSE;
					break;
				case "data":
					return "[[BINARY_DATA]]";
					break;
				default:
					throw new Exception("Mac OS XML pare error - unknown Item value type: $n", 1);
			}
	}

	private function parseArray( $xml ) {
		$category = array();

		foreach( $xml as $item ) {
			$r = $this->parseItem( $item );
			if(isset( $r['_name'] )) {
				$category[ $r['_name'] ] = $r;
			}
			else{
				$category[] = $r;
			}
		}
		return $category;
	}

	private function parseDict( $xml ) {
		$category = array();
		$key = NULL;

		foreach( $xml as $item ) {
			$n = $item->getName();
			switch( $n ) {
				case "key":
					$key = (string)$item;
					break;
				default:
					if( $key === NULL ) {
						throw new Exception("Mac OS XML pare error - key is not setted", 1);
					}
					if( ! in_array($key, $this->excludedKeys ) ) {
						$category[ $key ] = $this->parseItem( $item );
					}
					$key = NULL;
			}
		}
		return $category;
	}
}

class KeyValueMac {
	public $key;
	public $value;
	public function __construct($key, $value) {
		if($key === NULL) {
			throw new Exception("Mac OS XML pare error - Key cannot be NULL", 1);
		}
		$this->key = $key;
		$this->value = $value;
	}
}