<?php
define('CRYPT_RC4_MODE_INTERNAL', 1);
define('CRYPT_RC4_MODE_MCRYPT', 2);
define('CRYPT_RC4_ENCRYPT', 0);
define('CRYPT_RC4_DECRYPT', 1);
class CryptRC4 {
    /**
     * The Key
     *
     * @see CryptRC4::setKey()
     * @var String
     * @access private
     */
    var $key = "\0";

    /**
     * The Key Stream for encryption
     *
     * If CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT, this will be equal to the mcrypt object
     *
     * @see CryptRC4::setKey()
     * @var Array
     * @access private
     */
    var $encryptStream = false;

    /**
     * The Key Stream for decryption
     *
     * If CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT, this will be equal to the mcrypt object
     *
     * @see CryptRC4::setKey()
     * @var Array
     * @access private
     */
    var $decryptStream = false;

    /**
     * The $i and $j indexes for encryption
     *
     * @see CryptRC4::_crypt()
     * @var Integer
     * @access private
     */
    var $encryptIndex = 0;

    /**
     * The $i and $j indexes for decryption
     *
     * @see CryptRC4::_crypt()
     * @var Integer
     * @access private
     */
    var $decryptIndex = 0;

    /**
     * MCrypt parameters
     *
     * @see CryptRC4::setMCrypt()
     * @var Array
     * @access private
     */
    var $mcrypt = array('', '');

    /**
     * The Encryption Algorithm
     *
     * Only used if CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT.  Only possible values are MCRYPT_RC4 or MCRYPT_ARCFOUR.
     *
     * @see CryptRC4::CryptRC4()
     * @var Integer
     * @access private
     */
    var $mode;

    /**
     * Default Constructor.
     *
     * Determines whether or not the mcrypt extension should be used.
     *
     * @param optional Integer $mode
     * @return CryptRC4
     * @access public
     */

	var $continuousBuffer ;

	public static function instance(){
		return new CryptRC4();
	}

    function CryptRC4()
    {
        if ( !defined('CRYPT_RC4_MODE') ) {
            switch (true) {
                case extension_loaded('mcrypt') && (defined('MCRYPT_ARCFOUR') || defined('MCRYPT_RC4')):
                    // i'd check to see if rc4 was supported, by doing in_array('arcfour', mcrypt_list_algorithms('')),
                    // but since that can be changed after the object has been created, there doesn't seem to be
                    // a lot of point...
                    define('CRYPT_RC4_MODE', CRYPT_RC4_MODE_MCRYPT);
                    break;
                default:
                    define('CRYPT_RC4_MODE', CRYPT_RC4_MODE_INTERNAL);
            }
        }

        switch ( CRYPT_RC4_MODE ) {
            case CRYPT_RC4_MODE_MCRYPT:
                switch (true) {
                    case defined('MCRYPT_ARCFOUR'):
                        $this->mode = MCRYPT_ARCFOUR;
                        break;
                    case defined('MCRYPT_RC4');
                        $this->mode = MCRYPT_RC4;
                }
        }
    }

