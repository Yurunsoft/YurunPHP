#更新日志

v2.1 2017.05.15


新增PDO数据库驱动，去除老的数据库驱动支持

新增路由解析和调度两个事件

新增脚本执行完毕事件

新增Response三个方法，方便进行缓存处理

新增模型是否执行查询前置方法

新增获取和生成URL时支持指定scheme

新增YURUN_DISPLAY_BEFORE和YURUN_DISPLAY_AFTER事件

新增自动加载规则支持文件扩展名设置

新增CLI模式下强制使用UTF-8编码

新增import支持多个导入

新增新的链式查询功能

新增模型前置后置方法的链式操作参数

新增支持select控件选中值的判断方式是==还是===

新增SESSION_COOKIE_DOMAIN配置项支持

新增数据库查询失败抛出异常

新增Model查询某一列的方法

新增文件日志add方法支持对象和数组，自动编码为json格式

新增链式操作支持count(distinct field)查询

新增日志配置项LOG_CLI_AUTOSAVE

新增支持Model自动针对字段类型设置PDO字段类型

新增db层bindValue方法

新增Session::once()用法，支持读取后自动删除该数据

新增支持MySQL行锁和表锁

新增支持数据库驱动的锁机制

新增批量插入数据功能(Db+Model)

新增AUTOLOAD_RULES配置支持文件路径设置



优化Session类，不用再为并发或者后台长时间任务会阻塞访问发愁

优化接口控制器输出错误格式

去除不必要的代码

调整YurunPHP框架支持的PHP版本从5.3升为5.4

调整事件参数可以直接传递array数组

调整parseStatic函数

调整YURUN_DISPATCH事件触发时机

调整execute方法返回值为是否执行成功

调整获取当前访问的缓存名结果是md5

调整Model的delete方法默认返回值为影响行数





修复add、edit、delete返回值不正确的问题

修复Request::getHome方法的一些问题

修复linux下的网站，资源文件url可能会有2个/的问题

修复import函数中名称中有.时无法加载文件

修复有些控制台下的Demo路径错误

修复取当前访问的缓存名方法的BUG

修复编译后因import函数导致的各种BUG

修复join方法的错误

修复控制器returnData方法返回xml的错误

修复上传文件有时失败也成功的问题

修复链式操作page的问题

修复一个大小写问题

修复编译后运行报错问题

去除Model的__linkPage方法

修复order连贯操作的BUG

修复Model中__selectOneAfter方法第二个参数没有传

修复有时会触发多个__selectOneAfter事件的问题

修复命令行下编译框架出错的问题

修复count等查询偶现的问题


v2.0.0 Beta 2017.02.06


新版本进行了小范围重构，减少内存占用，少量提升性能

调整目录结构

去除不常用的常量

新增命令行CLI模式支持

新增一个快速获取表记录行数的方法，该值有可能是缓存

新增初始化项目初始化处理文件

新增配置驱动：Ini、JSON、XML、Db

新增缓存驱动：APC、APCu、Db、EAccelerator、Memcache、Memcached、Redis、WinCache、XCache

新增日志驱动：Db

新增数据库Session类，支持将Session保存至数据库

新增一个XML转换类


优化分页查询方法查询效率

优化插件机制


修复不配置数据库连接时使用模型报错的问题

修复访问某些URL时会认为是默认首页的BUG


v1.3 2017.01.02


新增定时任务功能

新增输出语言的模版标签

新增支持过滤域名访问

新增文件缓存默认值可以是回调，返回值是缓存内容

新增模型方法：wherePk，支持多主键智能查询

新增模型方法：save，支持新增修改智能判断

新增支持多数据库配置

新增数据库操作对象管理机制

新增专为接口开发而设计的控制器

新增事件YURUN_MCA_NOT_FOUND、YURUN_CONTROL_EXEC_COMPLETE

新增select控件将text_field设为value可以支持一维数组的数据集

新增select控件支持optgroup

新增根据组名获取数据的数据来源方法参数

新增处理多行文本，替换使用指定换行符换行的函数

新增Textbox控件多行模式

新增Radiogroup和Checkboxgroup支持为一维数组数据集设定值取key还是value

新增Checkboxgroup和Radiogroup支持自定义class


修复访问无法实例化的控制器类不友好报错

修复路由规则带文件名后有斜杠无法双向解析

修复有时候无法删除cookie的问题

修复导入sql文件的一个BUG

修复一个生成url的问题

修复get参数路由执行错误问题

修复路由配置default_mca中action无效的BUG

修复路由规则最后带/有可能无法解析的BUG

修复模型的inc和dec方法bug

修复Response::msg方法无法设置状态码的BUG

修复model的from方法第二次调用后出错的BUG

修复框架中可能存在的并发文件读写冲突问题

修复一个首页路由判断的BUG


优化getDataByGroup和getDataArrayByGroup方法如果组名为空则获取所有数据

优化模版缓存路径

优化生成URL时rule参数传空的处理

优化分页条控件直接设置总页数时不再重新计算总页数

优化去除Textbox控件不必要的属性

优化页面不存在事件的逻辑处理

优化一些事件的参数名


v1.2 2016.05.25


继续完善框架


v1.1 2016.01.12


经过1年多项目的积累沉淀，功能更加完善。正式推出开发手册。


v1.0 Beta 2014.08.21


完成了重新编写代码和构架，正式开源


v0.9 2014.06


做了一些微小的工作


v0.1 2014


第一个开发版