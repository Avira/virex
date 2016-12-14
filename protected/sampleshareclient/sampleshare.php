<?php

/* * ***************************************** */
/* Norman SampleShare Client Example          */
/* Version 1.10                               */
/* Created by Trygve Brox - Norman ASA - 2010 */
/* * ***************************************** */
ini_set('memory_limit', '512M');

define('GPG_PASSPHRASE', 'your_passphrase'); // change this with your gpg passphrase
define('GPG_PATH', 'gpg');

define('HTTP_PROXY_ADDRESS', ''); // leave this blank if you don't use any proxy
define('HTTP_PROXY_PORT', '');

define('HASH_ALGORITHM', 'sha256'); // possible values: md5,sha256

include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'sampleshare.inc');
if (!is_dir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'incoming')) {
    @mkdir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'incoming');
}

function mySampleCallback($hash)
{
    global $share;
    //echo "$hash downloaded ($share->curl_dl_speed)\r\n";	
}

function myCleanfilesCallback($hash)
{
    global $share;
    //echo "CLEANFILE $hash downloaded ($share->curl_dl_speed)\r\n";	
}

$share = new SampleShareObject();
$share->set_hash_algorithm(HASH_ALGORITHM);
$share->set_dates_localtime("<!--START_DATE--!> 00:00:00", "<!--END_DATE--!>  23:23:59");
$share->set_http_user("<!--USERNAME--!>", "<!--PASSWORD--!>");
$share->set_url("<!--BASE_URL--!>/server");
$share->set_max_blocksize("134217728");
$share->set_download_directory(dirname(__FILE__) . DIRECTORY_SEPARATOR . "incoming");
//$share->set_sample_callback("mySampleCallback");
//$share->set_cleanfiles_callback("myCleanfilesCallback");

$share->get_supported_compression();

if ($share->is_supported_compression("zlib")) {
    $share->set_compression("zlib");
}

echo "Requesting metadata.. ";
if ($share->get_metadata()) {
    echo strlen($share->metadata) . " bytes of metadata downloaded\r\n";
}

echo "Requesting CLEANFILES HASH-list.. ";
if ($share->get_cleanfiles_list()) {
    /*
      //Use this code to download cleanfiles in blocks
      foreach ($share->hashlist as &$entry) {
      // Your code to determine if a sample should be downloaded here
      $download_this_sample = true;
      if ($download_this_sample == true) {
      $entry["download"] = true;
      }
      }
	
	  
      $share->get_cleanfiles_by_list();
      echo "Downloaded $share->total_download_files samples/$share->total_download_size in $share->total_download_time seconds at $share->total_download_avg_speed\r\n";
	*/
	/*
      //Use this code to download samples individually
      echo "$share->hashcount samples found. Starting download.. \r\n\r\n";
      foreach ($share->hashlist as $entry) {
      // Your code to determine if a sample should be downloaded here
      $download_this_sample = true;
      if ($download_this_sample == true) {
      $share->get_clean_file($entry[$share->get_hash_algorithm()]);
      }
      }
      $share->print_avg_speed();
    */ 
}

echo "Requesting HASH-list.. ";
if ($share->get_list()) {

    //Use this code to download samples in blocks
    foreach ($share->hashlist as &$entry) {
        // Your code to determine if a sample should be downloaded here 
        $entry["download"] = true;
    }

    $share->get_files_by_list();
    echo "Downloaded $share->total_download_files samples/$share->total_download_size in $share->total_download_time seconds at $share->total_download_avg_speed\r\n";

    /*
      //Use this code to download samples individually
      echo "$share->hashcount samples found. Starting download.. \r\n\r\n";
      foreach ($share->hashlist as $entry) {
      // Your code to determine if a sample should be downloaded here
      $download_this_sample = true;
      if ($download_this_sample == true) {
      $share->get_file($entry[$share->get_hash_algorithm()]);
      }
      }

      $share->print_avg_speed();
     */
}

echo "Requesting URLs list..";
if ($urls = $share->get_urls()) {
    echo "Found " . count($urls) . " URLs!\n";
    echo implode("\n", $urls) . "\n";
} else {
    echo "No URLs found!";
}