# yii2-openvpngrid

STILL NOT FOR USE

The OpenVPN managment grid for the Yii framework
Requirements
------------

Enabled management interface on an OpenVPN server or client.

https://openvpn.net/index.php/open-source/documentation/howto.html#control

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist vbazovic/yii2-openvpngrid "*"
```

or add

```
"vbazovic/yii2-openvpngrid": "*"
```

to the require section of your `composer.json` file.


Usage
-----

If in the configuration file of OpenVPN magagment is set (in example this line is assumed: management localhost 7505),
after the extension is installed, simply use it in a view like this :

```php
<?php

use yii\helpers\Html;
use vbazovic\openvpngrid\OpenVpnGrid;

/* @var $this yii\web\View */
/* @var $status array */
$this->title = Yii::t('app', 'OpenVPN');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="openvpn-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?=
    OpenVpnGrid::widget([        
        'vpn_name' => 'My VPN',
        'vpn_host' => 'localhost',
        'vpn_port' => 7505,        
    ]);
    ?>

</div>```