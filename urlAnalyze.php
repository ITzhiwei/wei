<?php
    namespace wei;
    use lipowei\configClass\Config; 
    /**
     * 分析URL
     */
    class urlAnalyze{
        
        /**
         * 返回数组 0=>/顶级/控制器/方法   1=>参数
         */
        public static function analyze(){
            $urlPathInfo = self::customize($_SERVER["QUERY_STRING"]);
            //格式： new /顶层/控制器/方法   后面是参数
            $pathInfo = self::suffix($urlPathInfo);
            $objAndParam = self::getNewObj($pathInfo);
            return $objAndParam;
        }

        /**
         * 自定义路由支持
         * @param $urlPathInfo
         * @return string
         */
        protected static function customize($urlPathInfo){
            $customize = Config::pull('route.urlHtmlCustomize');
            foreach ($customize as $key=>$value){
                //检测是否存在冒号":"  user/search   user/search/:id => index/index/user/search    user/:age/:name => index/index/user/index     age/18/name/张三
                $startPosition = strpos($key, ':');
                if($startPosition !== false){
                    //存在，先将前面非“:”部分转化

                }else{
                    //不存在 :  直接查看 url 是否符合
                    $len = strlen($key);
                    if(substr($urlPathInfo, 0, $len-1) == $key){
                        $urlPathInfo = $value.substr($urlPathInfo, $len);
                    }
                }
            }
            return $urlPathInfo;
        }
        
        /**
         * 伪静态支持
         */
        public static function suffix($urlPathInfo){
            //设置受支持的后缀
            $suffixOk = Config::pull('route.urlHtmlSuffix');
            $pathInfoLen = strlen($urlPathInfo);
            $newPathInfo = $urlPathInfo;
            foreach($suffixOk as $value){
                $suffixLen = strlen($value);
                $buttomNum = strrpos($urlPathInfo, $value);
                if(!empty($buttomNum)){
                    $pathInfo2 = substr($urlPathInfo, 0, $buttomNum);
                    if(strlen($pathInfo2)+$suffixLen===$pathInfoLen){
                        $newPathInfo = $pathInfo2;
                    }
                }
            }
            return $newPathInfo;
        }
        
        /**
         * 获取需要new的对象及参数
         */
        public static function getNewObj($pathInfo){
            $param = [];
            if(strlen($pathInfo)>1){
                //需要删掉第1个干扰的/
                if(substr($pathInfo, 0, 1) == '/'){
                    $pathInfo = substr($pathInfo, 1);
                };
                
                $infoArr = explode('/', $pathInfo);
                $action1 = $infoArr[0];
                $action2 = !empty($infoArr[1])?$infoArr[1]:'index';
                $action3 = !empty($infoArr[2])?$infoArr[2]:'index';
                $action4 = !empty($infoArr[3])?$infoArr[3]:'index';
                
                list($action1, $action2, $action3, $action4) = self::filter([$action1, $action2, $action3, $action4]);
                
                $newObj  = "/$action1/$action2/$action3/$action4";

                if(count($infoArr) > 4){
                    array_splice($infoArr, 0, 4);

                    $n = 0;
                    foreach($infoArr as $k=>$v){
                        if($n%2==0){
                            if(isset($infoArr[$k+1])){
                                $param[$v] = $infoArr[$k+1];
                            }else{
                                $param[$v] = '';
                            }
                        }
                        $n++;
                    }
                }
            }else{
                $newObj = '/index/index/index/index';
            }

            return [$newObj, $param];
            
        }
        
        /**
         * 路由区域过滤，特殊符号仅支持-_  key=>value参数则无限制
         */
        public static function filter($array){
            foreach($array as $key=>$value){
                $array[$key] = preg_replace('/[^0-9a-zA-Z\-_]/', '', $value);
            }
            return $array;
        }
        
        
    }
/*
    $a = new urlAnalyze;

    print_r($a->analyze());
*/
?>