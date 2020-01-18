<?php
    
    use lipowei\configClass\Config;
    class weiLoader{
        
        /**
         * 路径映射白名单，动态配置其他路由在config/route.php里面
         */
        public static $type = [
            'wei'=>__DIR__.'/'
        ];

        /**
         * 自动加载入口
         * @param string $class
         */
        public static function autoload($class){
            $class = preg_replace('/[^0-9a-zA-Z\\\\_\\-]/', '', $class);
            self::$type = array_merge(self::$type, Config::pull('route.door'));
            $file = self::findFile($class);
            if(is_file($file)){
                include($file);
            }else{
                @header('HTTP/1.1 404');
                exit ('<meta charset="utf-8"/>不存在类'.$class);
            }
        }
        
        
        /**
         * 查看是否属于白名单入口
         */
        public static function findFile($class){
            //获取顶级命名空间
            if(substr($class, 0, 1) == '\\'){
                $class = substr($class, 1);
            };
            //顶级命名空间
            $type = substr($class, 0, strpos($class, '\\'));
            if(!empty(self::$type[$type])){
                //获取映射目录
                $path = self::$type[$type];
                $file = str_replace('\\', '/', $path.substr($class, strlen($type)+1).'.php');
                return $file;
            }else{
                @header('HTTP/1.1 404');
                if(!empty($type)) {
                    exit ('<meta charset="utf-8"/>请求未被识别');
                };
            }
        }
        
        /**
         * 错误日志记录
         */
        public static function errorLog($class){
            
        }
        
    }
    spl_autoload_register('weiLoader::autoload');
    //$smg = new \admin\asd\smg();
    
   

?>