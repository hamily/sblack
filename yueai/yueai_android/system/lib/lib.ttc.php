<?php
// vim: set expandtab cindent tabstop=4 shiftwidth=4 fdm=marker:
// +----------------------------------------------------------------------+
// | The Club Movie Portal v2.0                                           |
// +----------------------------------------------------------------------+
// | Copyright (c) 2009, Tencent Inc. All rights reserved.                |
// +----------------------------------------------------------------------+
// | Authors: The Club Dev Team, ISRD, Tencent Inc.                       |
// |          sniferyuan <sniferyuan@tencent.com>                         |
// +----------------------------------------------------------------------+


/**
* @file     TTC3.php
* @version  1.0
* @author   sniferyuan
* @date     2010-06-13
*/
define( "TPHP_TTC_OP_SVRADMIN",         3 );
define( "TPHP_TTC_OP_GET",              4 );
define( "TPHP_TTC_OP_SELECT",           4 );
define( "TPHP_TTC_OP_PURGE",            5 );
define( "TPHP_TTC_OP_INSERT",           6 );
define( "TPHP_TTC_OP_UPDATE",           7 );
define( "TPHP_TTC_OP_DELETE",           8 );
define( "TPHP_TTC_OP_REPLACE",          12 );
define( "TPHP_TTC_OP_FLUSH",            13 );
define( "TPHP_TTC_OP_INVALIDATE",       14 );

define( 'TPHP_TTC_KEYTYPE_NONE',        0 );    // undefined
define( 'TPHP_TTC_KEYTYPE_INT',         1 );    // Signed Integer
define( 'TPHP_TTC_KEYTYPE_STRING',      4 );    // String, case insensitive, null ended

define( 'TPHP_TTC_FIELDTYPE_NONE',      0 );    // undefined
define( 'TPHP_TTC_FIELDTYPE_SIGNED',    1 );    // Signed Integer
define( 'TPHP_TTC_FIELDTYPE_UNSIGNED',  2 );    // Unsigned Integer
define( 'TPHP_TTC_FIELDTYPE_FLOAT',     3 );    // float
define( 'TPHP_TTC_FIELDTYPE_STRING',    4 );    // String, case insensitive, null ended
define( 'TPHP_TTC_FIELDTYPE_BINARY',    5 );    // binary


if ( !extension_loaded('bc') )
{
    if ( !ini_get("safe_mode") && ini_get("enable_dl") )
    {
        if ( !@dl("bc.so") )
        {
            exit( 'fail to dl bc.so' );
        }
    }
    else
    {
        exit( 'fail to enable dl' );
    }
}

class LIB_TTC
{
    /* TTC错误码 */
    private $ttcCode = 0;

    /* TTC错误信息 */
    private $ttcMsg  = '';

    /* TTC操作主键 */
    private $ttcMainKey = '';

    /* TTC Host */
    private $ttcHost = '';

    /* TTC Port */
    private $ttcPort = 0;

    /* TTC table name */
    private $ttcTbName = '';

    /* timeout */
    private $timeout = 3;

    /* 连接TTC句柄 */
    private $server = NULL;

    /* TTC Request对象 */
    private $request = NULL;

    /* TTC result对象 */
    public $result = NULL;

    /* TTC GET操作返回的结果条数 */
    private $numRows = 0;

    /* TTC Config，存储GET、INSERT、UPDATE操作是的字段 */
    private $ttcConfig = NULL;

    /*{{{ __construct*/
    /**
     * class TTC构造函数
     * @param  host    TTC host
     * @param  port    TTC port
     * @param  tbname  TTC table name
     * @param  timeout TTC timeout
     * @return
     */
    function __construct( $host, $port, $tbname, $timeout = 3 )
    {
        $this->ttcHost   = $host;
        $this->ttcPort   = $port;
        $this->ttcTbName = $tbname;
        $this->timeout   = $timeout;
    }
    /*}}}*/

    /*{{{ getTtcCode*/
    /**
     * 返回TTC错误码
     * @return  错误码
     */
    public function getTtcCode()
    {
        return $this->ttcCode;
    }
    /*}}}*/

