<?php
namespace kra;

use yii\db\Connection;
use yii\di\Instance;

class Module extends \yii\base\Module
{
  public $db='db';

  public $gman_server;

  protected $gman_client;
  protected $gman_talk;

  public function init(){
    parent::init();

    $this->db=Instance::ensure($this->db,Connection::className());

    $this->gman_client=new \GearmanClient;
    $this->gman_client->addServers($this->gman_server);

    $this->gman_talk=new \GearmanClient;
    $this->gman_talk->addServers('115.68.48.242');
  }

  public function gman_do($func,$data){
    if(is_array($data)) $data=\yii\helpers\Json::encode($data);
    $this->gman_client->doNormal($func,$data);
  }

  public function send_talk($msg,$recv=149){
    $msg='마사회> '.$msg;
    if(!is_array($recv)) $recv[]=$recv;
    foreach($recv as $id){
      $this->gman_talk->doBackground('send_chat_message_from_admin',Json::encode([
        'recv_id'=>$id,
        'message'=>$msg,
      ]));
    }
  }
}

