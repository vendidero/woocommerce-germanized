<?php

namespace baltpeter\Internetmarke;

class StampPngResult extends ApiResult {
  /**
   * @var string url to zipfile
   */
  public $link;
  /**
   * @var integer portokasse balance in eurocent
   */
  public $walletBallance;
  /**
   * @var stdClass with attributes `shopOrderId` and `voucherList`
   */
  public $shoppingCart;
  /**
   * @var string url to manifestLink
   */
  public $manifestLink;

  /**
   *
   * @param string $link           url to zipfile
   * @param integer $walletBallance portokasse balance in eurocent
   * @param stdClass $shoppingCart   with attributes `shopOrderId` and `voucherList`
   * @param string url to posting receipt of order
   */
  public function __construct($link, $walletBallance, $shoppingCart, $manifestLink = null) {
      $this->link           = $link;
      $this->walletBallance = $walletBallance;
      $this->shoppingCart   = $shoppingCart;
      $this->manifestLink   = $manifestLink;
  }

  /**
   * Store zip and png files in folder of $path
   * @param string $path location where png should be extracted to
   * @return array on success: filenames of png's that were extracted
   *               on failure: if copy of zip file failed false,
   *                           if zip coulnd not be opened the error code
   */
  public function unzipPNG($path)
  {
      // make sure $path ends with slash
      $path = rtrim($path, '/') . '/';

      if(!file_exists($path)){
        mkdir($path);
      }

      $tempFile = $path . 'zip' . date('Y-m-d_H:i:s'). '_' . uniqid();

      if(!copy($this->link,$tempFile)) return false;

      $zip        = new \ZipArchive();
      $zip_result = $zip->open($tempFile);

      if($zip_result !== true) return $zip_result;

      $file_count = $zip->count();
      $files = [];
      for ($i=0; $i < $file_count; $i++) {
        $data     = $zip->getFromIndex($i);
        $filename = date('Y-m-d_H:i:s'). '_' . uniqid() . '.png';
        file_put_contents($path . $filename, $data);
        $files[]  = $filename;
      }

      unlink($tempFile);
      return $files;
  }

}
