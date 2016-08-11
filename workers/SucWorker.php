<?php
namespace kra\workers;

use kra\PageEvent;

class SucWorker extends Worker
{
  public $b_code;
  public $b_type;
  public $data;

  protected $s_plus=[];
  protected $s_minus=[];

  public function run(){
    $params=[
      'b_code'=>$this->b_code,
      'b_type'=>$this->b_type,
      'is_from_main'=>'true',
    ];
    try{
      $view=$this->view($params);
      $this->data['yega']=$this->yega($view);
      $this->data['multispare']=$this->multispare($view);
      $this->data['selms']=$this->selms($view);

      $query=[
        'b_code'=>$this->b_code,
        'page'=>1,
        'select_method'=>'20',
        'currency'=>'KRW',
        'status'=>'30',
      ];
      $list=$this->list_result($query);
      $last=$this->last($list);
      if(empty($last)) $last=1;
      $this->trigger(PageEvent::EVENT_PAGE,new PageEvent(['last'=>$last,'page'=>$page]));

      for($page=1; $page<=$last; $page++){
        if($page>1){
          $query['page']=$page;
          $list=$this->list_result($query);
        }
        $this->succoms($list);
        $this->trigger(PageEvent::EVENT_PAGE,new PageEvent(['last'=>$last,'page'=>$page]));
        sleep(1);
      }

      $this->data['innum']=count($this->data['succoms']);

      //rank
      $i=1;
      foreach($this->s_plus as $seq){
        $this->data['succoms'][$seq]['rank']=$i;
        if($i==1){
          $this->data['officeno1']=$this->data['succoms'][$seq]['officeno'];
          $this->data['officenm1']=$this->data['succoms'][$seq]['officenm'];
          $this->data['prenm1']=$this->data['succoms'][$seq]['prenm'];
          $this->data['success1']=$this->data['succoms'][$seq]['success'];
        }
        $i++;
      }
      $i=count($this->s_minus)*-1;
      foreach($this->s_minus as $seq){
        $this->data['succoms'][$seq]['rank']=$i;
        $i++;
      }

      return $this->data;
    }
    catch(\Exception $e){
      throw $e;
    }
  }

  public function view($params){
    $html=$this->post('/res/all/view.do',$params);
    $html=strip_tags($html,'<th><tr><td>');
    $html=preg_replace('/<tr[^>]*>/','<tr>',$html);
    $html=preg_replace('/<th[^>]*>/','<th>',$html);
    $html=str_replace('&nbsp;',' ',$html);
    return $html;
  }

  public function list_result($query){
    $html=$this->get('/res/result/bd_list_result_company_2.do',$query);
    $html=strip_tags($html,'<tr><td><a>');
    $html=preg_replace('/<tr[^>]*>/','<tr>',$html);
    $html=preg_replace('/<td[^>]*>/','<td>',$html);
    $html=str_replace('&nbsp;',' ',$html);
    return $html;
  }

  public function last($html){
    $p='#<a[^>]*page=(?<last>\d+)[^>]*btns last_page[^>]*>#';
    return static::match($p,$html,'last');
  }

  public function yega($html){
    $p='#<th>예정가격</th> <td> (?<yega>\d{1,3}(,\d{3})*) </td>#';
    $yega=static::match($p,$html,'yega');
    return str_replace(',','',$yega);
  }

  public function multispare($html){
    $p='#<td[^>]*>(?<no>\d+)</td> <td[^>]*> (?<price>\d{1,3}(,\d{3})*) </td> <td[^>]*> \d* </td>#';
    $p=str_replace(' ','\s*',$p);
    $arr=[];
    if(preg_match_all($p,$html,$matches,PREG_SET_ORDER)){
      foreach($matches as $m){
        $arr[$m['no']]=str_replace(',','',$m['price']);
      }
    }
    ksort($arr);
    return join('|',$arr);
  }

  public function selms($html){
    $p='#<td class=\'bid_mark\'>(?<no>\d+)</td> <td class=\'bid_mark\'> \d{1,3}(,\d{3})* </td> <td class=\'bid_mark\'> \d* </td>#';
    $p=str_replace(' ','\s*',$p);
    $arr=[];
    if(preg_match_all($p,$html,$matches,PREG_SET_ORDER)){
      foreach($matches as $m){
        $arr[]=$m['no'];
      }
    }
    sort($arr);
    return join('|',$arr);
  }

  public function succoms($html){
    $p='#<tr>'.
      ' <td>(?<seq>\d+)</td>'.
      ' <td>(?<officeno>[^<]*)</td>'.
      ' <td>(?<officenm>[^<]*)</td>'.
      ' <td>(?<prenm>[^<]*)</td>'.
      ' <td>(?<success>[^<]*)</td>'.
      ' <td>(?<pct>[^<]*)</td>'.
      ' <td>(?<point>[^<]*)</td>'.
      ' <td>(?<etc>[^<]*)</td>'.
      ' </tr>#';
    $p=str_replace(' ','\s*',$p);
    if(preg_match_all($p,$html,$matches,PREG_SET_ORDER)){
      foreach($matches as $m){
        $row=[
          'seq'=>trim($m['seq']),
          'officenm'=>trim($m['officenm']),
          'prenm'=>trim($m['prenm']),
          'officeno'=>trim(str_replace('-','',$m['officeno'])),
          'success'=>trim(preg_replace('/[,\*]/','',$m['success'])),
          'pct'=>trim(preg_replace('/[%\*]/','',$m['pct'])),
          'etc'=>trim($m['etc']),
        ];
        $this->data['succoms'][$row['seq']]=$row;
        switch($row['etc']){
        case '미달':
          $this->s_minus[]=$row['seq'];
          break;
        default:
          $this->s_plus[]=$row['seq'];
        }
      }
    }
  }
}

