<?php
define('VERSION', '1.3');
define('STEPS', 7);
$step=0;
$root = __DIR__;

$sslContextOptions = array(
    'crypto_method'       => STREAM_CRYPTO_METHOD_TLS_CLIENT,
    'verify_peer'         => TRUE,
    'cafile'              => __DIR__ . '/cacert.pem',
    'verify_depth'        => 5,
    'CN_match'            => 'audit.company.com',
    'disable_compression' => TRUE,
    'SNI_enabled'         => TRUE,
    'SNI_server_name'     => 'audit.company.com',
    'ciphers'             => 'ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA256:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:DHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA256:AES256-SHA256:AES128-SHA:AES256-SHA:AES:CAMELLIA:DES-CBC3-SHA:!aNULL:!eNULL:!EXPORT:!DES:!RC4:!MD5:!PSK:!aECDH:!EDH-DSS-DES-CBC3-SHA:!EDH-RSA-DES-CBC3-SHA:!KRB5-DES-CBC3-SHA',
);

echo "                   
                   
                   
         +@@@'       SOFTWARE AUDIT
        #@   @'      --------------
    #@@@@     @`   
   @#,;.      @@@    This tool check system health
  @;          @ @+   in your PC, get list of
  @          @' ,@   installed software, system
  @          ;  #@   configuration and send it to
  @@          ;#@    IT Support.
   @@@`       #@   
    `@:       '@     Please follow instructions
     @'       ,@     on screen.
     +@`#@@@@@@@   
     ;@@+`    '@     Press Ctrl+C to break or
     .:              PRESS ENTER TO CONTINUE.
                   
                   
";

//Wait to ENTER
$stdin=fopen('php://stdin', 'r');fgetc($stdin);fclose($stdin);

function getServer($name, $default=NULL) {
    if(isset($_SERVER[$name])) return $_SERVER[$name];
    return $default;
}

$token = hash('sha256', mt_rand());
$params = array(
    'token' => $token,
    'audit_version' => VERSION,
    'computername' => getServer("COMPUTERNAME"),
    'processor_architecture' => getServer("PROCESSOR_ARCHITECTURE"),
    'psmodulepath' => getServer("PSModulePath"),
    'username' => getServer("USERNAME"),
    'os' => getServer("OS"),
);
$query = http_build_query( $params );
$link = "https://audit.company.com/sw/automatic/run?$query";
$command = "\"$root\\pairtoken.cmd\" \"$link\"";
passthru($command);

echo "Connecting to IT support...";

$user = NULL;
for($i=0;$i<10;$i++) {
    sleep(3);
    echo ".";
    $params = array(
        'token' => $token,
        'try' => $i,
    );
    $query = http_build_query( $params );
    $url = "https://audit.company.com/sw/api/status?$query";

    $contextOptions = array(
        'http' => array(
            'method'   => 'GET',
        ),
        'ssl' => $sslContextOptions,
    );
    $sslContext = stream_context_create($contextOptions);
    $result = file_get_contents($url, NULL, $sslContext);
    if(!$result) continue;

    $data=json_decode($result, FALSE);
    if(!$data) continue;

    if(!$data->status) continue;

    if(!$data->token || !$data->token->user) continue;

    $user = $data->token->user;
    $limited_sections = $data->token->limited_sections;

    break;
}

if( $user ) {
    echo "OK.\n";
    echo "Connected as $user\n\n";
}
else {
    echo "\n\nERROR: Unable connect to IT Support!\nPlease make sure if you are signed on Audit's website.\n";
    echo "Press ENTER to exit.\n";
    //Wait to ENTER
    $stdin=fopen('php://stdin', 'r');fgetc($stdin);fclose($stdin);
    die();
}

$username = preg_replace('/@.+$/', '', $user);

function updateTokenStatus($progress, $description, $complete = FALSE) {
    global $token, $sslContextOptions;
    $params = array(
        'token' => $token,
    );
    $query = http_build_query( $params );
    $data = http_build_query(array(
        'progress' => $progress,
        'description' => $description,
        'complete' => $complete,
    ));
    $url = "https://audit.company.com/sw/api/update?$query";

    $contextOptions = array(
        'http' => array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $data,
        ),
        'ssl' => $sslContextOptions,
    );

    $sslContext = stream_context_create($contextOptions);
    $result = file_get_contents($url, FALSE, $sslContext);
}

