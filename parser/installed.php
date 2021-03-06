<?php
$root = "/Users/bukaj/Google Drive/Company/Audity";
$peopleFile = "$root/../people.json";
include __DIR__ . '/parserWinInstall.php';

$d = new processDir( $peopleFile );
$d->processAllUsers( $root );

class processDir {
	const OS_MAC = "Mac OS";
	const OS_WIN = "Windows";
	const OS_OTHER = "Other";

	private $parserWinInstall;
	private $people;
	public function __construct( $peopleFile ) {
		$this->people = json_decode(file_get_contents($peopleFile), TRUE);
		$this->parserWinInstall = new parserWinInstall;
	}

	public function processAllUsers( $dir ) {
		$d = dir( $dir );
		/*{$entry="jmeno.prijmeni"; */while (false !== ($entry = $d->read())) {
			$pf = "$dir/$entry";
			if( !is_dir($pf) || in_array($entry, array('.','..'))) continue;
 			$files = $this->listUserFiles( $pf );
 			$os = $this->detectOs( $files );
 			$user = array(
 				'user' => $this->getUser( $entry ),
 				'files' => array_keys( $files ),
 				'os' => $os,
 				'apps' => array(),
 			);
 			switch ($os) {
 				case self::OS_WIN:
					$user['apps'] = $this->parseWin( $files );
 					break;
 				case self::OS_MAC:
					$user['apps'] = $this->parseMacOS( $files );
 					break;
 			}
 			file_put_contents("$pf/user-sw-${entry}.json", $this->exportJson($user));
 			file_put_contents("$pf/user-sw-${entry}.txt", $this->exportTxt($user, 'sysinfo'));
		}
	}

	public function listUserFiles( $dir ) {
		$files = $exts = array();
		$d = dir( $dir );
		while (false !== ($entry = $d->read())) {
			$pf = "$d->path/$entry";
			if( !is_file($pf) || in_array($entry, array('.','..',"Icon\r"))) continue;
			$files[$entry] = $pf;
		}
		return $files;
	}

	private function detectOs( $files ) {
		foreach( $files as $file => $pf ) {
			$ext = pathinfo($pf, PATHINFO_EXTENSION);
			switch ($ext) {
				case 'spx':
					return self::OS_MAC;
					break;
				case 'nfo':
					return self::OS_WIN;
					break;
			}
		}
		return self::OS_OTHER;
	}

	private function parseWin( $files ) {
		$output = array();
		foreach( $files as $file => $pf ) {
			$ext = pathinfo($pf, PATHINFO_EXTENSION);
			switch ($ext) {
				case 'xml':
					$output += $this->parserWinInstall->parse($pf);
					break;
			}
		}
		return $output;
	}

	private function parseMacOS( $files ) {
		foreach( $files as $file => $pf ) {
			if(preg_match('/user-info-.*\.json/', $file)) {
				echo "$pf\n";
				$source = json_decode(file_get_contents($pf), TRUE);
				return array_keys($source['raw_os']['mac_os']['system_profiler']['SPApplicationsDataType']['_items']);
			}
		}
	}

	private function getUser( $id ) {
		if( $this->people[ $id ] ) {
			return $this->people[ $id ];
		}

		throw new Exception( "Unknown user $id" );
	}

	private function exportTxt( $data, $path = "" ) {
		$buffer = "";
		foreach ($data as $key => $value) {
			$cp = $path . '[' . $key . ']';
			if(is_array($value)) {
				$buffer .= $this->exportTxt( $value, $path . '[' . $key . ']' );
			}
			elseif( is_scalar($value)) {
				$buffer .= $cp . ": ";
				$buffer .= ((string) $value) . "\n";
			}
			elseif( empty($value)) {
				$buffer .= $cp . ": NULL";
			}
			else {
				$buffer .= $cp . ": ";
				$buffer .= "[[ Unprintable object type ]]\n";
			}
		}
		return $buffer;
	}

	private function exportJson( $data) { 
		return json_encode($data, JSON_PRETTY_PRINT);
	}
}

