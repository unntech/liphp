CHANGELOG
=========

### v2.0.2 `2025-06-14`
* 修复 SnowFlake 类型错误 bug

### v2.0.1 `2025-06-05`
* 从`unntech/litephp`引用版本, 优化PHP8强类型（严格模式），更多使用PHP8的新特性
* Template 模板load时无参数则为PHP脚本相同路径的htm模版文件
* mongodb 也实现统一链式格式对象化增删改查
* LiCrypt `getToken` 增加 `salt` 参数，增加验签安全性