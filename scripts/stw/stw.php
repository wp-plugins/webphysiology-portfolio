<?php
/**
 * Implements sourcing thumbnails from http://www.shrinktheweb.com
 *
 * Dependent on PHP5, but could be easily back-ported.  All config
 * information is defined in constants.  No reason to ever create
 * an instance of this class, hence abstract.
 *
 * adjusted by: Jeff Lambert, WEBphysiology.com
 * adjusted on: 2010-01-09
 * 
 * updated by: Jeff Lambert, WEBphysiology.com
 * updated on: 2010-01-21
 * updated   : added the "&inside=1" parameter so that URLs can be deeper than the primary domain
 *             this does require that the user purchase a higher level of ShrinkTheWeb subscription
 *             beyond Free
 * 
 * updated by: Jeff Lambert, WEBphysiology.com
 * updated on: 2010-01-11
 * updated   : added use of CURL when it is available to get the XML from ShrinkTheWeb.com
 *             added error reporting if thumbnail isn't retrieved
 * 
 * @author Entraspan, Based in part on STW sample code
 * @copyright Open Source/Creative Commons
 */

abstract class AppSTW {
	
    const THUMBNAIL_DIR = "/cache";
    const CACHE_DAYS = 3; // used 7 for Alexa!

    /**
     * Refreshes the thumbnail if it is expired or creates it if it does
     * not exist.  There is no cleanup of the thumbnails for ones that don't
     * get used again, e.g. find /static/images/thumbnails -type f -mtime +7 -delete
     *
     * Every combination of url and call arguments results in a unique filename
     * through a MD5 hash.  The size argument can also be an array where you can
     * add any parameter you wish to the request, or override any default.
     *
     * It is up to the calling function to decide what to do with the results when
     * a null is returned.  I often store the src in a database with a timestamp so
     * that I do not bombard the server with repeated requests for a thumbnail that
     * doesn't yet exist, although STW is very fast at processing.
     *
     * @param string $url URL to get thumbnail for
     * @param array $args Array of parameters to use
     * @param boolean $force Force call to bypass cache, was used for debugging
     * @return string Local SRC URI for the thumbnail.
     */
    public static function getThumbnail($url, $args = null, $force = false) {
		
        $args = $args ? $args : array("stwsize"=>"lg");
		$src = '/'.md5($url.serialize($args)).".jpg";
        $path = dirname( __FILE__ ) . self::THUMBNAIL_DIR.$src;
        $cutoff = time() - 3600 * 24 * self::CACHE_DAYS;
		
        if ($force || !file_exists($path) || filemtime($path) <= $cutoff) {
            if (($jpgurl = self::queryRemoteThumbnail($url, $args))) {
                if (($im = imagecreatefromjpeg($jpgurl))) {
					imagejpeg($im, $path, 100);
				}
			}
		}
        if (file_exists($path)) {
            return plugin_dir_url(__FILE__) . substr(self::THUMBNAIL_DIR, 1) . $src;
		}

        return null;
    }

    /**
     * Always retrieves the X-Large thumbnail from STW, then uses
     * local gd library to create arbitrary sized thumbnails.
     *
     * By passing the same arguments used for small/large should
     * generate cache hits so the only size ever retrieved would
     * be xlg.
     *
     * @param string $url URL to get thumbnail for
     * @param string $width The desired image width
     * @param string $height The desired image height
     * @param string $args Used to make name same as sm/lg fetches.
     */
    public static function getScaledThumbnail($url, $width, $height, $args = null, $force = false) {
		
        $args = $args ? $args : array("width"=>$width, "height"=>$height);
        $src = '/'.md5($url.serialize($args)).".jpg";
        $path = dirname( __FILE__ ) . self::THUMBNAIL_DIR.$src;
        $cutoff = time() - 3600 * 24 * self::CACHE_DAYS;
		
        if ( ($force || !file_exists($path) || filemtime($path) <= $cutoff) ) {
			if ( $xlg = self::getThumbnail($url, array("stwsize"=>"xlg")) ) {
				if ( $im = imagecreatefromjpeg($xlg) ) {
					
                    list($xw, $xh) = getimagesize($xlg);
                    $scaled = imagecreatetruecolor($width, $height);

                    if (imagecopyresampled($scaled, $im, 0, 0, 0, 0, $width, $height, $xw, $xh)) {
                        imagejpeg($scaled, $path, 100);
					}
                }
			}
		}
		
        if (file_exists($path)) {
			return plugin_dir_url(__FILE__) . substr(self::THUMBNAIL_DIR, 1) . $src;
		}
		
        return null;
		
    }
	
    /**
     * Calls through the API and processes the results based on the
     * original sample code from STW.
     *
     * It is common for this routine to return a null value when the
     * thumbnail does not yet exist and is queued up for processing.
     *
     * @param string $url URL to get thumbnail for
     * @param array $args Array of parameters to use
     * @return string full remote URL to the thumbnail
     */
    private static function queryRemoteThumbnail($url, $args = null, $debug = false) {
		
		$url_depth = strpos($url, ".", strlen($url) - 6);

        $args = is_array($args) ? $args : array();
        $defaults["stwaccesskeyid"] = get_option( 'webphysiology_portfolio_stw_ak' );
        $defaults["stwu"] = get_option( 'webphysiology_portfolio_stw_sk' );
		if (empty($url_depth)) {
			$defaults["inside"] = "1";
		}
		
		foreach ($defaults as $k=>$v) {
            if (!isset($args[$k])) {
                $args[$k] = $v;
			}
		}
		
		$args["stwurl"] = $url;
        $request_url = "http://images.shrinktheweb.com/xino.php?".http_build_query($args);
        $line = self::make_http_request($request_url);
		$check = strtolower($line);
		
		if ( strpos($check, 'fix_and_retry') > 0 || strpos($check, 'invalid credentials') > 0 ) {
			$errorString = 'Unable to retrieve thumbnail from ShrinkTheWeb.com';
			echo '<pre>' . htmlentities($errorString) . '</pre>';
			return null;
		}
		
        if ($debug) {
            echo '<pre style=font-size:10px>';
            unset($args["stwaccesskeyid"]);
            unset($args["stwu"]);
            print_r($args);
            echo '</pre>';
            echo '<div style=font-size:10px>';
            highlight_string($line);
            echo '</div>';
        }

        $regex = '/<[^:]*:Thumbnail\\s*(?:Exists=\"((?:true)|(?:false))\")?[^>]*>([^<]*)<\//';
		
        if (preg_match($regex, $line, $matches) == 1 && $matches[1] == "true") {
            return $matches[2];
		}

        return null;
    }
	
    private static function make_http_request($url){
	
        // get file
		if (function_exists ('curl_init')) {
			
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // forces response into return string, not echo
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_HTTPHEADER, Array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15") );
			$return = curl_exec($ch);
			curl_close($ch);
			
		} else {
			
			$lines = file($url);
			$return = implode("", $lines);
			
		}
		return $return;
}
}

?>