#!/usr/bin/php -q
<?php
//taken from: http://us3.php.net/manual/en/function.preg-replace.php#87058
function string_to_filename($word) {
    $tmp = preg_replace('/^\W+|\W+$/', '', $word); // remove all non-alphanumeric chars at begin & end of string
    $tmp = preg_replace('/\s+/', '_', $tmp); // compress internal whitespace and replace with _
    return strtolower(preg_replace('/\W/', '', $tmp)); // remove all non-alphanumeric chars except _ and -
}

function getFile($fileurl, $file)
{
    $fp = fopen($file, "w");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FILE, $fp); 
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $fileurl);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
}

//EPA.gov
//download all of the .exe files
$years = array('2005','2006','2007');

$year = $argv[1];

if(!in_array($year, $years)) {
    die('ERROR: Invalid Year - we only support 2005 - 2007'."\n");
}

//deal with patterns for each year

//2005
if($year == "2005") {
    $siteurl = 'http://www.epa.gov/tri/tridata/tri05/data/';
    $response = file_get_contents($siteurl);
    preg_match_all('#<td width="25%"><a href="../../tri05/data/(.*)">#', $response, $urls);
}

//2006
if($year == "2006") {
    $siteurl = 'http://www.epa.gov/tri/tridata/tri06/data/';
    $response = file_get_contents($siteurl);
    preg_match_all('#<td width="25%"><a href="../../tri06/data/(.*)">#', $response, $urls);

}

//2007
if($year == "2007") {
    $url = 'http://www.epa.gov/tri/tridata/tri05/data/';
    $siteurl = 'http://www.epa.gov/tri/tridata/tri07/data/Statedata07/';
    $response = file_get_contents($url);
    preg_match_all('#<td width="25%"><a href="../../tri07/data/(.*)">#', $response, $urls);
    preg_match_all('#<td width="25%"><a href="Statedata07/(.*)">#', $response, $urls);
}

foreach($urls[1] as $url_str) {

    //if we already have the file don't download it again
    if(!file_exists($url_str)) { 
        //TODO: getFile saving file but zip is broken
        //getFile($site_url.$url_str, $url_str);
        exec('wget '.$siteurl.$url_str);
        if(file_exists($url_str)) {
            if(!exec('unzip ./'.$url_str)) {
                echo 'Error unzipping file';
            }
        }
    }
}

// we got the files and have unzipped them
$files = glob("*.txt");

$_createtables=0;

foreach($files as $file) {
    //echo "Working on file: " . $file ."...\n";
    $_state = explode("_", $file);

    $filedata = file($file);
    
    $state_str = $_state[0];

    $open = fopen($state_str."_".$year.$file_type.".csv", "a");
    foreach($filedata as $data) {
        $data = explode("\t", trim($data));
        $data_str =  "\"" . implode ('","', $data). "\"\r\n";
        $write = fwrite($open, $data_str);
    }
    fclose($open);
    
}
