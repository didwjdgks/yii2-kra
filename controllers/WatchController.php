<?php
namespace kra\controllers;

use yii\helpers\Console;

use kra\PageEvent;
use kra\models\BidKey;

class WatchController extends \yii\console\Controller
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
      Console::startProgress(0,$event->last);
    }
    Console::updateProgress($event->page,$event->last);
    if($event->page==$event->last){
      Console::endProgress();
    }
  }

  public function actionBid(){
    $bid=new \kra\watchers\BidWatcher;
    $bid->on(PageEvent::EVENT_PAGE,[$this,'progress']);

    while(true){
      $start=date('Y-m-d',strtotime('-1 month'));
      $end=date('Y-m-d');

      try{
        $rows=$bid->watch($start,$end);
        foreach($rows as $row){
          $this->stdout2("마사회> [입찰] {$row['notinum']} {$row['constnm']} ({$row['bidproc']})");
          $bidkey=BidKey::findBid($row);
          if($bidkey===null){
            $this->stdout2(" %gNEW%n\n");
            continue;
          }
          $this->stdout2("\n");
        }
      }
      catch(\Exception $e){
        $this->stdout("$e\n",Console::FG_RED);
        \Yii::error($e,'kra');
      }

      $this->module->db->close();
      $this->memory_usage();
      sleep(mt_rand(3,6));
    }
  }

  /**
   * 낙찰정보 watch
   */
  public function actionSuc(){
    $suc=new \kra\watchers\SucWatcher;

    while(true){
      $start=date('Y-m-d',strtotime('-15 day'));      
      $end=date('Y-m-d');

      try{
        $suc->watch($start,$end,function($row){
          $this->stdout2("마사회> [낙찰] {$row['notinum']} {$row['constnm']} ({$row['bidproc']})");
          $bidkey=BidKey::findSuc($row);
          if($bidkey===null){
            $this->stdout2("\n %r> ERROR: 입찰공고가 없습니다.%n\n");
            return;
          }
          if($row['bidproc']=='입찰취소'){
            if($bidkey->bidproc!='C'){
              $this->stdout2(" %ybidproc='{$bidkey->bidproc}'%n\n");
              $this->stdout2(" %g> 취소공고 처리를 요청합니다.%n\n");
              return;
            }
          }
          else if($row['bidproc']=='유찰'){
            if($bidkey->bidproc!='F'){
              $this->stdout2(" %ybidproc='{$bidkey->bidproc}'%n\n");
              $this->stdout2(" %g> 유찰처리를 요청합니다.%n\n");
              return;
            }
          }
          else{
            if($bidkey->bidproc!='S'){
              $this->stdout2(" %ybidproc='{$bidkey->bidproc}'%n\n");
              $this->stdout2(" %g> 개찰처리를 요청합니다.%n\n");
              return;
            }
          }
          $this->stdout2("\n");
        });
      }
      catch(\Exception $e){
        $this->stdout("$e\n",Console::FG_RED);
        \Yii::error($e,'kra');
      }

      $this->module->db->close();
      $this->memory_usage();
      sleep(mt_rand(3,6));
    }
  }
}

