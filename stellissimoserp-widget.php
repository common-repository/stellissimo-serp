<?php
/*
Plugin Name: Stellissimo SERP
Plugin URI: http://www.stellissimo.eu/
Description: Widget to display the first 10 positions in Google and Bing for a given keyword and language
Author: Alan Curtis
Version: 1.3
Author URI: http://www.advertalis.com/
*/
 
function stellissimoserp($options)
{
	$keywords = htmlspecialchars($options["keyword"]); //(! empty ( $_GET ["k"] )) ? htmlspecialchars ( urlencode ( $_GET ["k"] ) ) : "stellissimo";
	
	$lang = htmlspecialchars($options["language"]);//(! empty ( $_GET ["s"] )) ? htmlspecialchars ( urlencode ( $_GET ["s"] ) ) : "it";
	
	$senginesuff = ($lang == 'en') ? 'com' : $lang;
	$senginepre = ($lang == 'en') ? 'www' : $lang;
	
$spath = pathinfo($_SERVER['SCRIPT_FILENAME'],PATHINFO_DIRNAME)."/wp-content/plugins/stellissimo-serp";

	if (! file_exists ( $spath."/serps" )) {
		mkdir ( $spath."/serps" ) or die ( "Impossible to create the directory 'serps'. Please create it manually to activate this plugin<br> $spath" );
		// echo "created the dir: serps";
	}
	

	$myFile= $spath."/serps/serp_" . $keywords . "_" . $lang . ".txt";
	if (! file_exists ( $myFile )) {
	
		try{
			$fh = fopen ( $myFile, 'w' ) or die ( "can't create file" );
			fclose ( $fh );
		}catch(Exception $e){
			echo $e->getMessage();
		}
	}
	

$date1 = filemtime ( $myFile );
$date2 = new DateTime ( "now" );


$oneday = 1 * 24 * 60 * 60;

$datediff = $date2->getTimestamp() - $date1;

$days = ($datediff >= $oneday) ? 2 : 0;

	
//$days = 2;
	
	if (($days > 1) || (filesize ( $myFile ) < 2)) {
	
	
		class PageRequester {
			var $url;
			var $proxy;
			var $referer = null;
			var $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.10) Gecko/20100914 Firefox/3.6.10";
			var $gzip = 1;
			var $header = 1;
			var $timeout = 4;
			var $headers = array ();
			var $cookies = array ();
	
			public function PageRequester($url) {
				$this->url = $url;
			}
	
			public function request() {
				$ch = curl_init ();
				curl_setopt ( $ch, CURLOPT_URL, $this->url );
				curl_setopt ( $ch, CURLOPT_HEADER, $this->header );
				curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, true );
					
				// set a proxy server if provided
				if (isset ( $this->proxy ))
					curl_setopt ( $ch, CURLOPT_PROXY, $this->proxy );
				curl_setopt ( $ch, CURLOPT_HTTPPROXYTUNNEL, 1 );
				curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $this->timeout );
				// set referer if it's provided
				if (isset ( $this->referer ) && strlen ( $this->referer ) > 0)
					curl_setopt ( $ch, CURLOPT_REFERER, $this->referer );
				// curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
				curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1' );
					
				// use gzip compression if enabled
				if (isset ( $this->gzip ) && $this->gzip == 1)
					curl_setopt ( $ch, CURLOPT_ENCODING, "gzip" );
					
				$cookies = array ();
				for($i = 0; $i < sizeof ( $this->cookies ); $i ++) {
					$cookie = $this->cookies [$i];
					array_push ( $cookies, $cookie );
				}
					
				$request_headers = $this->headers;
				if (sizeof ( $cookies ) > 0)
					array_push ( $request_headers, "Cookie: " . implode ( "; ", $cookies ) );
				if (sizeof ( $request_headers ) > 0)
					curl_setopt ( $ch, CURLOPT_HTTPHEADER, $request_headers );
					
				$response = curl_exec ( $ch );
				$info = curl_getinfo ( $ch );
				$error = curl_error ( $ch );
				$http_code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
				curl_close ( $ch );
					
				list ( $response_headers, $response_body ) = explode ( "\r\n\r\n", $response, 2 );
					
				$result ['info'] = $info;
				$result ['error'] = $error;
				$result ['headers'] = $response_headers;
				$result ['body'] = $response_body;
				$result ['http_code'] = $http_code;
					
				return $result;
			}
	
			// getter and setter methods for object
			public function __set($key, $val) {
				$this->$key = $val;
			}
			public function __get($key) {
				return $this->$key;
			}
		}
	
		function GetDomain($url) {
			$nowww = ereg_replace ( 'www\.', '', $url );
			$domain = parse_url ( $nowww );
			if (! empty ( $domain ["host"] )) {
				return $domain ["host"];
			} else {
				return $domain ["path"];
			}
	
		}
	
		if (isset ( $keywords ) && strlen ( $keywords ) > 0) {
	
			/*
			 * BING BING BING BING !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			*/
	
			// request/fetch page
			$url = "http://" . $senginepre . ".bing.com/search?q=" . urlencode ( $keywords );
// . "&go=&form=QBLH&count=10";
			$pageRequester = new PageRequester ( $url );
			// $pageRequester->proxy = getrandomproxy($newproxies);
	
			$pageRequester->referer = "http://" . $senginepre . ".bing.com/";
			// add us some headers for our request
			array_push ( $pageRequester->headers, "Cache-Control: no-cache" );
			array_push ( $pageRequester->headers, "Pragma: no-cache" );
			array_push ( $pageRequester->headers, "Accept-Language: " . $lang );
	
			// echo "Requesting URL: " . $url . "\n";
			$result = $pageRequester->request ();
	
			if ($result ['http_code'] == 200) {
				// load result body (response html content) into DOM
				$dom = new DOMDocument ();
				@$dom->loadHTML ( $result ['body'] );
				$xpath = new DOMXPath ( $dom );
				$num_results = $xpath->query ( "//span[@class='sb_count']" );
				// find # of results returned by bing search
					
				// echo "Results: " . $num_results->item(0)->nodeValue . "\n";
				// find all results in page
				$result_rows = $xpath->query ( "//div[@class='sb_tlst']/h3/a" );
				// loop through our results (a DOMDocument Object) and stick the
				// urls in an array
				$result_urls = array ();
				foreach ( $result_rows as $result_object )
					array_push ( $result_urls, $result_object->getAttribute ( "href" ) );
					
				$bing_results = Array ();
					
				for($i = 0; $i < sizeof ( $result_urls ); $i ++) {
	
					$rankurl = $result_urls [$i];
	
					$rankurl = GetDomain ( $rankurl );
	
					$bing_results [$i] = $rankurl;
	
				}
	
			}
	
			/*
			 * GOOGLE GOOGLE GOOGLE GOOGLE GOOGLE GOOGLE
			* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			*/
	
			// request/fetch page
			$url = "http://www.google." . $senginesuff . "/search?q=" . urlencode ( $keywords ) . "&hl=" . $lang . "&start=0";
			$pageRequester = new PageRequester ( $url );
			$pageRequester->referer = "http://www.google." . $senginesuff . "/";
	
			// add us some headers for our request
			array_push ( $pageRequester->headers, "Cache-Control: no-cache" );
			array_push ( $pageRequester->headers, "Pragma: no-cache" );
			array_push ( $pageRequester->headers, "Accept-Language: " . $lang );
	
			// echo "Requesting URL: " . $url . "\n";
			$result = $pageRequester->request ();
	
			if ($result ['http_code'] == 200) {
				// load result body (response html content) into DOM
				$dom = new DOMDocument ();
	
				@$dom->loadHTML ( $result ['body'] );
				$nodes = $dom->getElementsByTagName ( 'cite' );
					
				$i = 0;
					
				foreach ( $dom->getElementsByTagName ( 'cite' ) as $row ) {
	
					if ($i <= 9) {
							
						$rankurl = $row->nodeValue;
							
						$rankurl2 = GetDomain ( "http://" . $rankurl );
							
						if (in_array ( $rankurl2, $bing_results )) {
							$bkey = "," . (int)(array_search ( $rankurl2, $bing_results )+1); // $key =
							// 2;
						} else {
							$bkey = ",";
						}
							
						$stringData .= $rankurl2 . ',' . ($i + 1) . $bkey . "\n";
						$i ++;
	
					}
				}
				
				unlink($myFile);	
				$fh = fopen ( $myFile, 'w' ) or die ( "can't open file" );
				fwrite ( $fh, $stringData );
				fclose ( $fh );
	
			} else {
				echo "Error occurred!\n";
				echo "HTTP response status code received: " . $result ['http_code'] . "\n";
			}
			echo "\n\n";
		} else {
			echo "Syntax: php " . __FILE__ . " keyword\n";
			echo "Example: php " . __FILE__ . " \"find search engine ranking\"\n";
			echo "NOTE: Don't forget to enclose multi-word search strings within quotes when passing arguments.\n";
		}
	
	} // fine IF di check giorni
	
	$handle = fopen ( $myFile, "rb" ) or die ( "can't read the file" );
	
	while ( ! feof ( $handle ) ) {
	
		$line_of_text = fgets ( $handle );
		$parts = explode ( ',', $line_of_text );
	
		$contents .= '<tr style="border-bottom: 1px solid gray;font-size:11px!important;"><td style="border-bottom: 1px solid gray;border-right: 1px dotted gray;padding-left:1px;">' . $parts [0] . '</td><td style="border-bottom: 1px solid gray;text-align:center;border-right: 1px dotted gray;">' . $parts [1] . '</td><td style="border-bottom: 1px solid gray;text-align:center;">' . $parts [2] . '</td></tr>';
	}
	
	fclose ( $handle );
	
	//echo '<span style="font-size:10px!important;">primi 10 risultati di Google con la posizione in Bing a fianco!</span>';
	
	echo '<p style="font-size:11px!important;margin-bottom:0!important;padding-bottom:5px!important;">Keyword: <span style="font-weight:bold!important">' . $keywords . '</span><br>';
	echo 'Language: <span style="font-weight:bold!important">' . $lang . '</span></p>';
	
	echo '<table width="'.htmlspecialchars($options["width"]).'" style="margin-top:0!important;padding-top:0!important;border: 1px solid gray;"><tr style="padding:1px;font-weight:bold!important;background:'.htmlspecialchars($options["color"]).';color:white;padding-left:2px;"><td><b>Domain</b></td><td style="text-align:center;"><b>Google</b></td><td style="text-align:center;"><b>Bing</b></td></tr>' . $contents . '</table>';
	
	// check sul file
	echo "<p style='font-size:10px!important;line-height:11px!important;'><br>last check: <span style='font-weight:bold!important'>" . date ( "F d. Y - H:i:s", filemtime ( $myFile ) ) . '</span>';