    /*{{{ getTtcMsg*/
    /**
     * 返回TTC错误信息
     * @return  错误信息
     */
    public function getTtcMsg()
    {
        $this->ttcMsg = $this->result->errorMessage();
        return $this->ttcMsg;
    }
    /*}}}*/

    /*{{{ getNumRows*/
    /**
     * 返回TTC GET操作的结果条数
     * @return  结果数
     */
    public function getNumRows()
    {
        $this->numRows = $this->result->numRows();
        return $this->numRows;
    }
    /*}}}*/

    /*{{{ getAffectedRows*/
    /**
     * 返回TTC GET操作的结果条数
     * @return  结果数
     */
    public function getAffectedRows()
    {
        return $this->result->affectedRows();
    }
    /*}}}*/

    /*{{{ clear*/
    /**
     * 初始化为空
     */
    public function clear()
    {
        $this->ttcCode = 0;
        $this->ttcMsg  = '';
        $this->request = NULL;
        $this->result  = NULL;
        $this->numRows = 0;
    }
    /*}}}*/

    /*{{{ setMainKey*/
    /**
     * 设置TTC主键
     * @param  key    TTC key
     */
    public function setMainKey( $key )
    {
        $this->ttcMainKey = $key;
    }
    /*}}}*/

    /*{{{ setConfig*/
    /**
     * 存储操作TTC字段的数组
     * @param  ttckey    TTC字段数组
     */
    public function setConfig( $ttckey )
    {
        $this->ttcConfig = $ttckey;
    }
    /*}}}*/

    /*{{{ ttcInit*/
    /**
     * 初始化TTC
     */
    public function ttcInit()
    {
        $this->server = new BcServer();
        $this->server->setAddress( $this->ttcHost, $this->ttcPort );
        $this->server->setTimeout( $this->timeout );
        $this->server->setTablename( $this->ttcTbName );
    }
    /*}}}*/

    /*{{{ setKey*/
    /**
     * 设置TTC主键
     * @param  $option   TTC操作类型符
     * @return  true设置成功，否则失败
     */
    public function setKey( $option )
    {
        list( $key, $value ) = $this->ttcMainKey;
        switch ( substr( $key, 0, 1 ) )
        {
            case 'i': $this->server->intKey();
                      $this->request = new BcRequest( $this->server, $option );
                      $this->request->setKey( $value );
                      return true;
            case 's': $this->server->stringKey();
                      $this->request = new BcRequest( $this->server, $option );
                      $this->request->setKey( $value );
                      return true;
            default:  $this->ttcMsg = 'wrong_key_type';
                      return false;
        }
    }
    /*}}}*/

    /*{{{ need*/
    /**
     * 查询TTC时需要的字段
     */
    public function need()
    {
        if ( !empty( $this->ttcConfig ) )
        {
            foreach ( $this->ttcConfig as $key => $value )
            {
                $this->request->need( substr( $key, 1 ) );
            }
        }
    }
    /*}}}*/

    /*{{{ setParam*/
    /**
     * 设置INSERT和UPDATE操作的值
     */
    public function setParam()
    {
        foreach ( $this->ttcConfig as $key => $value )
        {
            $kkey = substr( $key, 1 );
            switch ( substr( $key, 0, 1 ) )
            {
                case 'i':
                case 'f':
                case 's': $this->request->set( $kkey, $value );break;
                case 'b': $this->request->setBin( $kkey, $value );break;
                default: break;
            }
        }
    }
    /*}}}*/

