<!DOCTYPE html>
<html>
<head>
<title>Kerberos SMB-Share Test</title>
</head>
<body>
<pre>
<?php
   /*
    * Copyright (C) 2018  C1-10P
    *
    * This program is free software: you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation, either version 3 of the License, or
    * (at your option) any later version.
    *
    * This program is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    * GNU General Public License for more details.
    *
    * You should have received a copy of the GNU General Public License
    * along with this program.  If not, see <http://www.gnu.org/licenses/>.
    */

     $smb_url = 'smb://domain.local/netlogon/test.txt';

     // inspired by https://git.typo3.org/TYPO3CMS/Extensions/fal_cifs.git
     
     if (!extension_loaded("krb5"))
     {
        die('You need to install php-pecl-krb5.');
     }

     if (!function_exists('smbclient_state_new')) 
     {
        die('libsmbclient-php is not installed!');
     }
    

     //read apache kerberos ticket cache
     $cacheFile = getenv("KRB5CCNAME");
     print "apache-cache-file: " . $cacheFile . "\n";

     if(!$cacheFile)
     {
        echo "Error: apache did not cache the kerberos token! \n";
        echo "maybe a server problem: check apache and kerberos config files. \n";
        echo "maybe a client problem: check your browser setting, trigger klist purge, reboot client. \n";
        die();
     }

     $krb5 = new \KRB5CCache();
     $krb5->open($cacheFile);

     if(!$krb5->isValid())
     {
        die("Error: the cached kerberos ticket is not valid!");
     }

     print "cached kerberos ticket entries: \n";
     print_r($krb5->getEntries());

     //workaround: smbclient is not working with the original apache ticket cache.
     $tmpFilename = tempnam("/tmp", "krb5cc_php_");
     $tmpCacheFile = "FILE:" . $tmpFilename;
     $krb5->save($tmpCacheFile);
     print "temp-cache-file: " . $tmpCacheFile . "\n";
     putenv("KRB5CCNAME=" . $tmpCacheFile);


     // smbclient
     // sample-code and error-codes: https://github.com/eduardok/libsmbclient-php/blob/master/README.md

     // Create new state:
     $state = smbclient_state_new();
     smbclient_option_set($state, SMBCLIENT_OPT_USE_KERBEROS, true);

     if (version_compare(PHP_VERSION, '7.2.0') >= 0) {
       smbclient_state_init($state, "DOMAIN.LOCAL" , "some_text");
     }
     else
     {
     // Bug?: If the field username is null, the kerberos auth is not working.
     smbclient_state_init($state, null , "some_text", null);
     }
     print "error-code smbclient_state_init: " . smbclient_state_errno($state) . "\n";

     // Open a file for reading:
     print "file-url: " . $smb_url . "\n";
     $file = smbclient_open($state, $smb_url, 'r');
     print "error-code smbclient_open: " . smbclient_state_errno($state) . "\n";
     print "file content: \n";

     // Read the file incrementally, dump contents to output:
     while (true) {
	$data = smbclient_read($state, $file, 100000);
	if ($data === false || strlen($data) === 0) {
		break;
	}
	echo $data;
     }
     echo "error-code smbclient_read: " . smbclient_state_errno($state) . "\n";


     // Close the file handle:
     smbclient_close($state, $file);

     // Free the state:
     smbclient_state_free($state);

     // cleanup 
     if (file_exists($tmpFilename)) {
        unlink($tmpFilename);
     }
?>
</body>
</html>
<pre>
