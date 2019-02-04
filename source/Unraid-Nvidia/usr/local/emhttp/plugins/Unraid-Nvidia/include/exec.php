<?PHP
require_once("/usr/local/emhttp/plugins/Unraid-Nvidia/include/xmlHelpers.php");

function download_url($url, $path = "", $bg = false, $timeout=45){
	if ( ! strpos($url,"?") ) {
		$url .= "?".time(); # append time to always wind up requesting a non cached version
	}
	exec("curl --compressed --max-time $timeout --silent --insecure --location --fail ".($path ? " -o '$path' " : "")." $url ".($bg ? ">/dev/null 2>&1 &" : "2>/dev/null"), $out, $exit_code );
	return ($exit_code === 0 ) ? implode("\n", $out) : false;
}
function startsWith($haystack, $needle) {
	return $needle === "" || strripos($haystack, $needle, -strlen($haystack)) !== FALSE;
}

#Variables
$mediaPaths['tempFiles']  = "/tmp/mediabuild";
$mediaPaths['sources'] = $mediaPaths['tempFiles']."/sources.json";
$mediaPaths['reboot'] = $mediaPaths['tempFiles']."/reboot";

#If temp dir does not exist then create.
if ( ! is_dir($mediaPaths['tempFiles']) ) {
  exec("mkdir -p ".$mediaPaths['tempFiles']);
}

switch ($_POST['action']) {

case 'show_description':
  $build = isset($_POST['build']) ? urldecode(($_POST['build'])) : false;

  $sources = json_decode(file_get_contents($mediaPaths['sources']),true);

  echo "<font size='2'>".$sources[$build]['imageDescription']."</font>";;
  break;

#Sets Text to be displayed in Dropdown Menu
case 'build_buttons':
  $types['nvidia']             = "Nvidia";
  $types['stock']              = "unRaid";

  $tempFile = $mediaPaths['tempFiles']."/temp";
  $description = $mediaPaths['tempFiles']."/description";

  @unlink($tempFile);

$xmlRaw = file_get_contents("https://lsio.ams3.digitaloceanspaces.com/?prefix=unraid-nvidia/");
$o = TypeConverter::xmlToArray($xmlRaw,TypeConverter::XML_GROUP);
foreach ($o['Contents'] as $test) {
    $folder[dirname($test['Key'])] = true;
}

foreach (array_keys($folder) as $path) {
	if ($path == ".") { continue; }
	$imageType = basename($path);
	if ( !$types[$imageType] ) { continue; }
	$tmpArray['imageType'] = $types[$imageType];
	$tmpArray['imageURL'] = "https://lsio.ams3.digitaloceanspaces.com/$path";
	$tmpArray['imageVersion'] = str_replace("-",".",basename(dirname($path)));
	if ( ! strpos($tmpArray['imageURL'],"stock") ) {
		download_url($tmpArray['imageURL']."/unraid-media",$description);
    $tempVar = parse_ini_file($description);
	  $tmpArray['imageDescription'] = "This will install the ".$tempVar['base']." Unraid Nvidia build with v".$tempVar['driver']. " drivers";
  } else {
    $tmpArray['imageDescription'] = "This will install stock Unraid";
	}
	@unlink($description);
	$mediaVersions[] = $tmpArray;
}

  $build = array();
  foreach ($mediaVersions as $key => $row){
    $build[$key] = $row['imageType'];
  }
  array_multisort($build, SORT_ASC, $mediaVersions);

  file_put_contents($mediaPaths['sources'],json_encode($mediaVersions, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

# set to true for separate menus, or false for all in one
# doesn't really work for true

  $separate = false;

  exec('mkdir -p "'.$mediaPaths['tempFiles'].'"');

  if ( is_file($mediaPaths['reboot']) )
  {
    $reboot = "true";
  }

  $sources = json_decode(file_get_contents($mediaPaths['sources']),true);
  $i = 0;
  foreach ($sources as $source) {
    $source['id'] = $i;
    if ( $source['imageType'] == "unRaid" ) {
      $buttons['unRaid']['name'] = "unRaid";
      $buttons['unRaid']['builds'][] = $source;
    } else {
      $buttons['MediaBuilds']['name'] = "Media Builds";
      $buttons['MediaBuilds']['builds'][] = $source;
    }
    $i = ++$i;
  }
  $o = "<center>";
  foreach ( $buttons as $button ) {
    if ( $button['name'] == "unRaid" ) {
      $o .= "Stock Unraid Builds: <select id='unRaid' onchange='showDescription0(value);'>";
    } else {
      $o .= "Nvidia Unraid Builds: <select id='Media' onchange='showDescription1(value);'>";
    }

    $o .= "<option value='default' disabled selected>Select an image to install</option>";
    foreach ($button['builds'] as $option) {
      $o .= '<option value="'.$option['id'].'" onselect="showDescription();">'.$option['imageType'].' '.$option['imageVersion'].'</option>';
    }
    $o .= "</select>";
  }
  echo $o;
  $o = "</center>";
  break;

case "check_reboot":
  if ( is_file("/tmp/mediabuild/reboot") ) {
    echo "reboot required";
  }
  break;
}
?>
