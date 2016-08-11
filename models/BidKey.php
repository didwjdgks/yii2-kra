<?php
namespace kra\models;

class BidKey extends \i2\models\BidKey
{
  public static function getDb(){
    return \kra\Module::getInstance()->db;
  }

  public static function findBid($row){
    $query=static::find()->where([
      'whereis'=>'93',
      'notinum'=>$row['notinum'],
    ]);
    $bidkey=$query->orderBy('bidid desc')->limit(1)->one();

    return $bidkey;
  }

  public static function findSuc($row){
    return static::findBid($row);
  }
}

