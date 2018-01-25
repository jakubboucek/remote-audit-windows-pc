<?php
$s = file(__DIR__.'/people.csv');
$b = array();
foreach($s as $l){
	$i = explode("\t", trim($l));
	$id = str_replace('@company.com', '', $i[3]);
	$b[$id] = array(
		'id' => $id,
		'name' => "$i[0] $i[1]",
		'fname' => $i[0],
		'lname' => $i[1],
		'email' => $i[3],
		'hr_email'=>$i[2],
		'cost_center'=>$i[4],
		'business_unit'=>$i[5],
		'dept_id'=>$i[6],
		'location'=>$i[7],
	);
}
file_put_contents(__DIR__.'/people.json', json_encode($b, JSON_PRETTY_PRINT));