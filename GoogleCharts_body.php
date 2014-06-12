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
      global $wgOut;

      if (array_key_exists('type', $args)) {
        $type = ucfirst($args['type']);
        unset($args['type']);
      } else {
        return $parser->recursiveTagParse("''(Invalid/missing TYPE in ".
                                          "&lt;chart/&gt;)''");
      }

      if (!$parser->getOutput()->getExtensionData('extGoogleCharts_count')) {
        $script = <<<ENDSCRIPT
<script type='text/javascript' src='https://www.google.com/jsapi'></script>
ENDSCRIPT;
        $wgOut->addScript($script);
        $script = <<<ENDSCRIPT
<script type='text/javascript'>
  extGoogleCharts = [];
  function extGoogleCharts_drawCharts() {
    var numCharts = extGoogleCharts.length;
    for (var i = 0; i != numCharts; ++i) {
      extGoogleCharts[i]();
    }
  }
  google.load('visualization', '1.0',
              {'packages':['corechart','gauge'],
               'callback':extGoogleCharts_drawCharts});
</script>
ENDSCRIPT;
        $wgOut->addScript($script);
        $parser->getOutput()->setExtensionData('extGoogleCharts_count', TRUE);
      }

      // $input is expected to be CSV data
      $data = '';
      foreach(preg_split("/((\r?\n)|(\r\n?))/", $input) as $line){
        if ($line) {
          if ($data) { $data .= ",["; } else { $data = "["; }
          $first = true;
          foreach(str_getcsv($line) as $col) {
            if ($first) { $first = false; } else { $data .= ","; }
            if (is_numeric($col)) {
              $data .= $col;
            } else {
              $data .= "'$col'";
            }
          }
          $data .= "]";
        }
      } 

      // $args become the options
      $options = '';
      foreach ($args as $key => $val) {
        if ($options) { $options .= ","; } 

        // XXX Gah!  MediaWiki is lower-casing the arguments so we can't
        // use them directly as the options.  For a quick hack, we replace
        // underscore and the letter that follows it with the uppercase of
        // the letter.  i.e. "minor_ticks" becomes "minorTicks"
        $key = preg_replace_callback('/_(.)/', 
                                     function ($m) {return strtoupper($m[1]);},
                                     $key);

        if (is_numeric($val)) {
	  $options .= "$key:$val";
        } else {
	  $options .= "$key:'$val'";
        }
      }
      $options = '{'.$options.'}';

      $script = <<<ENDSCRIPT
<script type='text/javascript'>
  extGoogleCharts.push(function() {
    var data = google.visualization.arrayToDataTable([$data]);
    var options = $options;
    var div = document.getElementById('extGoogleCharts_chart$count');
    var chart = new google.visualization.Gauge(div);
    chart.draw(data, options);
  });
</script>
ENDSCRIPT;
      $wgOut->addScript($script);
  
      return "<div id=\"extGoogleCharts_chart$count\"></div>";
    } // function chartRender()

} // class GoogleCharts

# =============================================================================
# vim: set et sw=2 ts=2 :
