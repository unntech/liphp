
LiPhp 2.0
===============

[![Latest Stable Version](https://poser.pugx.org/unntech/liphp/v/stable)](https://packagist.org/packages/unntech/liphp)
[![Total Downloads](https://poser.pugx.org/unntech/liphp/downloads)](https://packagist.org/packages/unntech/liphp)
[![Latest Unstable Version](http://poser.pugx.org/unntech/liphp/v/unstable)](https://packagist.org/packages/unntech/liphp)
[![PHP Version Require](http://poser.pugx.org/unntech/liphp/require/php)](https://packagist.org/packages/unntech/liphp)
[![License](https://poser.pugx.org/unntech/liphp/license)](https://packagist.org/packages/unntech/liphp)

LiPhp的公共库，需先创建LiApp 或LiteSwoole项目使用



## 主要新特性

* 采用`PHP8`强类型（严格模式）
* 支持更多的`PSR`规范
* 原生多应用支持
* 对IDE更加友好
* 统一和精简大量用法


> LitePhp 2.0的运行环境要求PHP8.1+

## 安装

~~~
composer require unntech/liphp
~~~


如果需要更新框架使用
~~~
composer update unntech/liphp
~~~

目录结构
~~~
litephp/
├── lib                                     #类库
│   ├── phpqrcode.php                       #二维码类库
├── src                                     #
│   ├── gif                                 #gif图像类库
│   ├── Library                             #共用类库文件
│   ├── Models                              #数据集模型类
│   ├── Config.php                          #Config类
│   ├── CorpWeixin.php                      #企业微信消息推送加解密类库
│   ├── Db.php                              #数据库实例类
│   ├── Exception.php                       #异常类继承方法
│   ├── GoogleAuthenticator.php             #Google二次验证类
│   ├── Image.php                           #图像处理类库
│   ├── LiComm.php                          #常用函数方法
│   ├── LiCrypt.php                         #jwt类库，自定义token加解密
│   ├── LiHttp.php                          #Http基础类，curl
│   ├── LiRegular.php                       #常用正则
│   ├── Lite.php                            #Lite基础类
│   ├── MongoDB.php                         #MongoDB操作对象类
│   ├── Mysqli.php                          #Mysql操作对象类
│   ├── Model.php                           #Model模型基础类
│   ├── qrCode.php                          #二维码生成类
│   ├── Redis.php                           #Redis静态实例类
│   ├── Session.php                         #Session类
│   ├── SnowFlake.php                       #雪花生成64位int
│   ├── SqlSrv.php                          #mssql server 操作对象类
│   ├── Template.php                        #视图模板文件载入类
│   ├── Tree.php                            #树型通用类
│   ├── UUID.php                            #UUID生成器
│   ├── Validate.php                        #常用数据验证器
│   ├── Weixin.php                          #微信消息基础类
├── tests                                   #测试样例，可删除
├── composer.json                           #
└── README.md
~~~

## 文档

[完全开发手册](#)

## 命名规范

`LiPhp`遵循PSR命名规范和PSR-4自动加载规范。

## 参与开发

直接提交PR或者Issue即可  
> [版本更新记录 CHANGELOG](CHANGELOG.md)

## 版权信息

LiPhp遵循MIT开源协议发布，并提供免费使用。

本项目包含的第三方源码和二进制文件之版权信息另行标注。

版权所有Copyright © 2025 by Jason Lin All rights reserved.

