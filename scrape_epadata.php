#!/usr/bin/php -q
<?php
//taken from: http://us3.php.net/manual/en/function.preg-replace.php#87058
function string_to_filename($word) {
    $tmp = preg_replace('/^\W+|\W+$/', '', $word); // remove all non-alphanumeric chars at begin & end of string
    $tmp = preg_replace('/\s+/', '_', $tmp); // compress internal whitespace and replace with _
    return strtolower(preg_replace('/\W/', '', $tmp)); // remove all non-alphanumeric chars except _ and -
}

//EPA.gov
//download all of the .exe files
$url = 'http://www.epa.gov/tri/tridata/tri07/data/';
$response = file_get_contents($url);

$year="2007";

if($year == "2005") {
    preg_match_all('#<td width="25%"><a href="../../tri05/data/(.*)">#', $response, $urls);
    $siteurl = 'http://www.epa.gov/tri/tridata/tri05/data/';
}

if($year == "2007") {
    preg_match_all('#<td width="25%"><a href="../../tri07/data/(.*)">#', $response, $urls);
    preg_match_all('#<td width="25%"><a href="Statedata07/(.*)">#', $response, $urls);
    $siteurl = 'http://www.epa.gov/tri/tridata/tri07/data/Statedata07/';
}

foreach($urls[1] as $url_str) {

    //if we already have the file don't download it again
    if(!file_exists($url_str)) { 
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

    //detect the file type
    if(preg_match('/_1_/',$file)) {
        //File Type 1: Facility, Chemical, Releases and Other Waste Management Summary Information
        $file_type = _type1;
    }
    else if(preg_match('/_2a_/',$file)) {
        //File Type 2A: Detailed Source Reduction Activities and Methods
        $file_type = _type2a;
    }
    else if(preg_match('/_2b_/',$file)) {
        //File Type 2B: Detailed On-Site Waste Treatment Methods and Efficiency
        $file_type = _type2b;
    }

    else if(preg_match('/_3a_/',$file)) {
        //File Type 3A: Details of Transfers Off-site
        $file_type = _type3a;
    }

    else if(preg_match('/_3b_/',$file)) {
        //File Type 3B: Details of Transfers to Publicly Owned Treatment Works (POTW)
        $file_type = _type3b;
    }

    else if(preg_match('/_4_/',$file)) {
        //File Type 4: Details of Facility Information
        $file_type = _type4;
    }

    else if(preg_match('/_5_/',$file)) {
        //File Type 5: Additional Information on Source Reduction, Recycling and Pollution Control
        $file_type = _type5;
    }

    $fieldnames = array_shift($filedata);
    $fields = explode("\t",$fieldnames);

    //print_r($fields);
    foreach($fields as $fieldname) {
        $tablefields[] = string_to_filename($fieldname);
    }

    //print_r($tablefields);
    $end = end($tablefields);

    //echo "Creating SQL Create script for file: " . $file ."...\n";
    //echo "TABLENAME: epa_data".$file_type."\n";
    $str = "CREATE TABLE epa_data".$file_type."  ("."\n";
    foreach($tablefields as $field) {

        if($field == $end) {
            $str .= $field . " varchar(200) NOT NULL DEFAULT ''"."\n";
        }
        else {
            $str .= $field . " varchar(200) NOT NULL DEFAULT '',"."\n";
        }
    }   

    $str .= ")";
    $_createtables++;
    $fp = fopen("epa_data".$file_type.".sql", "w");
    fwrite($fp, $str);
    fclose($fp);
    //echo $str;
    $state_str = $_state[0];   
    //echo "Creating csv file: " . $state_str."_".$year.$file_type.".csv";
    $open = fopen($state_str."_".$year.$file_type.".csv", "a");
    foreach($filedata as $data) {
        $data = explode("\t", trim($data));
        $data_str =  "\"" . implode ('","', $data). "\"\r\n";
        $write = fwrite($open, $data_str);
    }
    fclose($open);
    
}
