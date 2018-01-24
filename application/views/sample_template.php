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
    $.getJSON("<? echo site_url('samples/sample_number'); ?>/"+$("#field-sample_prefix").val(),function(data) {
      $("#field-sample_number").val(parseInt(data['max_sample'])+1);
    });
  });
  $(document).on('click','.stationid',function(e) {
    var station_id = $(e.target).attr('href');
    var $dialog = $('<div></div>')
      .html('<iframe style="border: 0px; " src="<?php echo base_url('samples/station/read/') ?>' + station_id + '/display" width="100%" height="100%"></iframe>')
      .dialog({
        autoOpen: false,
        modal: true,
        height: 600,
        width: 500,
        title: 'Station Details',
        buttons: {
          Ok: function() {
            $(this).dialog('close');
          }
        }
      });
    $dialog.dialog('open');    

    e.preventDefault();
    return false;
  });
});
</script>
<div>
<?php echo $output; ?>
</div>