function updateStep() {
    global $step;
    $step++;
    return $step/STEPS;
}

function step( $description, $complete = FALSE ) {
    echo "$description" . (!$complete ? '...':'');
    updateTokenStatus( ($complete ? 1 : updateStep()), $description, $complete );
}

function uploadFile( $type, $filename, $outfile ) {
    global $token, $sslContextOptions;
    $params = array(
        'token' => $token,
    );
    $query = http_build_query( $params );
    $data = array(
        'type' => $type,
    );

    $output = file_get_contents($outfile);
    unlink($outfile);

    $deflated = gzdeflate ( $output, 9 );
    unset($output);

    $boundary = '--------------------------'.microtime(TRUE);
    $header = 'Content-Type: multipart/form-data; boundary='.$boundary;

    $content =  "--".$boundary."\r\n".
        "Content-Disposition: form-data; name=\"uploaded_file\"; filename=\"".basename($filename)."\"\r\n".
        "Content-Type: application/zip\r\n\r\n".
            $deflated."\r\n";
    unset($deflated);

    foreach( $data as $fieldName=>$filedValue) {
        $content .= "--".$boundary."\r\n".
            "Content-Disposition: form-data; name=\"$fieldName\"\r\n\r\n".
            "$filedValue\r\n";
    }

    $content .= "--".$boundary."--\r\n";

    $url = "https://audit.company.com/sw/api/upload?$query";

    $contextOptions = array(
        'http' => array(
            'method'  => 'POST',
            'header'  => $header,
            'content' => $content,
        ),
        'ssl' => $sslContextOptions,
    );

    $sslContext = stream_context_create($contextOptions);
    return file_get_contents($url, FALSE, $sslContext);
}

function allowSection( $section ) {
    global $limited_sections;
    return empty($limited_sections) || preg_match("/(^|,)$section(\$|,)/", $limited_sections );
}

if( allowSection('msinfo')) {
    step( "Generating info about Windows hardware" );

    $filename= "$username.nfo";
    $outfile = "$root\\output\\$filename";
    $command = "\"$root\\msinfo.cmd\" \"$outfile\"";
    passthru($command);
    echo " OK\n";

    step( "Uploading report to IT Support" );
    uploadFile( 'msinfo', $filename, $outfile );
    echo " OK\n";
}

if( allowSection('bios')) {
    step( "Getting system identificators from BIOS" );

    $filename= "$username.bios.txt";
    $outfile = "$root\\output\\$filename";
    $command = "\"$root\\bios.cmd\" \"$outfile\"";
    passthru($command);
    echo " OK\n";

    step( "Uploading report to IT Support" );
    uploadFile( 'bios', $filename, $outfile );
    echo " OK\n";
}

if( allowSection('apps')) {
    step( "Generating list of installed applications" );
    echo "\n";

    $filename= "$username.installedapps.xml";
    $filename64= "$username.installedapps64.xml";
    $outfile = "$root\\output\\$filename";
    $outfile64 = "$root\\output\\$filename64";
    $f = file_get_contents("$root\\installedapp.ps1");
    $f = str_replace('%1', $outfile, $f);
    $f = str_replace('%2', $outfile64, $f);
    $f = iconv('UTF-8', 'UTF-16LE', $f);
    $f = base64_encode($f);
    passthru( "PowerShell.exe -NoLogo -NonInteractive -EncodedCommand $f");
    echo "OK\n";

    step( "Uploading report to IT Support" );
    echo " (1 of 2)...";
    if( file_exists($outfile) ) {
        uploadFile( 'installedapps', $filename, $outfile );
    }
    echo " (2 of 2)...";
    if( file_exists($outfile64) ) {
        uploadFile( 'installedapps64', $filename64, $outfile64 );
    }
    echo " OK\n";
}
echo "\n\n";
sleep(2);

step( "Audit of your PC now done, thank you.", TRUE );

sleep(2);