    /**
     * Sets the key.
     *
     * Keys can be between 1 and 256 bytes long.  If they are longer then 256 bytes, the first 256 bytes will
     * be used.  If no key is explicitly set, it'll be assumed to be a single null byte.
     *
     * @access public
     * @param String $key
     */
    function setKey($key)
    {
        $this->key = $key;

        if ( CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT ) {
            return;
        }

        $keyLength = strlen($key);
        $keyStream = array();
        for ($i = 0; $i < 256; $i++) {
            $keyStream[$i] = $i;
        }
        $j = 0;
        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $keyStream[$i] + ord($key[$i % $keyLength])) & 255;
            $temp = $keyStream[$i];
            $keyStream[$i] = $keyStream[$j];
            $keyStream[$j] = $temp;
        }

        $this->encryptIndex = $this->decryptIndex = array(0, 0);
        $this->encryptStream = $this->decryptStream = $keyStream;
    }

    /**
     * Dummy function.
     *
     * Some protocols, such as WEP, prepend an "initialization vector" to the key, effectively creating a new key [1].
     * If you need to use an initialization vector in this manner, feel free to prepend it to the key, yourself, before
     * calling setKey().
     *
     * [1] WEP's initialization vectors (IV's) are used in a somewhat insecure way.  Since, in that protocol,
     * the IV's are relatively easy to predict, an attack described by
     * {@link http://www.drizzle.com/~aboba/IEEE/rc4_ksaproc.pdf Scott Fluhrer, Itsik Mantin, and Adi Shamir}
     * can be used to quickly guess at the rest of the key.  The following links elaborate:
     *
     * {@link http://www.rsa.com/rsalabs/node.asp?id=2009 http://www.rsa.com/rsalabs/node.asp?id=2009}
     * {@link http://en.wikipedia.org/wiki/Related_key_attack http://en.wikipedia.org/wiki/Related_key_attack}
     *
     * @param String $iv
     * @see CryptRC4::setKey()
     * @access public
     */
    function setIV($iv)
    {
    }

    /**
     * Sets MCrypt parameters. (optional)
     *
     * If MCrypt is being used, empty strings will be used, unless otherwise specified.
     *
     * @link http://php.net/function.mcrypt-module-open#function.mcrypt-module-open
     * @access public
     * @param optional Integer $algorithm_directory
     * @param optional Integer $mode_directory
     */
    function setMCrypt($algorithm_directory = '', $mode_directory = '')
    {
        if ( CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT ) {
            $this->mcrypt = array($algorithm_directory, $mode_directory);
            $this->_closeMCrypt();
        }
    }

    /**
     * Encrypts a message.
     *
     * @see CryptRC4::_crypt()
     * @access public
     * @param String $plaintext
     */
    function encrypt($plaintext)
    {
        return self::toHex($this->_crypt($plaintext, CRYPT_RC4_ENCRYPT));
    }

    /**
     * Decrypts a message.
     *
     * $this->decrypt($this->encrypt($plaintext)) == $this->encrypt($this->encrypt($plaintext)).
     * Atleast if the continuous buffer is disabled.
     *
     * @see CryptRC4::_crypt()
     * @access public
     * @param String $ciphertext
     */
    function decrypt($ciphertext)
    {
        $ciphertext = self::fromHex($ciphertext);
		return $this->_crypt($ciphertext, CRYPT_RC4_DECRYPT);
    }

    /**
     * Encrypts or decrypts a message.
     *
     * @see CryptRC4::encrypt()
     * @see CryptRC4::decrypt()
     * @access private
     * @param String $text
     * @param Integer $mode
     */
    function _crypt($text, $mode)
    {
        if ( CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT ) {
            $keyStream = $mode == CRYPT_RC4_ENCRYPT ? 'encryptStream' : 'decryptStream';

            if ($this->$keyStream === false) {
                $this->$keyStream = mcrypt_module_open($this->mode, $this->mcrypt[0], MCRYPT_MODE_STREAM, $this->mcrypt[1]);
                mcrypt_generic_init($this->$keyStream, $this->key, '');
            } else if (!$this->continuousBuffer) {
                mcrypt_generic_init($this->$keyStream, $this->key, '');
            }
            $newText = mcrypt_generic($this->$keyStream, $text);
            if (!$this->continuousBuffer) {
                mcrypt_generic_deinit($this->$keyStream);
            }

            return $newText;
        }

        if ($this->encryptStream === false) {
            $this->setKey($this->key);
        }

        switch ($mode) {
            case CRYPT_RC4_ENCRYPT:
                $keyStream = $this->encryptStream;
                list($i, $j) = $this->encryptIndex;
                break;
            case CRYPT_RC4_DECRYPT:
                $keyStream = $this->decryptStream;
                list($i, $j) = $this->decryptIndex;
        }

        $newText = '';
        for ($k = 0; $k < strlen($text); $k++) {
            $i = ($i + 1) & 255;
            $j = ($j + $keyStream[$i]) & 255;
            $temp = $keyStream[$i];
            $keyStream[$i] = $keyStream[$j];
            $keyStream[$j] = $temp;
            $temp = $keyStream[($keyStream[$i] + $keyStream[$j]) & 255];
            $newText.= chr(ord($text[$k]) ^ $temp);
        }

        if ($this->continuousBuffer) {
            switch ($mode) {
                case CRYPT_RC4_ENCRYPT:
                    $this->encryptStream = $keyStream;
                    $this->encryptIndex = array($i, $j);
                    break;
                case CRYPT_RC4_DECRYPT:
                    $this->decryptStream = $keyStream;
                    $this->decryptIndex = array($i, $j);
            }
        }

        return $newText;
    }

    /**
     * Treat consecutive "packets" as if they are a continuous buffer.
     *
     * Say you have a 16-byte plaintext $plaintext.  Using the default behavior, the two following code snippets
     * will yield different outputs:
     *
     * <code>
     *    echo $rc4->encrypt(substr($plaintext, 0, 8));
     *    echo $rc4->encrypt(substr($plaintext, 8, 8));
     * </code>
     * <code>
     *    echo $rc4->encrypt($plaintext);
     * </code>
     *
     * The solution is to enable the continuous buffer.  Although this will resolve the above discrepancy, it creates
     * another, as demonstrated with the following:
     *
     * <code>
     *    $rc4->encrypt(substr($plaintext, 0, 8));
     *    echo $rc4->decrypt($des->encrypt(substr($plaintext, 8, 8)));
     * </code>
     * <code>
     *    echo $rc4->decrypt($des->encrypt(substr($plaintext, 8, 8)));
     * </code>
     *
     * With the continuous buffer disabled, these would yield the same output.  With it enabled, they yield different
     * outputs.  The reason is due to the fact that the initialization vector's change after every encryption /
     * decryption round when the continuous buffer is enabled.  When it's disabled, they remain constant.
     *
     * Put another way, when the continuous buffer is enabled, the state of the Crypt_DES() object changes after each
     * encryption / decryption round, whereas otherwise, it'd remain constant.  For this reason, it's recommended that
     * continuous buffers not be used.  They do offer better security and are, in fact, sometimes required (SSH uses them),
     * however, they are also less intuitive and more likely to cause you problems.
     *
     * @see CryptRC4::disableContinuousBuffer()
     * @access public
     */
    function enableContinuousBuffer()
    {
        $this->continuousBuffer = true;
    }

    /**
     * Treat consecutive packets as if they are a discontinuous buffer.
     *
     * The default behavior.
     *
     * @see CryptRC4::enableContinuousBuffer()
     * @access public
     */
    function disableContinuousBuffer()
    {
        if ( CRYPT_RC4_MODE == CRYPT_RC4_MODE_INTERNAL ) {
            $this->encryptIndex = $this->decryptIndex = array(0, 0);
            $this->setKey($this->key);
        }

        $this->continuousBuffer = false;
    }

    /**
     * Dummy function.
     *
     * Since RC4 is a stream cipher and not a block cipher, no padding is necessary.  The only reason this function is
     * included is so that you can switch between a block cipher and a stream cipher transparently.
     *
     * @see CryptRC4::disablePadding()
     * @access public
     */
    function enablePadding()
    {
    }

    /**
     * Dummy function.
     *
     * @see CryptRC4::enablePadding()
     * @access public
     */
    function disablePadding()
    {
    }

    /**
     * Class destructor.
     *
     * Will be called, automatically, if you're using PHP5.  If you're using PHP4, call it yourself.  Only really
     * needs to be called if mcrypt is being used.
     *
     * @access public
     */
    function __destruct()
    {
        if ( CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT ) {
            $this->_closeMCrypt();
        }
    }

    /**
     * Properly close the MCrypt objects.
     *
     * @access prviate
     */
    function _closeMCrypt()
    {
        if ( $this->encryptStream !== false ) {
            if ( $this->continuousBuffer ) {
                mcrypt_generic_deinit($this->encryptStream);
            }

            mcrypt_module_close($this->encryptStream);

            $this->encryptStream = false;
        }

        if ( $this->decryptStream !== false ) {
            if ( $this->continuousBuffer ) {
                mcrypt_generic_deinit($this->decryptStream);
            }

            mcrypt_module_close($this->decryptStream);

            $this->decryptStream = false;
        }
    }



	// @function fromHex 把十六进制数转换成字符串
	function toHex($sa , $len = 0){
		$buf = "";
		if(	$len == 0  )
			$len = strlen($sa) ;
		for ($i = 0; $i < $len; $i++)
		{
			$val = dechex(ord($sa{$i}));
			if(strlen($val)< 2)
				$val = "0".$val;
			$buf .= $val;
		}
		return $buf;

	}

	// @function fromHex 把十六进制数转换成字符串
	function fromHex($sa){
		$buf = "";
		$len = strlen($sa) ;
		for($i = 0; $i < $len; $i += 2){
			$val = chr(hexdec(substr($sa, $i, 2)));
			$buf .= $val;
		}
		return $buf;
	}

}

