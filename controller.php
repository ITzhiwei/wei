<?php

    namespace wei;

    class controller{

        //url pathInfo 参数
        public $param = [];
        //post 参数
        public $post;
        //参数强制指定格式
        protected $dataType = [
            //支持负数
            //'id'=>'int',
            'uid'=>'int',
            'user_id'=>'int',
            'token'=>'numAndAbc',
        ];
        //子类添加需要转化的key
        protected $childClassDataType = [];


        //  url的路由信息，[0]一级目录入口（映射前） [1]二级目录入口  [2]控制器  [3]所调用的方法
        //  注意：一般来说，一个一级目录就是一个项目应用，用户端后台管理端可以放在二级目录入口；当然，如果你喜欢，用俩个一级目录来区分用户端和后台管理端也是可以的
        public $route;
        //当非url访问时（url访问的控制器调用其他地方的控制器）,$route只存在 [0] [1] [2]，第[3]需要手动传入后再启动run()，默认值是 index
        public $fucName = 'index';
        //程序结束后服务器继续执行 - 函数   0=>function
        public $fucArr = [];
        //是否执行前置钩子类，提供给URL访问的 A控制器 内调用 B控制器 时能 B 能选择是否关闭前置执行
        public $runHookBefore = true;
        //是否执行后置钩子类，此处和前置钩子类作用一样
        public $runHookAfter = true;

        /**
         * @param array $param 正常url访问时从url内获取的参数
         * @param array $injectPost 在控制器内 new 其他控制器类时手动注入的 Post 参数，原控制器的 post 参数不被继承
         * @param array $route 从url中获的项目入口、次目录、控制器、方法的参数
         */
        public function run($param = [], $injectPost = [], $route = []){
            if(!empty($param)){
                $this->param = $param;
            };
            if($injectPost != []){
                $this->post = $injectPost;
            }else{
                $this->post = $_POST;
            }

            if($route == []){
                $info = urlAnalyze::analyze();
                $pathArr = explode('/', substr($info[0], 1));
                $this->route = [$pathArr[0], $pathArr[1], $pathArr[3]];
                $route = $this->route;
            }else{
                $this->route = $route;
            }
            $this->dataTransform();

            if($this->runHookBefore) {
                //执行前置钩子
                $this->hookBefore();
            }

            if(isset($route[3])){
                $fucName = $route[3];
            }else{
                $fucName = $this->fucName;
            }
            $paramArr = Factory::getMethodParams(static::class, $fucName);
            $res = call_user_func_array([$this, $fucName], $paramArr);
            if($this->runHookAfter) {
                //执行后置钩子
                $this->hookAfter($res);
            }
            if ($this->fucArr != []) {
                $this->userAccessEndExecute();
            }

        }

        /**
         * 格式强制转化
         */
        protected function dataTransform(){
            $this->param = $this->dataTransformAction($this->param);
            $this->post = $this->dataTransformAction($this->post);
        }

        protected function dataTransformAction($data){
            $dataType = array_merge($this->dataType, $this->childClassDataType);
            foreach($dataType as $key=>$type){
                if(array_key_exists($key, $data)){
                    $data[$key] = $this->transform($data[$key], $type);
                }
            }
            return $data;
        }

        /**
         * @param string|int $data
         * @param $type
         * @return int|string|string[]|null
         */
        public function transform($data, $type){
            switch($type){
                case 'int':
                    $data = (int)$data;
                    break;
                case 'numAndAbc':
                    $data = preg_replace('/[^0-9a-zA-Z]/', '', $data);
                    break;
                //符号 - _ . 字母数字汉字等可通过  其他一些必要符号会转为中文符号
                case 'numAndAbcChinese':
                    // \x{2700}-\x{27BF}\x{2600}-\x{26FF}\x{1F680}-\x{1F6FF}\x{1F300}-\x{1F5FF}\x{1F600}-\x{1F64F} emoji表情
                    // \x{4e00}-\x{9fa5}    中文
                    $data = preg_replace('/[^0-9a-zA-Z\x{4e00}-\x{9fa5}\-_,\.]/u', '', $data);
                    break;
                case 'Chinese':
                    $data = preg_replace('/[^\x{4e00}-\x{9fa5}]/u', '', $data);
                    break;
                case 'sqlAndHtml':
                    $data = (string)$data;
                    $data = str_replace('?', '？', $data);
                    $data = str_replace('/', '／', $data);
                    $data = str_replace('\\', '＼', $data);
                    $data = str_replace(',', '，', $data);
                    $data = str_replace('<', '＜', $data);
                    $data = str_replace('>', '＞', $data);
                    $data = str_replace('#', '&nbsp;#&nbsp;', $data);
                    $length = strlen($data)-1;
                    $centerStr = '';
                    $single = 0;
                    $double = 0;
                    for($i=0; $i<=$length; $i++)
                    {
                        $tempData = $data[$i];
                        if($tempData == "'"){
                            $single++;
                            if($single%2 == 0){
                                $tempData = '’';
                            }else{
                                $tempData = '‘';
                            }
                        }elseif ($tempData == '"'){
                            $double++;
                            if($double%2 == 0){
                                $tempData = '”';
                            }else{
                                $tempData = '“';
                            }
                        };
                        $centerStr = $centerStr.$tempData;
                    }
                    $data = $centerStr;
                    break;
                case 'abc':
                    $data = preg_replace('/[^a-zA-Z]/', '', $data);
                    break;
                case 'num':
                    $data = preg_replace('/[^0-9]/', '', $data);
                    break;
                case 'noHtml':
                    //不允许存在Html标签
                    $data = str_replace('<', '＜', $data);
                    $data = str_replace('>', '＞', $data);
                    break;
            }
            return $data;
        }

        /**
         * 前置钩子，依次执行一级目录钩子（如application）、二级目录中间件（如index）
         */
        public function hookBefore(){
            $oldParam = $this->param;
            $oldPost = $this->post;
            $route = $this->route;

            //全局钩子，所有控制器都会执行
            $FilePath = __DIR__.'/../hook/hookBefore.php';
            if(is_file($FilePath)) {
                include_once $FilePath;
                $hookBefore = new \hookBefore;
                //执行全局钩子
                list($this->param, $this->post) = $hookBefore->handle($this, $oldParam, $oldPost);
            }
            //先判断一级目录钩子是否存在
            $oneFilePath = \weiLoader::$type[$route[0]].'hook/hookBefore.php';
            if(is_file($oneFilePath)) {
                $className = $route[0].'\\hook\\hookBefore';
                $oneHookBefore = new $className;
                //执行一级目录钩子，即该目录下的所有控制器都会执行该钩子
                list($this->param, $this->post) = $oneHookBefore->handle($this, $oldParam, $oldPost);
            }
            //二级目录钩子
            $twoFilePath = \weiLoader::$type[$route[0]].$route[1].'/hook/hookBefore.php';
            if(is_file($twoFilePath)) {
                $className = $route[0] . '\\' . $route[1] . '\\hook\\hookBefore';
                $twoHookBefore = new $className;
                //执行二级目录钩子，即该目录下的所有控制器都会执行该钩子
                list($this->param, $this->post) = $twoHookBefore->handle($this, $oldParam, $oldPost);
            }
        }

        /**
         * 后置钩子
         * @param mixed $res 控制器返回内容
         */
        public function hookAfter($res){
            $oldRes = $res;
            $route = $this->route;

            //全局钩子，所有控制器都会执行
            $FilePath = __DIR__.'/../hook/hookAfter.php';
            if(is_file($FilePath)) {
                include_once $FilePath;
                $hookAfter = new \hookAfter;
                $res = $hookAfter->handle($this, $res);
            }

            $oneFilePath = \weiLoader::$type[$route[0]].'hook/hookAfter.php';
            if(is_file($oneFilePath)) {
                $className = $route[0].'\\hook\\hookAfter';
                $oneHookAfter = new $className;
                $res = $oneHookAfter->handle($this, $res, $oldRes);
            }
            $twoFilePath = \weiLoader::$type[$route[0]].$route[1].'/hook/hookAfter.php';
            if(is_file($twoFilePath)) {
                $className = $route[0] . '\\' . $route[1] . '\\hook\\hookAfter';
                $twoHookAfter = new $className;
                $twoHookAfter->handle($this, $res, $oldRes);
            }
        }

        /**
         * 视图文件加载
         * @param array $data
         * @param string $viewName
         */
        public function view($data = null, $viewName = null){
            $route = $this->route;
            if($viewName == null) {
                $fucName = $route[3];
            }else{
                $fucName = $viewName;
            }
            //通过映射得到真实的一级目录入口
            $oneEntrance = \weiLoader::$type[$route[0]];
            $twoEntrance = $route[1];
            $controller = $route[2];
            $viewFile = $oneEntrance.$twoEntrance."/view/$controller/$fucName.view.php";
            $viewFile = str_replace('\\', '/', $viewFile);
            if(is_file($viewFile)) {
                include $viewFile;
            }else{
                exit("不存在 ../view/$controller/$fucName.view.php 视图文件");
            }
        }

        /**
         * @param string $urlParam
         */
        public function href($urlParam){
            header("Location:$urlParam");
        }



        /**
         * @param int $code
         * @param string $msg
         * @param string $data
         */
        protected function json($code = 200, $msg = '操作成功', $data = ''){
                if (is_array($code)) {
                    $return['code'] = 200;
                    $return['msg'] = '操作成功';
                    $return['data'] = $code;
                } else {
                    if (is_string($code) && strlen((int)$code) != strlen($code)) {
                        $return['code'] = 400;
                        $return['msg'] = $code;
                    } else {
                        $return['code'] = $code;
                        $return['msg'] = $msg;
                        $return['data'] = $data;
                    }
                }
                echo json_encode($return, JSON_UNESCAPED_UNICODE);
        }

        /**
         * 当调用不存在的方法时返回这个
         */
        public function __call($a, $b){
            @header('HTTP/1.1 404');
            $this->json(404, '该方法不存在:'.$a);
            $this->null = true;
        }

        /**
         * 将数据推送给用户后在后台继续执行相关程序
         */
        public function userAccessEndExecute(){
            $fucArr = $this->fucArr;
            if(\function_exists('fastcgi_finish_request')){
                fastcgi_finish_request();
            };
            foreach ($fucArr as $value){
                $value();
            }
        }

    }
?>