    /*{{{ getParam*/
    /**
     * 获取GET操作的值
     * @param  num   用户定制的需要取得记录的条数
     * @return array 结果数组
     */
    public function getParam( $num )
    {
        $array = array();
        $num = ( $num > $this->numRows ) ? $this->numRows : $num;
        $j = 0;
        for ( $i = 0; $i < $num; $i++ )
        {
            $ret = $this->result->fetch();
            if ( $ret >= 0 )
            {
                foreach ( $this->ttcConfig as $key => $value )
                {
                    $kkey = substr( $key, 1 );
                    switch ( substr( $key, 0, 1 ) )
                    {
                        case 'i': $array[$j][$kkey] = $this->result->intValue( $kkey );
                                  break;
                        case 'f': $array[$j][$kkey] = $this->result->floatValue( $kkey );
                                  break;
                        case 's': $array[$j][$kkey] = $this->result->stringValue( $kkey );
                                  break;
                        case 'b': $array[$j][$kkey] = $this->result->binaryValue( $kkey );
                                  break;
                        default: break;
                    }
                }
                $j++;
            }
        }
        return $array;
    }
    /*}}}*/

    /*{{{ eq*/
    /**
     * 设置equal条件
     * @param  eq  equal条件数组
     */
    public function eq( $eq )
    {
        foreach ( $eq as $key => $value )
        {
            $kkey = substr( $key, 1 );
            $this->request->eq( $kkey, $value );
        }
    }
    /*}}}*/

    /*{{{ lt*/
    /**
     * 设置little条件
     * @param  little   little条件数组
     */
    public function lt( $lt )
    {
        foreach ( $lt as $key => $value )
        {
            $this->request->lt( $key, $value );
        }
    }
    /*}}}*/

    /*{{{ gt*/
    /**
     * 设置great条件
     * @param  gt    gt条件数组
     */
    public function gt( $gt )
    {
        foreach ( $gt as $key => $value )
        {
            $this->request->gt( $key, $value );
        }
    }
    /*}}}*/

    /*{{{ limit*/
    /**
     * 设置limit条件
     * @param  limit  limit条件数组
     */
    public function limit( $limit )
    {
        $this->request->limit( $limit['from'], $limit['num'] );
    }
    /*}}}*/

    /*{{{ add*/
    /**
     * 设置add
     * @param  add    add数组
     */
    public function add( $add )
    {
        foreach ( $add as $key => $value )
        {
            $this->request->add( $key, $value );
        }
    }
    /*}}}*/

    /*{{{ optTTC*/
    /**
     * TTC操作  遇到-110错误ttc会自动重试一次
     *
     * @param  operation    TTC操作符
     * @param  need         GET操作的数组
     * @param  lt           little条件数组
     * @param  gt           great条件数组
     * @param  limit        limit条件数组
     */
    public function optTTC( $operation, $eq = NULL, $lt = NULL, $gt = NULL, $limit = NULL, $add = NULL )
    {
        for ($i=0; $i<2; $i++)
        {
            $this->_optTtcOnce( $operation, $eq, $lt, $gt, $limit, $add );
            if ($this->ttcCode !== -110)
            {
                break;
            }
        }
    }
    /*}}}*/


    /**
     * TTC操作
     * @param  operation    TTC操作符
     * @param  need         GET操作的数组
     * @param  lt           little条件数组
     * @param  gt           great条件数组
     * @param  limit        limit条件数组
     */
    private function _optTtcOnce( $operation, $eq = NULL, $lt = NULL, $gt = NULL, $limit = NULL, $add = NULL )
    {
        $this->clear();
        if ( empty( $this->server ) )
        {
            $this->ttcInit();
        }
        $this->setKey( $operation );
        switch ( $operation )
        {
            case TPHP_TTC_OP_GET:     $this->need();break;
            case TPHP_TTC_OP_INSERT:  $this->setParam();break;
            case TPHP_TTC_OP_UPDATE:  $this->setParam();break;
            case TPHP_TTC_OP_REPLACE: $this->setParam();break;
            default: break;
        }
        if ( !empty( $eq ) )
        {
            $this->eq( $eq );
        }
        if ( !empty( $lt ) )
        {
            $this->lt( $lt );
        }
        if ( !empty( $gt ) )
        {
            $this->gt( $gt );
        }
        if ( !empty( $limit ) )
        {
            $this->limit( $limit );
        }
        if ( !empty( $add ) )
        {
            $this->add( $add );
        }
        $this->result = new BcResult();
        $this->request->execute( $this->result );
        $this->ttcCode = $this->result->resultCode();
    }
}
?>