if ( !function_exists('curl_init') )
{
  throw new Exception('Require CURL extension');
}

if ( !function_exists('json_decode') )
{
  throw new Exception('Require JSON extension');
}

class Boyaausercenter_api{
    const SECRET = 'by@#RKas[d09fik2#R_k5|s*op';
    protected $source;
    protected $secret;
    protected $apiUrl = 'https://uc.boyaagame.com/';

	protected $userAgent = 'Boyaa Agent Alpha 0.0.1';
	protected $connectTimeout = 30;
	protected $timeout = 30;
	protected $try = 3;
	protected $bpid = 'BB676A80983662C7E5B8A6F3C9F1E476';
	protected $bact = 'uc_api_profile';
	protected $bylhost = '';
	protected $bylport = '';
	protected $httpCode;
	protected $httpInfo;

	public function __construct( $source , $secret, $bylhost='127.0.0.1',  $bylport=1066 )
	{
		$this->source = $source;
		$this->secret = $secret;
		$this->bylhost = $bylhost;
		$this->bylport = $bylport;

		if( empty( $this->source ) )
		{
			throw new Exception('Require source');
		}
		if( empty( $this->secret ) )
		{
			throw new Exception('Require secret');
		}
	}
	/**
	 * 获取请求API的url
	 */
	public function get_url( $api, $data = array() )
	{
		return ( $this->apiUrl . $api . '?' . $this->buildQueryParamStr( $data ) );
	}
	/**
	 * call api (post) 请求API(post方式)
	 * @param string $api api name 接口名
	 * @param array $data params except 除去source,secret,timestamp的接口参数
	 * @return array
	 */
	public function post( $api , $data = array() )
	{
		return json_decode( base64_decode($this->http( $this->apiUrl . $api , $this->buildQueryParamStr( $data ) , 1 ) ), true );
	}
	/**
	 * call api (get) 请求API(get方式)
	 * @param string $api api name 接口名
	 * @param array $data params except 除去source,secret,timestamp的接口参数
	 * @return array
	 */
	public function get( $api , $data = array() )
	{
		return json_decode( base64_decode($this->http( $this->apiUrl . $api , $this->buildQueryParamStr( $data ) , 0 ) ) , true );
	}

