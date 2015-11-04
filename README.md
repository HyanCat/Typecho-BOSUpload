## Typecho-BOSUpload

本插件用于解决部署在 BAE 上的 Typecho 不能上传文章附件和图片的问题，需要用户开通百度开放云存储 (BOS)，价格比较实惠，详情点击[这里][1]。

## 问题来源
由于 BAE 不支持写入的文件读取，所以部署在 BAE 上的 Typecho 的上传图片和附件的功能暂不可用。而 BAE 的静态文件服务由百度开放云 BOS 提供，本插件就基于 BOS 提供的服务实现这一功能。

## 使用
1. 通过右侧的「Download ZIP」下载本插件到本地；
2. 将压缩包解压后，重命名为 `BOSUpload`，并复制到 `usr/plugins`；
3. 将代码 push 到 BAE；
4. 进入网站的后台管理界面，点击「控制台」-> 「插件」;
5. 点击 BOSUpload 右侧的「启用」，跳转到 BOS 信息配置页面；
6. 根据提示，填入自己已经开通的 BOS 的 `Access_Key`, `Secret_Key`，`Bucket_Name`，「绑定域名」这一项可不填；
7. All Ready, 发表一篇带图片的文章吧！

## 注意
- 默认开通区域为「华北-北京」，开通 BOS 为「华南-广州」等其他区域的，请将 `BosService.php` 的[第 15 行][2]网址改为 `http://gz.bcebos.com`（以广州为例）。

## 参考
- [BAE 上安装 typecho][3]
- [BOS 介绍][4]



[1]: http://bce.baidu.com/doc/BOS/Pricing.html
[2]: https://github.com/HyanCat/Typecho-BOSUpload/blob/master/BosService.php#L15
[3]: http://docs.typecho.org/bae-install
[4]: http://bce.baidu.com/doc/BOS/index.html
