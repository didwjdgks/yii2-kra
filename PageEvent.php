<?php
namespace kra;

class PageEvent extends \yii\base\Event
{
  const EVENT_PAGE='page_event';

  public $last;
  public $page;
}

