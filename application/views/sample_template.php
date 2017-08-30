<div id="worms_dlg" title="Load species information" style="display:none">
Scientific name begins with: <input id="spp_search" type="text" size="20">
</div>
<script>
function makePrefix(str)
{
  var tw = str.split(/\s+/);
  var prefix = tw[0][0] + tw[1][0] + tw[1][1];
  $("#field-sample_prefix").val(prefix);
  $("#field-sample_prefix").change();
}
$(function() {
  $("#field-taxon_id").change(function(event,ui) {
    makePrefix($("#field-taxon_id option:selected").text());
  });
  $("#field-sample_prefix").change(function(event,ui) {
    $.getJSON("/sampledb/samples/sample_number",{"prefix":$("#field-sample_prefix").val()},function(data) {
      $("#field-sample_number").val(parseInt(data['max_sample'])+1);
    });
  });
  //$("#field-taxon_id").change(function(event,ui) {
    //$.getJSON("/sampledb/samples/mlh_number",function(data) {
      //$("#field-mlh_number").val(parseInt(data['mlh_number'])+1);
    //});
  //});
});
</script>

<div style='height:20px;'></div>  
<div>
<?php echo $output; ?>
</div>