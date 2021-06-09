# thinkphp6 plugin for AutoDesk Forge

> 实现DWG,DWF,RVT等文件的在线预览功能

## 安装
```
composer require bingher/forge
```

## 配置

配置文件`config/forge.php`
```
<?php
return [
    'id'                => 'autodesk forge client id',
    'secret'            => 'autodesk forge secret',
    'prepend_bucketkey' => true,
    'scope_internal'    => ['bucket:create', 'bucket:read', 'data:read', 'data:create', 'data:write'],
    'scope_public'      => ['data:read'],
    'bucket'            => 'default',
];
```

## 示例
请看demo目录控制器及视图模板

## 建议
文件上传后,后端将文件上传到forge的oss,执行转换后返回urn记录到文件信息,预览采用demo的view方法通过get传参进行访问展示

## 参考
- [https://forge.autodesk.com/](https://forge.autodesk.com/)
- [https://learnforge.autodesk.io/](https://learnforge.autodesk.io/)
- [https://forge.autodesk.com/en/docs/viewer/v7/developers_guide/overview/](https://forge.autodesk.com/en/docs/viewer/v7/developers_guide/overview/)
