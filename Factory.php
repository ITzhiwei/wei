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
      * @param $className
      * @param string $methodsName
      * @return array
      */
    public static function getMethodParams($className, $methodsName = '__construct') {
        $class = new \ReflectionClass($className);
        $paramArr = [];
        //判断是否存在方法
        if ($class->hasMethod($methodsName)) {
            $fuc = $class->getMethod($methodsName);
            $params = $fuc->getParameters();
            if (count($params) > 0) {
                foreach ($params as $key => $param) {
                    if ($paramClass = $param->getClass()) {
                        $paramClassName = $paramClass->getName();
                        $args = self::getMethodParams($paramClassName);
                        $paramArr[] = (new \ReflectionClass($paramClassName))->newInstanceArgs($args);
                    }
                }
            }
        }
        //存放对象的数组
        return $paramArr;
    }

     /**
      * 当 Factory 不存在该类对应的获取方法时，尝试从全局中获取并注入 Factory 内
      * @param $className
      * @param mixed ...$params
      * @return mixed
      */
    protected static function searchClass($className, ...$params){
        $paramArr = self::getMethodParams($className);
        foreach ($params as $key=>$value){
            $paramArr[] = $value;
        }
        $class = new \ReflectionClass($className);
        if($class->hasMethod('__construct')) {
            return $class->newInstanceArgs($paramArr);
        }else{
            return $class->newInstance();
        }
    }

 }
 
 


?>