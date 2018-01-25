<?php

class parserWinInstall {

	public function parse( $file ) {
		echo "$file\n";
		$string = file_get_contents($file);
		$string = iconv('UTF-16', 'UTF-8', $string);
		preg_match_all('/<S N="DisplayName">(.*)<\/S>/iU', $string, $matches);
		return array_values(array_unique($matches[1]));
	}
}