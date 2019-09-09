<?php if (isset($station_filter) && strlen($station_filter) > 0): ?>
<script>
$(function() {
  var station_filter = "<?php echo $station_filter; ?>";
  $("#search_text").val(station_filter);
  $("#filtering_form").submit(); 
});
</script>
<?php endif; ?>
<div>
<?php echo $output; ?>
</div>