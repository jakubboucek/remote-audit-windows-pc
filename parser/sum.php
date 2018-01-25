<?php
$root = "/Users/bukaj/Google Drive/Socialbakers/Audity";
$summaryJson = "$root/../audit-hw-summary.json";
$summaryCsv = "$root/../audit-hw-summary.csv";
$summarySwJson = "$root/../audit-sw-summary.json";
$summarySwCsv = "$root/../audit-sw-summary.csv";
$summarySwCountCsv = "$root/../audit-sw-summary-counts.csv";

$d = new processDir( );
$d->processAllUsers( $root, $summaryJson, $summaryCsv, $summarySwJson, $summarySwCsv, $summarySwCountCsv );

class processDir {
	const OS_MAC = "Mac OS";
	const OS_WIN = "Windows";
	const OS_OTHER = "Other";

	public function __construct(  ) {
	}

	public function processAllUsers( $dir, $summaryJson, $summaryCsv, $summarySwJson, $summarySwCsv, $summarySwCountCsv ) {
		$summary = array();
		$summarySw = array('detail'=>[],'agr'=>[],'count'=>[]);
		$d = dir( $dir );
		/*{$entry="jaroslav.rab";*/while (false !== ($entry = $d->read())) {
			$pf = "$dir/$entry";
			if( !is_dir($pf) || in_array($entry, array('.','..'))) continue;

			$file = "$pf/user-info-${entry}.json";

			$source = json_decode(file_get_contents($file), TRUE);
			$user = array(
				"User ID" => $source['user']['id'],
				"User name" => $source['user']['name'],
				"User email" => $source['user']['email'],
				"User email (HR)" => $source['user']['hr_email'],
				"User email" => $source['user']['email'],
				"Cost Center" => $source['user']['cost_center'],
				"Business Unit" => $source['user']['business_unit'],
				"Dept ID" => $source['user']['dept_id'],
				"Location" => $source['user']['location'],
			);

			switch( $source['os']) {
				case self::OS_WIN:
					$user += $this->parseWindows( $source['raw_os']['windows'] );
					break;
				case self::OS_MAC:
					$user += $this->parseMacOs( $source['raw_os']['mac_os'] );
					break;
				default:
					$user += $this->parseOther( );
			}

			$summary[ $entry ] = $user;

			$file = "$pf/user-sw-${entry}.json";

			$source = json_decode(file_get_contents($file), TRUE);

			foreach($source['apps'] as $app) {
				$summarySw['detail'][] = ['app'=>$app, 'user'=>$entry];
				$summarySw['agr'][$app][] = $entry;
				$summarySw['count'][$app] = ['app'=>$app, 'count'=>count($summarySw['agr'][$app])];
			}

		}
		file_put_contents($summaryJson, $this->exportJson($summary));
		file_put_contents($summaryCsv, $this->exportCsv($summary));
		file_put_contents($summarySwJson, $this->exportJson($summarySw));
		file_put_contents($summarySwCsv, $this->exportCsv($summarySw['detail']));
		file_put_contents($summarySwCountCsv, $this->exportCsv($summarySw['count']));
	}

	private function parseWindows( $data ) {
		$pfx = "msinfo:System Summary~Souhrn systémových informací";
		$system = array(
			'Platform'=> self::OS_WIN,
			'OS'=> $this->getArrayItem($data, "$pfx:OS Name~Operační systém"),
			'OS Version'=> $this->getArrayItem($data, "$pfx:Version~Verze"),
			'Manufacturer'=> $this->getArrayItem($data, "$pfx:System Manufacturer~Výrobce systému"),
			'Model'=> $this->getArrayItem($data, "$pfx:System Model~Model systému"),
			'PC name'=> $this->getArrayItem($data, "$pfx:System Name~Název systému"),
			'User name'=> $this->getArrayItem($data, "$pfx:User Name~Uživatelské jméno"),
			'Serial Number'=> $this->getArrayItem($data, "bios:SerialNumber"),
			'RAM'=> $this->getArrayItem($data, "$pfx:Installed Physical Memory (RAM)~Nainstalovaná fyzická paměť (RAM)"),
			'Processor'=> $this->getArrayItem($data, "$pfx:Processor~Procesor"),
			'HDD'=> $this->getArrayItem($data, "$pfx:Components~Součásti:Storage~Úložiště:Disks~Disky:Size~Velikost"),
			'HDD encrypt'=>NULL
		);
		return $system;
	}
	private function parseMacOs( $data ) {
		if(!$data) {
			throw new Exception("Error Processing Request", 1);
		}
		$sw_pfx = "system_profiler:SPSoftwareDataType:_items:os_overview";
		$hw_pfx = "system_profiler:SPHardwareDataType:_items:hardware_overview";
		$hd_pfx = "system_profiler:SPStorageDataType:_items:OS X~Macintosh HD~Mac HD";
		$system = array(
			'Platform'=> self::OS_MAC,
			'OS'=> $this->getArrayItem($data, "$sw_pfx:boot_volume"),
			'OS Version'=> $this->getArrayItem($data, "$sw_pfx:os_version"),
			'Manufacturer'=> $this->getArrayItem($data, "$hw_pfx:machine_name"),
			'Model'=> $this->getArrayItem($data, "$hw_pfx:machine_model"),
			'PC name'=> $this->getArrayItem($data, "$sw_pfx:local_host_name"),
			'User name'=> $this->getArrayItem($data, "$sw_pfx:user_name"),
			'Serial Number'=> $this->getArrayItem($data, "$hw_pfx:serial_number"),
			'RAM'=> $this->getArrayItem($data, "$hw_pfx:physical_memory"),
			'Processor'=> $this->getArrayItem($data, "$hw_pfx:cpu_type"),
			'HDD'=> $this->getArrayItem($data, "$hd_pfx:size_in_bytes"),
			'HDD encrypt'=> ( !!$this->getArrayItem($data, "$hd_pfx:com.apple.corestorage.lv:com.apple.corestorage.lv.encrypted") ? "Yes":NULL),
		);
		return $system;
	}
	private function parseOther( ) {
		$system = array(
			'Platform'=> self::OS_OTHER,
			'OS'=> NULL,
			'OS Version'=> NULL,
			'Manufacturer'=> NULL,
			'Model'=> NULL,
			'PC name'=> NULL,
			'User name'=> NULL,
			'Serial Number'=> NULL,
			'RAM'=> NULL,
			'Processor'=> NULL,
			'HDD'=> NULL,
			'HDD encrypt'=> NULL,
		);
		return $system;
	}

	private function exportCsv( $data ) {
		$fl = reset($data);
		$buffer = '"'.join('","',array_keys($fl))."\"\n";
		foreach ($data as $key => $value) {
			$buffer .= '"'.join('","',array_map("addslashes", $value))."\"\n";
		}
		return $buffer;
	}

	private function exportJson( $data) { 
		return json_encode($data, JSON_PRETTY_PRINT);
	}

	private function getArrayItem($array, $pathString) {
		$path = explode(':', $pathString, 2);
		$variants = explode('~', $path[0]);
		foreach($variants as $variant) {
			if(is_array($array) && isset($array[$variant]) ) {
				if(isset($path[1])) {
					return $this->getArrayItem($array[$variant], $path[1]);
				}
				else {
					return $array[$variant];
				}
			}
		}
		return NULL;
	}
}