if (($options ['credits']==true) && ( is_home() || is_front_page() ) ) {
  echo '<br>by <a href="http://www.stellissimo.eu/" target="_blank">www.stellissimo.eu</a></p>';
} else {
  echo '</p>';
}

//	echo basename($_SERVER['SCRIPT_FILENAME']);
//echo pathinfo($_SERVER['SCRIPT_FILENAME'],PATHINFO_DIRNAME);
/*
echo $days;
echo '<br>file date '.date("F j, Y, H:i:s",$date1);
echo '<br>now '.date("F j, Y, H:i:s",$date2->getTimestamp());
echo '<br>diff '.$datediff;
echo '<br>diff '.date("F j, Y, H:i:s",$datediff);
echo '<br>one day '.date("F j, Y, H:i:s",$oneday);
echo '<br>one day '.$oneday;
echo '<br>filesize '.filesize ( $myFile );
*/


	
}
 
function widget_stellissimoserp($args) {
  
  extract ( $args );
  
  $options = get_option ( "widget_stellissimoserp" );
  if (! is_array ( $options )) {
  	$options = array ( 'title' => 'Stellissimo SERP','width'=>'300','keyword'=>'stellissimo','language'=>'it','color'=>'MidnightBlue','credits'=>true );
  }
  
  echo $before_widget;
  echo $before_title;
  echo $options ['title'];
  echo $after_title;
  
  // Our Widget Content
  stellissimoserp ($options);
  echo $after_widget;
}

