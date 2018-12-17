<script>
var tmr_delay=false;
$(function() {
  $("#field-sample_prefix").on('change paste',function(event,ui) {
    $.getJSON("<? echo site_url('samples/edna_number'); ?>/"+$("#field-sample_prefix").val(),function(data) {
      $("#field-number_start").val(parseInt(data['max_edna'])+1);
    });
  });
  $("#field-sample_prefix").on('keyup',function(event,ui) {
    if (tmr_delay) clearTimeout(tmr_delay);
    tmr_delay = setTimeout(function() {
      $.getJSON("<? echo site_url('samples/edna_number'); ?>/"+$("#field-sample_prefix").val(),function(data) {
        $("#field-number_start").val(parseInt(data['max_edna'])+1);
      });
    },
    500);
  });
});
</script>
<div>
<?php echo $output; ?>
</div>