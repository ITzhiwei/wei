# wei
框架核心部分-其余部分独立放composer
# 访问入口
程序唯一入口：public/index.php  
# 路由（配置文件：config/route.php）
自动路由 + 可配置伪静态 + 自定义路由设置  
# 项目入口(配置文件也是 config/route.php )
项目默认一级目录入口：application，可以根据多个域名配置多个一级目录入口；也可以自定义一级目录入口
```
//路径映射；第一个键名不可更改，只能是index，其他键名和键值都可以任意修改；
'door' => [
          'index'=>__DIR__.'/../application/',
          'application2'=>__DIR__.'/../application2/',
          'app3'=>__DIR__.'/../app3/',
          ],
//域名指定一级目录入口，不同的域名对应不同的入口；不同的项目共享资源环境
'urlEntranceData' => [
    //所有域名默认入口是 index； 对应的是 door 中的 index 所映射的目录入口
    //指定入口，从 door 中映射路径，可以设为 door 中存在的任意一个键名
    'www.wei.com'=>'application2'
 ]，
 /*
  * 自定义一级目录入口，使一个域名拥有多个入口；
  * 若使用自定义入口，url的路由参数是4个，如非自定义是 index/user/info/id/1 自定义是 selectApp2/index/user/info/id/1
  * 第一个值是布尔值，为false时表示只针对某些域名，为 true 时表示所有域名都生效
  * 第2个值是入口（从 door 中映射目录）
  * 第3个值是数组，当第一个值是 false 才有效，表示哪些域名启用这额外的项目
  */，
 'assignEntranceData'    => [
        //解释如：selectApp2：当遇到 www.xxx.com/selectApp2(或www.xxx.comselectApp2/) 的url时触发检测
        'selectApp2'=>[false, 'application2', ['www.wei.com', 'www.weib.com', 'www.weic.com']],
        //所有域名在遇到 www.url.com/app3/ 开头的url时，会直接选定 app3 入口
        'lipowei'=>[true, 'app3']
  ]
```
# 工厂自助管理类
自助单例管理类，可设置别名重新实例化  
在获取类时，使用 wei\Factory::get($className) 可进行注入并自动单例管理，可用别名new新的类如::get($className , 'two')   
# 控制器基类
json 统一返回功能  
数据转换功能方法  
指定特殊字段自动转化  
view文件调用  
用户请求结束后在服务器继续执行相关代码（在子控制器类中把相关代码以匿名函数方式放进 $this->fucArr 即可）
````
$this->fucArr[] = function() use ($phoneArr){
                        //用户请求结束后，服务器继续执行耗时的任务
                        ...
                  }
````
# 配置类
安装 composer require lipowei/config   
可获取 vendor 同级目录下 config 目录任意配置文件的参数   
# Db 操作库
安装 composer require lipowei/db  
支持分布式、读写分离等模式  
全面预处理、自动添加反引号  
mysql 错误日志记录  
# php 错误日志记录
与 mysql 错误分别记录不同的 log 文件
