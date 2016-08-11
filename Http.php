<?php
namespace kra;

class Http extends \yii\base\Component
{
  public $client;

  public function init(){
    parent::init();

    $this->client=new \GuzzleHttp\Client([
      'base_uri'=>'http://ebid.kra.co.kr',
      'cookies'=>true,
      'allow_redirects'=>false,
      'headers'=>[
        'User-Agent'=>'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko',
        'Connection'=>'Keep-Alive',
      ],
    ]);
  }

  public function request($method,$uri='',array $options=[]){
    $res=$this->client->request($method,$uri,$options);
    $body=$res->getBody();
    $html=iconv('euckr','utf-8//IGNORE',$body);
    return $html;
  }

  public function post($uri,array $params=[]){
    $html=$this->request('POST',$uri,['form_params'=>$params]);
    return $html;
  }

  public function get($uri,array $query=[]){
    $html=$this->request('GET',$uri,['query'=>$query]);
    return $html;
  }

  public static function match($pattern,$html,$label){
    $p=str_replace(' ','\s*',$pattern);
    $ret='';
    if(preg_match($p,$html,$m)){
      if(is_array($label)){
        $ret=[];
        foreach($label as $v){
          $ret[$v]=trim($m[$v]);
        }
      }else{
        $ret=trim($m[$label]);
      }
    }
    return $ret;
  }
}

