<?php
namespace kra\watchers;

class SucWatcher extends Watcher
{
  const URL='/res/all/list.do';

  public function watch($start,$end,$callback){
    $params=[
      'is_form_main'=>'true',
      'page'=>1,
      'open_date_from'=>$start,
      'open_date_to'=>$end,
    ];
    try{
      $html=$this->html($params);
      $last=$this->last($html);
      if(empty($last)) throw new \Exception('총 페이지를 찾을 수 없습니다.');

      for($page=1; $page<=$last; $page++){
        if($page>1){
          $params['page']=$page;
          $html=$this->html($params);
        }
        $this->matchAll($html,$callback);
        sleep(1);
      }
    }
    catch(\Exception $e){
      throw $e;
    }
  }

  public function html($params){
    $html=$this->post(static::URL,$params);
    $html=strip_tags($html,'<tr><td><a>');
    $html=preg_replace('/<tr[^>]*>/','<tr>',$html);
    $html=preg_replace('/<td[^>]*>/','<td>',$html);
    return $html;
  }

  public function last($html){
    $p='#<a[^>]*page=(?<last>\d+)[^>]*btns last_page[^>]*>#';
    return static::match($p,$html,'last');
  }

  public function pattern(){
    $p='#<tr>'.
      ' <td>\d+</td>'.
      ' <td>KRA(?<code>\d+)</td>'.
      ' <td>(?<constnm>[^<]*)</td>'.
      ' <td>[^<]*</td>'.
      ' <td>[^<]*</td>'.
      ' <td>[^<]*</td>'.
      ' <td>(?<bidproc>[^<]*)</td>'.
      ' <td>[^<]*</td>'.
      ' </tr>#';
    return str_replace(' ','\s*',$p);
  }

  public function matchAll($html,$callback){
    $html=strip_tags($html,'<tr><td>');
    $pattern=$this->pattern();
    if(preg_match_all($pattern,$html,$matches,PREG_SET_ORDER)){
      foreach($matches as $m){
        $row=[
          'code'=>$m['code'],
          'notinum'=>'KRA'.$m['code'],
          'constnm'=>trim($m['constnm']),
          'bidproc'=>trim($m['bidproc']),
        ];
        $callback($row);
      }
    }
  }
}

