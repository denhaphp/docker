<?php
namespace denha;

class Start
{
    public static $client;
    public static $config = array();

    /**
     * [start description]
     * @date   2017-07-14T16:12:51+0800
     * @author ChenMingjiang
     * @param  string                   $client [配置文件名称]
     * @param  string                   $route  [路由模式 mca smca ca]
     * @return [type]                           [description]
     */
    public static function up($client = '', $route = 'mca')
    {
        //执行创建文件
        get('build') == false ?: self::bulid();

        self::$client = $client;
        //获取配置文档信息
        self::$config = include CONFIG_PATH . 'config.php';
        if (is_file(CONFIG_PATH . 'config' . $client . '.php')) {
            self::$config = array_merge(include (CONFIG_PATH . 'config.php'), include (CONFIG_PATH . 'config' . $client . '.php'));
            print_r(self::$config);
        }

        error_reporting(0);
        register_shutdown_function('denha\Trace::catchError');
        set_error_handler('denha\Trace::catchNotice');
        set_exception_handler('denha\Trace::catchApp');

        Start::filter(); //过滤
        Route::$route(); //解析路由

        if (preg_match("/^[A-Za-z](\/|\w)*$/", CONTROLLER)) {
            $class = Route::$class;
            if (class_exists($class)) {
                $object = new $class();
            } else {
                $object = false;
            }
        } else {
            $object = false;
        }

        if (!$object) {
            throw new Exception('NOT FIND CONTROLLER : ' . CONTROLLER . ' in ' . $class = Route::$class);
        }

        $action = lcfirst(parsename(ACTION, 1));

        if (!method_exists($object, $action)) {
            throw new Exception('Class : ' . Route::$class . ' NOT FIND [ ' . $action . ' ] ACTION');
        }

        get('api') == false ?: self::apiDoc(Route::$class, $action);

        $action = $object->$action();

        if (self::$config['trace']) {
            Trace::run();
        }

    }

    /**
     * 过滤GET POST参数
     * @date   2017-07-26T17:20:10+0800
     * @author ChenMingjiang
     * @return [type]                   [description]
     */
    public static function filter()
    {
        $urlArr  = array('xss' => '\=\+\/v(?:8|9|\+|\/)|\%0acontent\-(?:id|location|type|transfer\-encoding)');
        $argsArr = array('xss' => '[\'\\\'\;\*\<\>].*\bon[a-zA-Z]{3,15}[\s\\r\\n\\v\\f]*\=|\b(?:expression)\(|\<script[\s\\\\\/]|\<\!\[cdata\[|\b(?:eval|alert|prompt|msgbox)\s*\(|url\((?:\#|data|javascript)', 'sql' => '[^\{\s]{1}(\s|\b)+(?:select\b|update\b|insert(?:(\/\*.*?\*\/)|(\s)|(\+))+into\b).+?(?:from\b|set\b)|[^\{\s]{1}(\s|\b)+(?:create|delete|drop|truncate|rename|desc)(?:(\/\*.*?\*\/)|(\s)|(\+))+(?:table\b|from\b|database\b)|into(?:(\/\*.*?\*\/)|\s|\+)+(?:dump|out)file\b|\bsleep\([\s]*[\d]+[\s]*\)|benchmark\(([^\,]*)\,([^\,]*)\)|(?:declare|set|select)\b.*@|union\b.*(?:select|all)\b|(?:select|update|insert|create|delete|drop|grant|truncate|rename|exec|desc|from|table|database|set|where)\b.*(charset|ascii|bin|char|uncompress|concat|concat_ws|conv|export_set|hex|instr|left|load_file|locate|mid|sub|substring|oct|reverse|right|unhex)\(|(?:master\.\.sysdatabases|msysaccessobjects|msysqueries|sysmodules|mysql\.db|sys\.database_name|information_schema\.|sysobjects|sp_makewebtask|xp_cmdshell|sp_oamethod|sp_addextendedproc|sp_oacreate|xp_regread|sys\.dbms_export_extension)', 'other' => '\.\.[\\\\\/].*\%00([^0-9a-fA-F]|$)|%00[\'\\\'\.]');

        $httpReferer = empty($_SERVER['HTTP_REFERER']) ? array() : array($_SERVER['HTTP_REFERER']);
        $queryString = empty($_SERVER['QUERY_STRING']) ? array() : array($_SERVER['QUERY_STRING']);
        GSF($queryString, $urlArr);
        GSF($httpReferer, $argsArr);
        GSF($_GET, $argsArr);
        GSF($_POST, $argsArr);
        GSF($_COOKIE, $argsArr);

        if (MAGIC_QUOTES_GPC) {
            $_GET     = array_map('GSS', $_GET);
            $_POST    = array_map('GSS', $_POST);
            $_COOKIE  = array_map('GSS', $_COOKIE);
            $_REQUEST = array_map('GSS', $_REQUEST);
        }
    }

    //自动创建文件夹
    private static function apiDoc($class, $action)
    {
        $doc = new \ReflectionMethod($class, $action);
        $tmp = $doc->getDocComment();

        /* $flag = preg_match_all('/@cc(.*?)\n/', $tmp, $tmp);
        $tmp  = trim($tmp[1][0]);
        $tmp  = $tmp != '' ? $tmp : '无';*/
        var_dump($doc);
    }

    //自动创建文件夹
    private static function bulid()
    {
        $dir = [
            'controller' => ['index'],
            'tools'      => ['dao', 'vendor', 'util', 'var'],
            'view'       => ['index'],
        ];

        $path = APP_PATH . APP . DS;
        foreach ($dir as $key => $value) {
            if (!is_dir($path . $key)) {
                mkdir($path . $key, 0077, true);
                if (is_array($value)) {
                    foreach ($dir[$key] as $k => $v) {
                        if (!is_dir($path . $key . DS . $v)) {
                            mkdir($path . $key . DS . $v, 0077, true);
                        }
                    }
                }
            }
        }

        die('创建成功');
    }
}
