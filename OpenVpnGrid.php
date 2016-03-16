<?php

namespace vbazovic\openvpngrid;

use Yii;
use yii\grid\GridView;
use yii\base\InvalidConfigException;
use yii\data\ArrayDataProvider;
use yii\web\BadRequestHttpException;

class OpenVpnGrid extends GridView {

    public $vpn_name;
    public $vpn_host;
    public $vpn_port;
    public $vpn_password;
    protected $err_no;
    protected $err_str;
    protected $items;
    public $sort_attributes;
    public $labels;
    public $vpn_name;
    public $keys;

    protected function handleError() {        
        throw new BadRequestHttpException(Yii::t('app','Status not returned.')."\n\n".'Details:'."\n".'error number - '.$this->err_no."\n".'error string - '.$this->err_str); 
    }
    
    protected function makeDataProvider() {
        $first_key = $this->keys[0];

        return new ArrayDataProvider([
            'key' => $first_key,
            'allModels' => $this->items,
            'sort' => [
                'attributes' => $this->sort_attributes,
            ],
        ]);
        ;
    }

    protected function makeColumns() {
        $attrib = ['class' => 'yii\grid\SerialColumn'];
        foreach ($this->keys as $index => $key) {
            $attrib[] = ['attribute' => $key, 'label' => $this->labels[$index]];
        }
        return $attrib;
    }

    public function killUser($id) {
        if (!empty($this->vpn_host))
            return false;

        // -----------------------------
        $fp = @fsockopen($this->vpn_host, $this->vpn_port, $this->err_no, $this->err_str, 3);
        if (!$fp) {
            return false;
        }

        fwrite($fp, $this->vpn_password . "\n\n\n");
        usleep(250000);
        fwrite($fp, "kill " . $id . "\n\n\n");
        usleep(250000);
        fwrite($fp, "quit\n\n\n");
        usleep(250000);
        while (!feof($fp)) {
            $line = fgets($fp, 128);
            if (strpos($line, $id) !== false) {
                if (strpos($line, 'SUCCESS') === false) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }

    protected function getStatusServer() {
        if (empty($this->vpn_host)) {
            $this->err_no = 1000;
            $this->err_str = Yii::t('app', 'Host not specified');
            return ['result' => false, 'name' => $this->vpn_name];
        }

        $fp = @fsockopen($this->vpn_host, $this->vpn_port, $this->err_no, $this->err_str, 3);
        if (!$fp) {
            return ['result' => false, 'name' => $this->vpn_name];
        }

        fwrite($fp, $this->vpn_password . "\n\n\n");
        usleep(250000);
        fwrite($fp, "status\n\n\n");
        usleep(250000);
        fwrite($fp, "quit\n\n\n");
        usleep(250000);
        $clients = [];
        $inclients = $inrouting = false;
        while (!feof($fp)) {
            $line = fgets($fp, 128);
            if (substr($line, 0, 13) == "ROUTING TABLE") {
                $inclients = false;
            }
            if ($inclients) {
                $cdata = explode(',', $line);
                $clines[$cdata[1]] = [$cdata[2], $cdata[3], $cdata[4]];
            }
            if (substr($line, 0, 11) == "Common Name") {
                $inclients = true;
            }

            if (substr($line, 0, 12) == "GLOBAL STATS") {
                $inrouting = false;
            }
            if ($inrouting) {
                $routedata = explode(',', $line);
                array_push($clients, array_merge($routedata, $clines[$routedata[2]]));
            }
            if (substr($line, 0, 15) == "Virtual Address") {
                $inrouting = true;
            }
        }

        fclose($fp);
 
        $result = [];
        foreach ($clients as $index => $client) {
            $result[] = array_combine($this->keys, $client);
        }

        return ['result' => true, 'items' => $result, 'clients' => $clients, ];
    }

    public function init() {
        parent::init();

        if ($this->vpn_name === NULL) {
            $this->vpn_name = 'OpenVPN Grid';
        }
        if ($this->vpn_host === NULL) {
            $this->vpn_host = 'localhost';
        }
        if ($this->vpn_port === NULL) {
            $this->vpn_port = 7505;
        }
        if ($this->vpn_password === NULL) {
            $this->vpn_password = '';
        }
        
        $this->err_no = 0;
        $this->err_str = '';        
        
        if ($this->keys == NULL) {
            $this->keys = ['vpn_address', 'name', 'address', 'last_act', 'recv', 'sent', 'since'];
        }
        
        if ($this->labels == NULL) {
            $this->labels = ['VPN Address', 'Name', 'Real Address', 'Last Act', 'Recv', 'Sent', 'Connected Since'];
        }
        
        $result = $this->getStatusServer();       
        
        if ($result['result']) {
            $this->items = $result['items'];
        } else {
            $this->handleError();
        }
        
        if (empty($this->items)) {
            throw new InvalidConfigException('The "items" property must be set.');
        }
        
        if (empty($this->keys)) {
            throw new InvalidConfigException('The "keys" property must be set.');
        }
        
        if (empty($this->labels)) {
            throw new InvalidConfigException('The "labels" property must be set.');
        }

        if ($this->sort_attributes == NULL) {
            $this->sort_attributes = $this->keys;
        }

        $this->columns = $this->makeColumns();
        $this->dataProvider = $this->makeDataProvider();

        parent::init();
    }

    public function run() {

        Yii::setAlias('@vbazovic', '@vendor/vbazovic');
        parent::run();
    }

}
