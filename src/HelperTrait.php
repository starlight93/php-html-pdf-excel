<?php

namespace Starlight93\HtmlPdfExcel;

trait HelperTrait
{
  function data_sum( array $data ){
    $res = 0;
    foreach ($data as $dt) {
      if (is_numeric($dt)) {
        $res += $dt;
      }
    }
    return $res;
  }
}
