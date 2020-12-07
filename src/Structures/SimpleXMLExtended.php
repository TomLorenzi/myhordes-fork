<?php

// http://coffeerings.posterous.com/php-simplexml-and-cdata
/**
 * Custom XML class to handle easily CData
 * Source : https://stackoverflow.com/questions/6260224/how-to-write-cdata-using-simplexmlelement/6260295
 */
class SimpleXMLExtended extends SimpleXMLElement {
  public function addCData($cdata_text) {
    $node = dom_import_simplexml($this); 
    $no   = $node->ownerDocument; 
    $node->appendChild($no->createCDATASection($cdata_text)); 
  } 
}