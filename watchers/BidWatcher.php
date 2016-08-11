<?php
namespace kra\watchers;

use yii\helpers\ArrayHelper;

use kra\PageEvent;

class BidWatcher extends Watcher
{
  const URL='/bid/notice/all/list.do';

  protected $rows;

  public function html($params){
    $html=$this->post(static::URL,$params);
    $html=strip_tags($html,'<tr><td><a><input>');
    $html=preg_replace('/<tr[^>]*>/','<tr>',$html);
    $html=preg_replace('/<td[^>]*>/','<td>',$html);
    return $html;
  }

  public function watch($start,$end){
    $params=[
      'noticeDateFrom'=>$start,
      'noticeDateTo'=>$end,
      'pageIndex'=>1,
    ];
    try{
      $html=$this->html($params);
      $last=$this->last($html);
      if(empty($last)) throw new \Exception("총 페이지를 찾을 수 없습니다.");
      $this->trigger(PageEvent::EVENT_PAGE,new PageEvent(['last'=>$last,'page'=>$page]));

      for($page=1; $page<=$last; $page++){
        if($page>1){
          $params['pageIndex']=$page;
          $html=$this->html($params);
        }
        $rows=$this->matchAll($html);
        $this->rows=ArrayHelper::merge($this->rows,$rows);
        sleep(1);
        $this->trigger(PageEvent::EVENT_PAGE,new PageEvent(['last'=>$last,'page'=>$page]));
      }

      ArrayHelper::multisort($this->rows,['notinum'],[SORT_ASC]);
      return $this->rows;
    }
    catch(\Exception $e){
      throw $e;
    }
  }

  public function last($html){
    $p='#<a.*listLink[^>]*>(?<last>\d+)</a> <input id="pageIndex"[^>]*>#';
    return static::match($p,$html,'last');
  }

  public function pattern(){
    $p='#<tr>'.
      ' <td>\d+</td>'.
      ' <td>KRA(?<code>\d+)</td>'.
      ' <td>[^<]*</td>'.
      ' <td>(?<constnm>[^<]*)</td>'.
      ' <td>[^<]*</td>'.
      ' <td>[^<]*</td>'.
      ' <td>[^<]*</td>'.
      ' <td>[^<]*</td>'.
      ' <td>(?<bidproc>[^<]*)</td>'.
      ' </tr>#';
    return str_replace(' ','\s*',$p);
  }

  public function matchAll($html){
    $rows=[];
    $html=strip_tags($html,'<tr><td>');
    $pattern=$this->pattern();
    if(preg_match_all($pattern,$html,$matches,PREG_SET_ORDER)){
      foreach($matches as $m){
        $code=$m['code'];
        $constnm=trim($m['constnm']);
        $notinum='KRA'.$code;
        $bidproc=trim($m['bidproc']);

        $rows[$notinum]=[
          'code'=>$code,
          'notinum'=>$notinum,
          'constnm'=>$constnm,
          'bidproc'=>$bidproc,
        ];
      }
    }
    return $rows;
  }
}

