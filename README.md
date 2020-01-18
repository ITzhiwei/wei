# wei
框架核心部分-其余部分独立放composer
# 入口
唯一入口
# 路由
自动路由 + 可配置伪静态
# 工厂类
自助单例管理类，可设置别名重新实例化  
在获取类时，使用 wei\Factory::get($className) 可进行注入并自动单例管理，可用别名new新的类如::get($className , 'two')   
# 控制器基类
json 统一返回功能  
数据转换功能方法、指定特殊字段自动转化  
用户请求结束后在服务器后端继续执行（在子控制器类中传入匿名函数即可）
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