	public function buildQueryParamStr( &$data )
	{
		$timestamp = microtime(true);

		$params = array(
			'source' => $this->source,
			'timestamp' => $timestamp,
		);

		$params = array_merge( $params , $data );
		$baseString = self::buildBaseString( $params );
		$signature = sha1( $baseString.$this->secret );
		$baseString .= '&signature=' . $signature;

		$rc4 = CryptRC4::instance();
		$rc4->setKey( Boyaausercenter_api::SECRET );
		$baseString = $rc4->encrypt( $baseString );

		$baseString = 'ps='.$baseString;

		return $baseString;
	}

	public static function buildBaseString( &$params )
	{
		if (!$params) return '';
		$keys = self::urlencodeRfc3986( array_keys( $params ) );
		$values = self::urlencodeRfc3986( array_values( $params ) );
		$params = array_combine( $keys , $values );

		uksort( $params , 'strcmp' );

		$pairs = array();
		foreach ( $params as $parameter => $value )
		{
			if ( is_array( $value  ) )
			{
				natsort( $value );
				foreach ( $value as $duplicate_value )
				{
					$pairs[] = $parameter . '=' . $duplicate_value;
				}
			}
			else

			{
				$pairs[] = $parameter . '=' . $value;
			}
		}
		return implode( '&' , $pairs );
	}

