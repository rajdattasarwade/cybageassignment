<?php

namespace Drupal\mycustomproducts\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Block\BlockBase;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Provides a 'Lend Smart with Five Paisa' block.
 *
 * @Block(
 *   id = "product_qr_code_block",
 *   admin_label = @Translation("Product QR Code"),
 *   category = @Translation("My Custom Products Module Blocks")
 * )
*/
class ProductQRCodeBlock extends BlockBase {

 /**
  * {@inheritdoc}
 */
 public function build() {
  $nid="";
  $productLink = "";
  $productUrl = "";
  $image_url = "";
  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node instanceof \Drupal\node\NodeInterface) {
    // You can get nid and anything else you need from the node object.
    $nid = $node->id();
    $productLink = $node->get('field_product_link')->uri;
    $productUrl = Url::fromUri($productLink,array('absolute' => 'true'))->toString();
    $moduleDir = dirname(__DIR__, 3);

    try{
      $writer = new PngWriter();
      // Create QR code
      $qrCode = QrCode::create($productUrl)
      ->setEncoding(new Encoding('UTF-8'))
      ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
      ->setSize(300)
      ->setMargin(10)
      ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
      ->setForegroundColor(new Color(0, 0, 0))
      ->setBackgroundColor(new Color(255, 255, 255));

      // Create generic logo
      $logo = Logo::create($moduleDir.'/images/symfony.png')
      ->setResizeToWidth(50);

      // Create generic label
      $label = Label::create($node->getTitle())
      ->setTextColor(new Color(255, 0, 0));

      $result = $writer->write($qrCode, $logo, $label);

      header('Content-Type: '.$result->getMimeType());
      //echo $result->getString();

      // Save it to a file
      $result->saveToFile($moduleDir.'/images/qrcode-'.$nid.'.png');
      
      // Generate a data URI to include image data inline (i.e. inside an <img> tag)
      $dataUri = $result->getDataUri();
      $module_path = drupal_get_path('module', 'mycustomproducts');
      $image_path = "$module_path/images/qrcode-$nid.png";
      $image_url = file_create_url($image_path);
    }
    catch (Throwable $t) {
      \Drupal::logger('widget')->error($t->getMessage());
    }
    
  }
  $build = [];
  $build['body']['#markup'] = '<div class="qrcodescannew"><fieldset>
  <legend>Scan here on your mobile</legend><p>To purchase this product on our app to avail exclusive app only.</p><img src="'.$image_url.'"></fieldset></div>';
  $build['#cache']['max-age'] = 0;
  return $build;       
}
}