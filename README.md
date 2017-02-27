#简介
YurunPHP是一款MVC开源PHP框架，它的一切都是根据实际项目需求总结归纳而出，力争减少开发者在项目开发中的重复工作量。它经历了许多大小项目的考验（其中不乏年销售额上亿的系统），不断完善改进，已经十分成熟。

YurunPHP框架支持PHP 5.3-7.x 版本，兼容 Windows / Linux 。

YurunPHP 2.x 开发手册：http://www.kancloud.cn/yurun/yurunphp2

YurunPHP 1.x 开发手册：http://www.kancloud.cn/yurun/php-framework-1_0

YurunPHP Demo 开源项目 YurunBlog

Coding：https://coding.net/u/yurunsoft/p/YurunBlog/git

Github仓库：https://github.com/Yurunsoft/YurunBlog

YurunPHP最新消息都会在官网以及QQ中发布，欢迎关注，共同学习进步！

宇润PHP技术交流群：74401592

#YurunPHP 2

* 进行了小范围重构，减少内存占用，少量提升性能
* 命令行CLI模式支持
* 支持以配置形式配置多种缓存、配置、日志、数据库
* 初始化项目初始化处理文件
* 配置驱动：Ini、JSON、XML、Db
* 缓存驱动：APC、APCu、Db、EAccelerator、Memcache、Memcached、Redis、WinCache、XCache
* 日志驱动：Db
* 支持将Session保存至数据库
* 新增一个XML转换类

#特色功能

> * **双向路由：**解析、生成一步到位
> * **动态分层架构：**除了常见的Control、Model、View，开发者还可通过配置文件自定义分层
> * **多入口：**满足各类开发者的需要
> * **API接口开发：**内置API接口控制器，支持直接返回包括json、xml等格式的各类数据
> * **简单ORM：**单表增删改查不需要写SQL语句，复杂ORM不如写SQL更为便捷
> * **DB统一接口：**可以不改任何代码，实现更换数据库类型切换。目前支持MYSQL/MSSQL
> * **多数据库自由切换：**配置文件中可以配置多个数据库连接，在需要时连接，自由切换
> * **模版引擎：**YurunPHP内置了一个简单的模版引擎。采用html标签式的标签，方便不懂PHP的设计人员制作页面模版。release模式下模版会被编译，无须担心效率损耗！
> * **模版控件：**内置了许多常用控件，在开启默认模版引擎时可以使用<textbox runat="server" text="yurunphp"/>这样的标签来展示控件，支持数据绑定
> * **插件机制：**支持在系统中埋下事件，以便开发插件扩展
> * **数据验证：**不仅有数据验证类，而且model支持自动验证
> * **定时任务：**可以定时执行一些操作，拥有高度自由的触发时间配置功能
> * **驱动扩展：**内置Redis、Memcache等常用驱动，通过配置文件即可使用
> * **其它：**缓存、配置、数据库、错误日志记录、多语言支持、Session、Cookie、Request、Response

#开源协议
Apache Licence是著名的非盈利开源组织Apache采用的协议。该协议和BSD类似，同样鼓励代码共享和尊重原作者的著作权，同样允许代码修改，再发布（作为开源或商业软件）。