<?
function download($URL, $name, &$error) {
  if ($file = popen("wget --progress=dot -O $name $URL 2>&1", 'r')) {
    echo "Downloading: $name ...\r";
    $level = -1;
    while (!feof($file)) {
      if (preg_match("/\d+%/", fgets($file), $matches)) {
        $percentage = substr($matches[0],0,-1);
        if ($percentage > $level) {
          echo "Downloading: $name ... $percentage% \r";
          $level = $percentage;
        }
      }
    }
    if (($perror = pclose($file)) == 0) {
      echo "Downloading: $name ... done\n";
      return true;
    } else {
      echo "Downloading: $name ... failed (".error_desc($perror).")\n";
      $error = "wget: $Uname download failure (".error_desc($perror).")";
      return false;
    }
  } else {
    $error = "wget: $name failed to open";
    return false;
  }
}
function error_desc($code) {
  switch($code) {
    case 0: return 'No errors';
    case -1: return 'Generic error';
    case 1: return 'Generic error';
    case 2: return 'Parse error';
    case 3: return 'File I/O error';
    case 4: return 'Network failure';
    case 5: return 'SSL verification failure';
    case 6: return 'Username/password authentication failure';
    case 7: return 'Protocol errors';
    case 8: return 'Invalid URL / Server error response';
    default: return 'Error code '.$code;
  }
}

$build = $argv[1];

$mediaPaths['tempFiles']  = "/tmp/mediabuild";

$mediaPaths['sources'] = $mediaPaths['tempFiles']."/sources.json";

$sources = json_decode(file_get_contents($mediaPaths['sources']),true);

echo "Now installing ".$sources[$build]['imageType']." version ".$sources[$build]['imageVersion']."\n\n";

$downloadURL = $sources[$build]['imageURL'];

echo "Base URL: $downloadURL\n\n";

download($downloadURL."/bzimage","/tmp/mediabuild/bzimage",$error);
download($downloadURL."/bzroot","/tmp/mediabuild/bzroot",$error);
download($downloadURL."/bzroot-gui","/tmp/mediabuild/bzroot-gui",$error);
download($downloadURL."/bzmodules","/tmp/mediabuild/bzmodules",$error);
download($downloadURL."/bzfirmware","/tmp/mediabuild/bzfirmware",$error);
download($downloadURL."/bzimage.sha256","/tmp/mediabuild/bzimage.sha256",$error);
download($downloadURL."/bzroot.sha256","/tmp/mediabuild/bzroot.sha256",$error);
download($downloadURL."/bzroot-gui.sha256","/tmp/mediabuild/bzroot-gui.sha256",$error);
download($downloadURL."/bzmodules.sha256","/tmp/mediabuild/bzmodules.sha256",$error);
download($downloadURL."/bzfirmware.sha256","/tmp/mediabuild/bzfirmware.sha256",$error);

echo "\n";

$bzimageSHA256 = explode(" ",file_get_contents("/tmp/mediabuild/bzimage.sha256"));
$bzrootSHA256 = explode(" ",file_get_contents("/tmp/mediabuild/bzroot.sha256"));
$bzroot_guiSHA256 = explode(" ",file_get_contents("/tmp/mediabuild/bzroot-gui.sha256"));
$bzmodulesSHA256 = explode(" ",file_get_contents("/tmp/mediabuild/bzmodules.sha256"));
$bzfirmwareSHA256 = explode(" ",file_get_contents("/tmp/mediabuild/bzfirmware.sha256"));

echo "Checking SHA 256's: ";

if ( hash_file("sha256", "/tmp/mediabuild/bzimage") != $bzimageSHA256[0] || hash_file("sha256", "/tmp/mediabuild/bzroot") != $bzrootSHA256[0]  || hash_file("sha256", "/tmp/mediabuild/bzroot-gui") != $bzroot_guiSHA256[0]  || hash_file("sha256", "/tmp/mediabuild/bzmodules") != $bzmodulesSHA256[0]  || hash_file("sha256", "/tmp/mediabuild/bzfirmware") != $bzfirmwareSHA256[0] ) {
  echo "failed!\n\n";
  exit(1);
} else {
  echo "passed\n\n";

  exec("cp /tmp/mediabuild/bzimage /boot/bzimage");
  exec("cp /tmp/mediabuild/bzroot /boot/bzroot");
  exec("cp /tmp/mediabuild/bzroot-gui /boot/bzroot-gui");
  exec("cp /tmp/mediabuild/bzmodules /boot/bzmodules");
  exec("cp /tmp/mediabuild/bzfirmware /boot/bzfirmware");
  echo "You must reboot your server\n\n";
  file_put_contents("/tmp/mediabuild/reboot","reboot");

}








?>