function stellissimoserp_control() {
	$options = get_option ( "widget_stellissimoserp" );
	if (! is_array ( $options )) {
		$options = array ( 'title' => 'Stellissimo SERP','width'=>'300','keyword'=>'stellissimo','language'=>'it','color'=>'MidnightBlue','credits'=>true );
	}

	if ($_POST ['stellissimoserp-Submit']) {
		$options ['title'] = htmlspecialchars ( $_POST ['stellissimoserp-WidgetTitle'] );
		$options ['width'] = htmlspecialchars ( $_POST ['stellissimoserp-WidgetWidth'] );
		$options ['keyword'] = htmlspecialchars ( $_POST ['stellissimoserp-WidgetKeyword'] );
		$options ['language'] = htmlspecialchars ( $_POST ['stellissimoserp-WidgetLanguage'] );	
		$options ['color'] = htmlspecialchars ( $_POST ['stellissimoserp-WidgetColor'] );	
		$options ['credits'] = $_POST ['stellissimoserp-WidgetCredits'];	
		update_option ( "widget_stellissimoserp", $options );
	}

	?>
	<p>
	<label for="stellissimoserp-WidgetTitle">Title: </label> 	
	<input class="widefat" type="text" id="stellissimoserp-WidgetTitle" name="stellissimoserp-WidgetTitle" value="<?php echo $options['title'];?>" />
	<br />
	<label for="stellissimoserp-WidgetWidth">Width: </label> 	
	<select class="widefat" id="stellissimoserp-WidgetWidth" name="stellissimoserp-WidgetWidth">
		<option value="180" <?php if($options['width']==180) echo 'selected';?> >180px</option>
		<option value="240" <?php if($options['width']==240) echo 'selected';?>>240px</option>
		<option value="300" <?php if($options['width']==300) echo 'selected';?>>300px</option>		
		<!-- option value="50%" <?php if($options['width']=='50%') echo 'selected';?>>50%</option>	 
		<option value="60%" <?php if($options['width']=='60%') echo 'selected';?>>60%</option>	 
		<option value="70%" <?php if($options['width']=='70%') echo 'selected';?>>70%</option>	 
		<option value="80%" <?php if($options['width']=='80%') echo 'selected';?>>80%</option>	 
		<option value="90%" <?php if($options['width']=='90%') echo 'selected';?>>90%</option>	 
		<option value="100%" <?php if($options['width']=='100%') echo 'selected';?>>100%</option -->	 
	</select>
	
	<label for="stellissimoserp-WidgetKeyword">Keyword: </label> 	
	<input class="widefat" type="text" value="<?php echo $options['keyword'];?>" name="stellissimoserp-WidgetKeyword" id="stellissimoserp-WidgetKeyword" />
	
	<label for="stellissimoserp-WidgetLanguage">Language: </label> 	
	<select class="widefat" id="stellissimoserp-WidgetLanguage" name="stellissimoserp-WidgetLanguage">
		<option value="en" <?php if($options['language']=="en") echo 'selected';?>>en</option>
		<option value="it" <?php if($options['language']=="it") echo 'selected';?>>it</option>
		<option value="fr" <?php if($options['language']=="fr") echo 'selected';?>>fr</option>
		<option value="es" <?php if($options['language']=="es") echo 'selected';?>>es</option>	
		<option value="de" <?php if($options['language']=="de") echo 'selected';?>>de</option>			 
		<option value="nl" <?php if($options['language']=="nl") echo 'selected';?>>nl</option>	
	</select>

	<label for="stellissimoserp-WidgetColor">Color: </label> 	
	<select class="widefat" id="stellissimoserp-WidgetColor" name="stellissimoserp-WidgetColor">
		<option value="MidnightBlue" <?php if($options['color']=="MidnightBlue") echo 'selected';?>>MidnightBlue</option>
		<option value="Navy" <?php if($options['color']=="Navy") echo 'selected';?>>Navy</option>
		<option value="Red" <?php if($options['color']=="Red") echo 'selected';?>>Red</option>
		<option value="SaddleBrown" <?php if($options['color']=="SaddleBrown") echo 'selected';?>>SaddleBrown</option>	
		<option value="MediumVioletRed" <?php if($options['color']=="MediumVioletRed") echo 'selected';?>>MediumVioletRed</option>			 
		<option value="Maroon" <?php if($options['color']=="Maroon") echo 'selected';?>>Maroon</option>		
<option value="Green" <?php if($options['color']=="Green") echo 'selected';?>>Green</option>	
		<option value="DeepPink" <?php if($options['color']=="DeepPink") echo 'selected';?>>DeepPink</option>
		<option value="DarkViolet" <?php if($options['color']=="DarkViolet") echo 'selected';?>>DarkViolet</option>
		<option value="DarkRed" <?php if($options['color']=="DarkRed") echo 'selected';?>>DarkRed</option>
		<option value="Black" <?php if($options['color']=="Black") echo 'selected';?>>Black</option>
	</select>	
<br>
<label for="stellissimoserp-WidgetCredits">Display Credits: </label> 
<input id="stellissimoserp-WidgetCredits" name="stellissimoserp-WidgetCredits" type="checkbox" <?php if($options['credits']==true) echo 'checked';?>>

	<input type="hidden" id="stellissimoserp-Submit" name="stellissimoserp-Submit" value="1" />
	
	</p>
<?php
}

function stellissimoserp_init()
{
	register_widget_control ( 'Stellissimo SERP', 'stellissimoserp_control', 100, 100 );
  	register_sidebar_widget(__('Stellissimo SERP'), 'widget_stellissimoserp');
}
add_action("plugins_loaded", "stellissimoserp_init");
?>