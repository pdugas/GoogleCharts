<?php
# =============================================================================
# GoogleCharts - MediaWiki Extension for Integration with GoogleCharts
# =============================================================================
# @file     GoogleCharts_body.php
# @brief    Implementation for the extension
# @author   Paul Dugas <paul@dugas.cc>
# =============================================================================
 
if (!defined('MEDIAWIKI')) {
    echo("This is an extension to the MediaWiki package and ".
         "cannot be run standalone.\n");
    die(-1);
}

/**
 * Implementation for the GoogleCharts extension. 
 */
class GoogleCharts 
{
  /**
   * Setup hooks. 
   */
  static function onParserInit(Parser $parser)
    {
      $parser->setHook('chart', array(__CLASS__, 'chartRender'));
      return true;
    } // function onParserInit()

  /**
   * Render the <chart/> tag.
   */
  static function chartRender($input, array $args, 
                              Parser $parser, PPFrame $frame)
    {
      global $wgArticlePath;

      if (array_key_exists('type', $args)) {
        $type = ucfirst($args['type']);
        unset($args['type']);
      } else {
        return $parser->recursiveTagParse("''(Invalid/missing TYPE in ".
                                          "&lt;chart/&gt;)''");
      }

      $count = $parser->getOutput()->getExtensionData('extGoogleCharts_count');
      $count = ($count ? $count+1 : 1);
      $parser->getOutput()->setExtensionData('extGoogleCharts_count', $count);

      if ($count == 1) {
        $script = "<script type='text/javascript' src='https://www.google.com/jsapi'></script>";
        $parser->getOutput()->addHeadItem($script);
        $script = "<script type='text/javascript'>extGoogleCharts = []; ".
                  "google.load('visualization', '1.0', {'packages':['corechart','gauge'], ".
                  "'callback':function () { var num = extGoogleCharts.length; for (var i = ".
                  "0; i != num; ++i) { extGoogleCharts[i](); } }});</script>";
        $parser->getOutput()->addHeadItem($script);
        $parser->getOutput()->setExtensionData('extGoogleCharts_count', TRUE);
      }

      // $input is expected to be JSON or CSV data
      $linkCol = 'null';
      $data = json_decode(str_replace("\n", '', $input));
      if (json_last_error() == JSON_ERROR_NONE) {
        if (property_exists($data, 'cols') && is_array($data->cols)) {
          $colNum = 0;
          foreach ($data->cols as $col) {
            if (property_exists($col, 'id') && is_string($col->id) && strtolower($col->id) == 'link') {
              $linkCol = $colNum; 
              if (property_exists($data, 'rows') && is_array($data->rows)) {
                foreach ($data->rows as &$row) {
                  $row->c[$linkCol]->v = str_replace('$1',$row->c[$linkCol]->v, $wgArticlePath);
                } unset($row);
              }
              break;
            }
            ++$colNum;
          }
        }
        $data = json_encode($data);
        $data = "new google.visualization.DataTable($data)";
      } else {
        $data = '';
        $firstRow = true;
        foreach (preg_split("/\R/s", $input) as $line){
          if ($line) {
            if ($firstRow) { 
              $firstRow = false;
              $colNum = 0;
              foreach (str_getcsv($line) as $col) {
                if (strtolower($col) == 'link') { $linkCol = $colNum; break; }
                ++$colNum;
              }
            } else { $data .= ","; }
            $data .= "[";
            $colNum = 0;
            foreach (str_getcsv($line) as $col) {
              if ($colNum) { $data .= ","; }
              if (is_numeric($col)) {
                $data .= $col;
              } elseif (is_int($linkCol) && $linkCol == $colNum)  {
                $data .= "'".str_replace('$1',$col, $wgArticlePath)."'";
              } else {
                $data .= "'".addslashes($col)."'";
              }
              ++$colNum;
            }
            $data .= "]";
          }
        } 
        $data = "google.visualization.arrayToDataTable([$data])";
      }

      // $args become the options
      $options = array();
      foreach ($args as $key => $val) {
        // XXX Gah!  MediaWiki is lower-casing the arguments so we can't
        // use them directly as the options.  For a quick hack, we replace
        // underscore and the letter that follows it with the uppercase of
        // the letter.  i.e. "minor_ticks" becomes "minorTicks"
        $key = preg_replace_callback('/_(.)/', 
                                     function ($m) {return strtoupper($m[1]);},
                                     $key);
        // If the value is JSON code, we use that, otherwise, we pass the value
        // directly.
        $val = html_entity_decode($val);
        $json = json_decode($val);
        $options[$key] = (json_last_error() == JSON_ERROR_NONE ? $json : $val);
      }
      $options = json_encode($options);

      $script = "<script type='text/javascript'>extGoogleCharts.push(function() ".
      "{ var data = $data; var options = $options; var chart = new ".
      "google.visualization.$type(document.getElementById('extGoogleCharts_$count')); ".
      "chart.draw(data, options); google.visualization.events.addListener(chart, ".
      "'select', function(e) { var col = $linkCol; if (col == null) { return; } ".
      "var item = chart.getSelection()[0]; if (item) { ".
      "window.location.replace(data.getValue(item.row, col)); } } ); });</script>";
  
      return "<div id=\"extGoogleCharts_$count\"></div>$script";
    } // function chartRender()

} // class GoogleCharts

# =============================================================================
# vim: set et sw=2 ts=2 :
