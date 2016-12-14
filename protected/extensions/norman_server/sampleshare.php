<?php

/* * ***************************************** */
/* Norman SampleShare Client Framework        */
/* Version 1.30                               */
/* Created by Trygve Brox - Norman ASA - 2010 */
/* * ***************************************** */
include("server_includes.php");


$share = new ServerObject();
if ($share->uri_action == "")
    die();

$user = new UserObject($share->sql, $share->uri_user);
$share->virex_currentUser = $user;
$share->virex_init();
if ($share->uri_action == "getlist" && isset($_REQUEST["from"])) {
	if(isset($_GET['clean']) && ($_GET['clean']=='true')){
		if(!$user->rights_clean){
			echo "ERROR! => You are not allowed to download the clean files";
			die();
		}
	}
	
    $plaintext = $share->get_list();
    $encrypted = '';
    $encrypted = $share->encrypt_file($plaintext, $user->key_name);

    if (!file_exists($encrypted))
        die("ERROR! => Error encrypting hashlist!");
    $contents = file_get_contents($encrypted);

    if ($share->verify_gpg($contents)) {
        $share->send_headers("hashlist.gpg", filesize($encrypted));
        echo $contents;
    } else {
        echo "ERROR! => Unable to encrypt list";
    }

    @unlink($encfile);
    @unlink($encrypted);
    @unlink($plaintext);
    die();
}

if ($share->uri_action == "getmetadata" && isset($_REQUEST["from"])) {
    $metadata = "";
    /* Your code to get metadata here */
//	$metadata = '-- here will be metadata --';
    /* $metadata should contain metadata for all samples shared between the two given dates in XML-format according formatted according to the IEEE standard */
    if ($metadata == "")
        $metadata = "ERROR! => No metadata found";
    $encrypted = $share->encrypt_buffer($metadata, $user->key_name,'metadata.txt');
    if ($share->verify_gpg($encrypted)) {
        $share->send_headers("metadata.gpg", strlen($encrypted));
        echo $encrypted;
    } else {
        echo "ERROR! => Unable to encrypt list .";
    }
    die();
}
if ($share->uri_action == "geturls" && isset($_REQUEST["from"])) {
	$urls = "";
	if(!$user->rights_urls){
		$urls = "";
		echo "ERROR! => Access denied!";
		die();
	}
	else{
		$url = $share->sql->Query("SELECT url_url FROM urls_url WHERE " . $share->virex_ExtraConditions['urls'] . ' LIMIT  0,400000');
		while ($row = $url->fetch_object()) {
			$urls .= "$row->url_url\n";
		}
		/* Your code to get URL's here */
		/* $urls should contain all URL's shared between the two given dates separated by newline characters */
		if (trim($urls) == "")
			$urls = "ERROR! => No URL's found";
	}
    $encrypted = $share->encrypt_buffer($urls, $user->key_name,'urls.txt');

    if ($share->verify_gpg($encrypted)) {
        $share->send_headers("urls.gpg", strlen($encrypted));
        echo $encrypted;
    } else {
        echo "ERROR! => Unable to encrypt URL list .";
    }
    die();
}

if ($share->uri_action == "get_supported_compression") {
    $share->get_supported_compression();
}

if ($share->uri_action == "get_supported_hashes") {
    $share->get_supported_hashes();
}

if ($share->uri_action == "getfile_by_list") {
    if (isset($_POST['md5list']) && ($_POST["md5list"] != "")) {
        $hashlist = $share->secure(@$_POST["md5list"]);
    } else {
        $hashlist = $share->secure(@$_POST["hashlist"]);
		
    }
	$share->uri_hash_type = "md5";
	if(isset($_REQUEST['uri_hash_type']) && $_REQUEST['uri_hash_type']=='sha256'){
		$share->uri_hash_type = "sha256";
	}

    $share->send_headers("block.gpg");

    $arr = explode(":", $hashlist);
    if ($arr[count($arr) - 1] == "")
        unset($arr[count($arr) - 1]);

    $loop = 0;
    foreach ($arr as $hash) {
        $loop++;
        if ($hash != "") {
            $hash = strtoupper($hash);
            $sample = $share->get_sample($hash);
			if($sample){
				if ($share->uri_compression != "") {
					$compressed_sample = $share->compress_file($sample);
					$encrypted = $share->encrypt_file($compressed_sample, $user->key_name);
					@unlink($compressed_sample);
				} else {
					$encrypted = $share->encrypt_file($sample, $user->key_name);
				}

				$contents = file_get_contents($encrypted);
				if ($share->verify_gpg($contents)) {
					$len = sprintf("%010d", filesize($encrypted));
					echo $len . $hash . $contents;
				} else {
					$share->send_error("Unable to encrypt file! $loop $contents", $hash);
				}

				@unlink($encrypted);
			}
            
        }
    }
    die();
}

if ($share->uri_action == "getfile") {
	
    $hash = strtoupper($share->secure($_REQUEST[$share->uri_hash_type]));
    $sample = $share->get_sample($hash);
	if($sample){
		if ($share->uri_compression != "") {
			$compressed_sample = $share->compress_file($sample);
			$encrypted = $share->encrypt_file($compressed_sample, $user->key_name);
			@unlink($compressed_sample);
		} else {
			$encrypted = $share->encrypt_file($sample, $user->key_name);
		}

		$contents = file_get_contents($encrypted);

		if ($share->verify_gpg($contents)) {
			$share->send_headers($share->filename . ".gpg", filesize($encrypted));
			echo $contents;
		} else {
			echo "ERROR! => Unable to encrypt file! $contents";
		}

		@unlink($plaintext);
		@unlink($encrypted);
	}
    else{
		echo "ERROR! => Unable to encrypt file! $contents";
	}
    die();
}