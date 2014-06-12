<?php
# =============================================================================
# GoogleCharts - MediaWiki Extension for Integration with GoogleCharts
# =============================================================================
# @file     GoogleCharts.php
# @brief    Setup for the extension
# @author   Paul Dugas <paul@dugas.cc>
# =============================================================================
 
if (!defined('MEDIAWIKI')) {
    echo("This is an extension to the MediaWiki package and ".
         "cannot be run standalone.\n");
    die(-1);
}

$wgExtensionCredits['parserhook'][] = array(
    'path'          => __FILE__,
    'name'          => 'GoogleCharts',
    'author'        => array('[mailto:paul@dugas.cc Paul Dugas]'),
    'url'           => 'https://github/pdugas/GoogleCharts/',
    'description'   => 'Adds <nowiki><chart/></nowiki> tag '.
                       'for inserting Google Charts into wiki pages.',
    'version'       => 0.1,
    'license-name'  => 'GPL v2',
);

$wgAutoloadClasses['GoogleCharts'] = __DIR__.'/GoogleCharts_body.php';

$wgHooks['ParserFirstCallInit'][] = 'GoogleCharts::onParserInit';

$wgExtensionMessagesFiles['GoogleCharts'] = __DIR__.'/GoogleCharts.i18n.php';

# =============================================================================
# vim: set et sw=2 ts=2 :
