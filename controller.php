<?php

    namespace wei;
   
    class controller{
        
        //url pathInfo 参数
        public $param;
        //post 参数
        public $post;
        //参数强制指定格式
        protected $dataType = [
            //支持负数
            'id'=>'int',
            'uid'=>'int',
            'user_id'=>'int',
            'token'=>'numAndAbc',
        ];
        //子类添加需要转化的key
        protected $childClassDataType = [];

        //程序结束后服务器继续执行 - 函数   0=>function
        public $fucArr = [];

        /**
         * controller constructor.
         * @param array $param 正常url访问时从url内获取的参数
         * @param array $injectParam 在控制器内 new 其他控制器类时手动注入的参数
         * @param array $injectPost
         */
        public function __construct($param = [], $injectParam = [], $injectPost = []){
            $this->param = $param;
            $this->post = $_POST;
            $this->dataTransform();
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
         * @param int $code
         * @param string $msg
         * @param string $data
         */
        protected function json($code = 200, $msg = '操作成功', $data = ''){
            if((!is_int($code)) && strlen($code)>4) {
                $return['code'] = 200;
                $return['msg'] = '操作成功';
                $return['data'] = $code;
                echo json_encode($return, JSON_UNESCAPED_UNICODE);
            }else{
                $return['code'] = $code;
                $return['msg'] = $msg;
                $return['data'] = $data;
                echo json_encode($return, JSON_UNESCAPED_UNICODE);
            }
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