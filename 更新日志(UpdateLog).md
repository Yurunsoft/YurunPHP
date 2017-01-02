#更新日志

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