	public static function urlencodeRfc3986( $input )
	{
		if ( is_array( $input  ))
		{
			return array_map( array(__CLASS__, 'urlencodeRfc3986') , $input );
		}
		else if ( is_scalar( $input ) )
		{
			return trim( rawurlencode( $input )  );
		}
		else
		{
			return '';
		}
	}
	public function http( $url , $dataStr = '' , $isPost = 0 )
	{
		$this->httpInfo = array();
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_0 );
		curl_setopt( $ch, CURLOPT_USERAGENT , $this->userAgent );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , $this->connectTimeout );
		curl_setopt( $ch, CURLOPT_TIMEOUT , $this->timeout );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
		if( $isPost )
		{
			curl_setopt( $ch , CURLOPT_POST , true );
			curl_setopt( $ch , CURLOPT_POSTFIELDS , $dataStr );
			curl_setopt( $ch , CURLOPT_URL , $url );
		}
		else
		{
			curl_setopt( $ch , CURLOPT_URL , $url.'?'.$dataStr );
		}

		$try = intval($this->try);
		while($try>0){
			$try--;
			$response = curl_exec( $ch );
			if( false!==$response ){//正常响应
				curl_close( $ch );
				return $response;
			}

			$this->httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
			$this->httpInfo = array_merge( $this->httpInfo , curl_getinfo( $ch ) );
			$err['errno'] = curl_errno( $ch );
			$err['error'] = curl_error( $ch );
			$err['source'] = $this->source;
			$this->httpInfo = array_merge( $this->httpInfo , $err );

			self::byl_watchdog( $this->httpInfo, $this->bpid, $this->bact, true, $error_code, $error_message, $this->bylhost, $this->bylport );
		}

		curl_close( $ch );

		return $response;
	}

	public static function byl_watchdog($game_data, $bpid, $act_name, $keep_socket = false, &$error_code = 0, &$error_message = '', $host = '127.0.0.1', $port = 1066)
	{
		static $socket = null;
		if (strlen($bpid) != 32) {
			$error_code = 10002;
			$error_message = "valid bpid({$bpid})";
			return false;
		}

		if (empty($game_data) || !is_array($game_data)) {
			$error_code = 10003;
			$error_message = "empty game data";
			return false;
		}

		//--
		if (!isset($socket) || !$socket) {
			if (empty($host) || empty($port)) {
				$error_code = 10001;
				$error_message = "the host or port was empty";
				return false;
			}
			$socket = stream_socket_client("udp://{$host}:{$port}", $error_code, $error_message, 300);
			if (!$socket) {
				return false;
			}
		}

		if ($socket) {
			//drop the char "\r", "\n", "|"
			array_walk($game_data, array( __CLASS__,'byl_encode_argument') );
			//log_format
			//bpid|report_at|act_name game_data_json
			$log_buff_str = sprintf("%s|%s|%s\t%s\r\n",
				$bpid,
				isset($_SERVER['REQUEST_TIME']) && $_SERVER['REQUEST_TIME'] ? $_SERVER['REQUEST_TIME'] : time(),
				$act_name,
				json_encode($game_data)
			);
			fwrite($socket, $log_buff_str);
			if (!$keep_socket) {
				fclose($socket);
				$socket = null;
			}
		}
		return true;
	}

	public static function byl_encode_argument(&$value, $key) {
		$value = strtr($value, array("\r" => "", "\n" => ""));
		$value = str_replace('|', "", $value);
	}
}//End class BYClient