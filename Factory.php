<?php
/**
 * 工厂类，自助单例管理类，可设置别名重新实例化
 */
 namespace wei;

 class Factory{
    public static $classArr = [];
     /**
      * 单例模式
      * @param string $className
      * @param string $classAsName 如果要获取额外重新new的类，就使用标识
      * @param mixed ...$params
      * @return mixed
      */
    public static function get($className, $classAsName = 0, ...$params){
        if(empty(self::$classArr[$className][$classAsName])) {
            if(method_exists(self::class, $className)) {
                $class = self::$className(...$params);
            }else{
                $class = self::searchClass($className, ...$params);
            }
            self::$classArr[$className][$classAsName] = $class;
        }
        return self::$classArr[$className][$classAsName];
    }

     /**
      * 当 Factory 不存在该类对应的获取方法时，尝试从全局中获取并注入 Factory 内
      * @param $className
      * @param mixed ...$params
      * @return mixed
      */
    protected static function searchClass($className, ...$params){
        return new $className(...$params);
    }

 }
 
 


?>