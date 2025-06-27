CHANGELOG
=========

### v2.0.4 `2025-06-23`
* 修改`LiCrypt`加密`iv`参数值为随机产生，并放于密文头部
* 增加数据库条件支持 `['field'=>['BETWEEN', 123, 456]]` 

### v2.0.3 `2025-06-20`
* Db::Create 修改了参数, 引用到此类的扩展需对应修改：/app/framework/extend/Db.php
* 增加`PgSql`数据库支持

### v2.0.2 `2025-06-14`
* 修复 SnowFlake 类型错误 bug
* 增加 Logger，日志记录标准类
* Template init() 第一个参数改传模版文件根路径

### v2.0.1 `2025-06-05`
* 从`unntech/litephp`引用版本, 优化PHP8强类型（严格模式），更多使用PHP8的新特性
* Template 模板load时无参数则为PHP脚本相同路径的htm模版文件
* mongodb 也实现统一链式格式对象化增删改查
* LiCrypt `getToken` 增加 `salt` 参数，增加验签安全性