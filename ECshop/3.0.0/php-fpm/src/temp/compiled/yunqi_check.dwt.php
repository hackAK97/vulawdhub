<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en"><head>
<meta name="Generator" content="ECSHOP v3.0.0" />
  <meta charset="UTF-8">
  <title>Eshop</title>
  <link href="admin/styles/login.css" rel="stylesheet" type="text/css" />
  <style>
    html,body{height: 100%;}
  </style>
</head>
<body>
<div class="cloud_installbg">
  
  <div class="ecshop-logo">
    <img src="admin/images/ecshop-logo.png" alt="">
  </div>
  <!--<div class="login-hd">-->
    <!--<img src="admin/images/shopex.png" alt="shopex" class="logo">-->
  <!--</div>-->
  <div class="panel-hint panel-icloud" id="panelCloud">
    <div class="panel-cross"><a href="install/index.php?step=done">Ｘ</a></div>
    <div class="panel-title">
      <span class="tit">您需要激活系统</span>
      <p>用云起账号激活您的系统，享受物流查询，天工收银，手机短信等更多应用和服务</p>
    </div>
    <div class="panel-left">
      <span>没有云起账号吗？</span>
      <p>点击下列按钮一步完成注册激活！</p>
      <a href="https://account.shopex.cn/reg?refer=yunqi_ecshop" target="_blank" class="btn btn-yellow">免费注册云起账号</a>
    </div>
    <div class="panel-right">
      <h5 class="logo">云起</h5>
      <p>我有云起账号</p>
      <iframe src="<?php echo $this->_var['iframe_url']; ?>" frameborder="0"></iframe>
      <div class="cloud-passw">
        <a target="_blank" href="https://account.shopex.cn/forget?">忘记密码？</a>
      </div>
    </div>
  </div>
  
</div>
</body>
</html>