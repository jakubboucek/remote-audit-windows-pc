<?php

class parserMsInfo {

	private $requiredCategories = array(
		"System Summary",
		"Souhrn systémových informací",
		"Components",
		"Součásti",
		"Storage",
		"Úložiště",
		"Drives",
		"Jednotky",
		"Disks",
		"Disky",
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

		$outputs = array();
		foreach($xml as $x) {
			$res = $this->processItem($x);
			if($res instanceof KeyValue) {
				$outputs[ (string) $res->key ] =  $res->value;
			}
		}
		return $outputs;
	}


	private function processItem( $xml ) {
		$n = $xml->getName();
		switch( $n ) {
			case "Data":
				return $this->parseData( $xml );
				break;
			case "Category":
				return $this->parseCategory( $xml );
				break;
			default:
				return NULL;
		}
	}

	private function parseCategory( $xml ) {
		$category = array();
		$n = (string)$xml->attributes()->name;

		if(!in_array($n, $this->requiredCategories)) {
			return NULL;
		}

		foreach( $xml as $item ) {
			$r = $this->processItem( $item );
			if($r instanceof KeyValue) {
				$category[ (string)$r->key ] = $r->value;
			}
		}
		return new KeyValue( $n, $category );
	}

	private function parseData( $xml ) {
		if(isset($xml->Položka)) {
			return new KeyValue( (string)$xml->Položka, (string)$xml->Hodnota );
		}
		else {
			return new KeyValue( (string)$xml->Item, (string)$xml->Value );
		}
	}
}

class KeyValue {
	public $key;
	public $value;
	public function __construct($key, $value) {
		$this->key = $key;
		$this->value = $value;
	}
}