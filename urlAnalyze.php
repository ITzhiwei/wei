<?php
    namespace wei;
    use lipowei\configClass\Config; 
    /**
     * 分析URL
     */
    class urlAnalyze{

        protected static $maohaoArr = null;
        /**
         * 返回数组 0=>/顶级/控制器/方法   1=>参数
         */
        public static function analyze(){
            $urlPathInfo = $_SERVER["QUERY_STRING"];
            //静态识别
            $pathInfo = self::suffix($urlPathInfo);
            //自定义路由处理
            $pathInfo = self::customizeAction($pathInfo);
            //域名指定入口，如果是指定域名，自动在 $pathInfo 前加上入口
            $pathInfo = self::urlEntrance($pathInfo);
            //分析域名，返回需要new的对象名和参数  $objAndParam[0] = /index/indexa/indexb/indexc  index是项目入口  indexa 是 项目的分支  indexb 是控制器  indexc是调用控制的方法名
            $objAndParam = self::getNewObj($pathInfo);
            return $objAndParam;
        }

        /**
         * 自定义路由处理
         * @param $urlPathInfo
         * @return string $urlPathInfo
         */
        protected static function customizeAction($urlPathInfo){
            $ruleAll = Config::pull('route.urlHtmlCustomize');
            $rule = $ruleAll['*'];
            if(isset($ruleAll[$_SERVER['HTTP_HOST']])) {
                $rule = $ruleAll[$_SERVER['HTTP_HOST']] + $rule;
            }
            foreach ($rule as $key=>$value){
                if(substr($key, 0, 1) != '/'){
                    $key = '/'.$key;
                }
                $maohaoStr = '';
                //检测是否存在":"符号     ['user/:age/:name'=>'/index/user/index', 'article'=>'/index/article/index']   访问URL=> /user/12/娜娜    article
                if(strstr($key, ':')){
                    $maohaoStr = substr($key, strpos($key, ':'));
                    //   user/:id  截成 user 赋值给 $key
                    $key = substr($key, 0, strlen($key) - strlen($maohaoStr) - 1);
                };
                $keylen = strlen($key);
                //检测url中是否存在匹配的
                if(strstr($urlPathInfo, $key) == $urlPathInfo && substr($urlPathInfo, $keylen, 1) == '/'){
                    //替换匹配的内容
                    $urlPathInfo = $value.substr($urlPathInfo, $keylen);
                    if(!empty($maohaoStr)){
                        $maohaoStr = str_replace('/', '', $maohaoStr);
                        $maohaoArr = explode(':', $maohaoStr);
                        array_shift($maohaoArr);
                        self::$maohaoArr = $maohaoArr;
                    }
                    break;
                }
            }
            return $urlPathInfo;
        }

        /**
         * 域名指定入口
         * @param string $urlPathInfo
         * @return string $urlPathInfo
         */
        protected static function urlEntrance($urlPathInfo){
            $urlEntranceData =Config::pull('route.urlEntranceData');
            if(isset($urlEntranceData[$_SERVER['HTTP_HOST']])){
                $urlPathInfo = $urlEntranceData[$_SERVER['HTTP_HOST']].$urlPathInfo;
            }else{
                $urlPathInfo = $urlEntranceData['*'].$urlPathInfo;
            }
            return $urlPathInfo;
        }

        
        /**
         * 伪静态支持
         * @param $urlPathInfo
         * @return false|string
         */
        protected static function suffix($urlPathInfo){
            //设置受支持的后缀
            $suffixOk = Config::pull('route.urlHtmlSuffix');
            $pathInfoLen = strlen($urlPathInfo);
            $newPathInfo = $urlPathInfo;
            foreach($suffixOk as $value){
                if(substr($value, 0, 1) != '.'){
                    $value = '.'.$value;
                }
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
         * @param $pathInfo
         * @return array
         */
        protected static function getNewObj($pathInfo){
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
                    //查看是否存在自定义路由参数
                    $maohaoArr = self::$maohaoArr;
                    if($maohaoArr != null){
                        $newInfoArr = [];
                        foreach ($infoArr as $key=>$value){
                            if(isset($maohaoArr[0])){
                                $newInfoArr[] = $maohaoArr[0];
                                array_shift($maohaoArr);
                            }
                            $newInfoArr[] = $value;
                        }
                        $infoArr = $newInfoArr;

                    }
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
         * @param $array
         * @return mixed
         */
        protected static function filter($array){
            foreach($array as $key=>$value){
                $array[$key] = preg_replace('/[^0-9a-zA-Z\-_]/', '', $value);
            }
            return $array;
        }
        
        
    }

?>