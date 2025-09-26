<?php
namespace App\Services\Shared;

final class Breadcrumbs
{
  public static function make(array $items): array {
    // normaliza estructura
    return array_map(function($i){
      return [
        'href'  => isset($i['href']) ? (string)$i['href'] : null,
        'label' => (string)$i['label']
      ];
    }, $items);
  }
}
