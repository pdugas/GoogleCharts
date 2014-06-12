GoogleCharts
============

MediaWiki extension to insert Google Charts into wiki pages

(work in progress)

Generate a couple of gauges like so:
```
<chart type="gauge" minor_ticks="5">
Label,Value
Speed,55
Pressure,95
</chart>
```

See https://developers.google.com/chart/

The `type` attribute passed to the `<chart/>` tag is used determine the type of chart to display.  i.e. `google.visualization.$TYPE`

Other attributes passed to the `<chart/>` tag become the options.  MediaWiki translates the attribute names to all lower case but the Google API uses mixed case attributes.  As a hack, underscores in attribute names will be removed and the following character will be converted to uppercase.  So, in order to specify the `minorTicks` option, use the `minor_ticks` attribute with the tag.  Sorry for the hassle.

Data within the `<chart>...</chart>` tag is expected to be simple CSV lines.  
