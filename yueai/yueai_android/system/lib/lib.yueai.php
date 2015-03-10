<?php 
defined('YUEAI') OR exit('Access Denied!');

/**
 * 实现类的按需加载
 *
 * @package Lib
 * @author  WYJ
 * 
 * @version 1.0.0
 */
final class Yueai
{
    protected static $_paths = array( APP_COM_PATH,
	                                  SYS_API_PATH,
									  APP_MOD_PATH,
	                                  APP_LOD_PATH,
                                      APP_CFG_PATH,
                                      SYS_HEP_PATH,
                                      SYS_LIB_PATH,
                                      SYSPATH,);
									  
    protected static $_files    = array();
    protected static $_instance = array();
    public    static $ext       = '.php';
    protected static $_init     = FALSE;
    
    /**
     * 环境变量初始化
     *
     * @author WYJ
     *
     * @return void
     */
    public static function init()
    {
        if(self::$_init)
        {
            return;
        }
        self::$_init = TRUE;
        
        error_reporting(PRODUCTION_SERVER ? 0:(E_ALL ^ E_NOTICE));
        
        spl_autoload_register(array('Yueai', 'auto_load'));
		//自定义错误处理函数
		register_shutdown_function('Yueai::fatalError');
		set_error_handler('Yueai::appError');
		set_exception_handler('Yueai::appException');
    }
	/**
	 * 自定义错误文件
	 * @access public
     * @param int $errno 错误类型
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行数
     * @return void
	**/
	public static function appError($errno,$errstr,$errfile,$errline){
		$errorstr = "";
		$errorType = array (
               E_ERROR            	=> 'ERROR',
               E_WARNING        	=> 'WARNING',
               E_PARSE          	=> 'PARSING ERROR',
               E_NOTICE        		=> 'NOTICE',
               E_CORE_ERROR    		=> 'CORE ERROR',
               E_CORE_WARNING  		=> 'CORE WARNING',
               E_COMPILE_ERROR  	=> 'COMPILE ERROR',
               E_COMPILE_WARNING 	=> 'COMPILE WARNING',
               E_USER_ERROR    		=> 'USER ERROR',
               E_USER_WARNING  		=> 'USER WARNING',
               E_USER_NOTICE    	=> 'USER NOTICE',
               E_STRICT        		=> 'STRICT NOTICE',
               E_RECOVERABLE_ERROR  => 'RECOVERABLE ERROR'
        );
		if(array_key_exists($errno,$errorType)){
			$err = $errorType[$errno];
		} else {
			$err = 'CAUGHT EXCEPTION';
		}
		
		$errorstr .= "[{$err}] error occured in file [{$errfile}] 第 [{$errline}] 行 错误信息为: {$errstr} 错误号为: [{$errno}]";
		Logs::debug("appRrror", $errorstr);
	}
    /**
	 * 致命错误捕获
	**/	
    public static function fatalError() {
        
		if ($e = error_get_last()) {
            switch($e['type']){
              case E_ERROR:
              case E_PARSE:
              case E_CORE_ERROR:
              case E_COMPILE_ERROR:
              case E_USER_ERROR:  
                Logs::debug("fatelerror",$e);
                break;
            }
        }
    }
	/**
     * 自定义异常处理
     * @access public
     * @param mixed $e 异常对象
     */
    public static function appException($e) {
        $error = array();
        $error['message']   =   $e->getMessage();
        $trace              =   $e->getTrace();
        if('E'==$trace[0]['function']) {
            $error['file']  =   $trace[0]['file'];
            $error['line']  =   $trace[0]['line'];
        }else{
            $error['file']  =   $e->getFile();
            $error['line']  =   $e->getLine();
        }
        $error['trace']     =   $e->getTraceAsString();
        Logs::debug("appException",$error);
    }
    /**
     * 类自动加载器
     *
     * @author WYJ
     *
     * @param {string} $class 类名
     *
     * @return boolean
     */
    public static function auto_load($class)
    {        
        $pos  = strrpos($class, '_');
        
        if($pos > 0)
        {
            $suffix = substr($class, 0, $pos + 1);
            $dir    = str_replace('_', '/', $suffix );
        }
        else
        {
            $dir = "{$class}/"; 
        }
        
        $dir = strtolower($dir);

        $file = str_replace('_', '.', strtolower($class));

        if($file = self::find_file($dir, $file,$class))
        {
            require($file);
            
            return TRUE;
        }
        
        return FALSE;
    }

    /**
     * 查找文件
     *
     * @author WYJ
     *
     * @param mix    $dir   目录名
     * @param string $file  文件名
     * @param string $ext   扩展名
	 * @param string $class 加载类名 modify by 王延军 2015-03-02
     * @param bool   $array 是否返回匹配的文件列表
     *
     * @return string|false 成功返回文件路径，失败返回FALSE
     */
    public static function find_file($dir, $file, $class,$ext=NULL, $array=FALSE)
    {
        $ext = $ext ? ".{$ext}" : self::$ext;
        
        $found = FALSE;
        
		$prefix = "Facebook\\";
		$len = strlen($prefix);
		
        foreach(self::$_paths as $path)
        {
            if(strpos($class,$prefix)!==false){
				
				$base_dir = defined('FACEBOOK_SDK_V4_SRC_DIR') ? FACEBOOK_SDK_V4_SRC_DIR : SYSPATH.'api/facebooksdk4/Facebook/';	
				
				if (strncmp($prefix, $class, $len) !== 0) {
					return FALSE;
				}
				
				$relative_class = substr($class, $len);
				
				$filePath = $base_dir . str_replace('\\', '/', $relative_class) . $ext;
				
			} else {				
				$path = str_replace('{module}/', $dir, $path);
				
				$filePath = $path . $file . $ext;
				
			}    
            $key = md5($filePath);

            if(isset(self::$_files[$key]))
            {
                $found = self::$_files[$key];
                break;
            }
			
            if(is_file($filePath))
            {                
                self::$_files[$key] = $filePath;
            }
            else
            {
                continue;
            }
            
            if(isset(self::$_files[$key]) && $array==FALSE)
            {
                $found = self::$_files[$key];
                break;
            }
            else
            {
                $found[] = $filePath;
            }
        }
        
        return $found;        
    }
    
    /**
     * 设置查找路径
     *
     * @author WYJ
     *
     * @param {string} $path
     *
     * @return void
     */
    public static function setPath($path)
    {
        if(!in_array($path, self::$_paths))
        {
            self::$_paths[] = $path;
        }
    }    
}