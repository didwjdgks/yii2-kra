<?php
namespace kra\controllers;

use yii\helpers\Console;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;

use kra\PageEvent;
use kra\models\BidKey;

class WorkController extends \yii\console\Controller
{
  public function stdout2($string){
    $this->stdout(Console::renderColoredString($string));
  }

  public function memory_usage(){
    $this->stdout(sprintf("[%s] Peak memory usage: %s Mb\n",
      date('Y-m-d H:i:s'),
      (memory_get_peak_usage(true)/1024/1024))
    ,Console::FG_GREY);
  }

  public function progress($event){
    if($event->page==1){
      Console::startProgress(0,$event->last,'참여업체:');
    }
    Console::updateProgress($event->page,$event->last);
    if($event->page==$event->last){
      Console::endProgress();
    }
  }

  public function actionSuc(){
    $w=new \GearmanWorker;
    $w->addServers($this->module->gman_server);
    $w->addFunction('kra_work_suc',function($job){
      $workload=Json::decode($job->workload());
      $this->stdout2("마사회> [개찰] {$workload['notinum']} {$workload['constnm']} ({$workload['bidproc']})");
      if($workload['bidproc']=='입찰취소'){
      }
      $this->stdout2("\n");

      $worker=new \kra\workers\SucWorker([
        'b_code'=>$workload['code'],
        'b_type'=>'1',
      ]);
      $worker->on(PageEvent::EVENT_PAGE,[$this,'progress']);
      $data=$worker->run();
      print_r($data);
    });
    while($w->work());
  }
}

