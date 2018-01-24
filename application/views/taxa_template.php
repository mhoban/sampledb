<?php if (!$add_dialog):?>
<link type="text/css" rel="stylesheet" href="<?php echo base_url('assets/grocery_crud/css/jquery_plugins/chosen/chosen.css'); ?>">
<link type="text/css" rel="stylesheet" href="<?php echo base_url('assets/grocery_crud/css/ui/simple/jquery-ui-1.10.1.custom.min.css'); ?>">
<script src="<?php echo base_url('assets/grocery_crud/js/jquery_plugins/jquery.chosen.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/grocery_crud/js/jquery_plugins/config/jquery.chosen.config.js'); ?>"></script>
<script src="<?php echo base_url('assets/grocery_crud/js/jquery_plugins/ui/jquery-ui-1.10.3.custom.min.js'); ?>"></script>
<?php endif; ?>
<script>
var delay_timer=false;
function taxonChange(e)
{
  if ($(this).val().length > 2) {
    var srch = $("#field-genus").val() + " " + $("#field-species").val();
    if (delay_timer) {
      clearTimeout(delay_timer);
    }
    delay_timer = setTimeout(function() {
      if (!/^\s/.test(srch)) {
        srch = encodeURI(srch.trim());
        $("#img-loader").show();
        $.getJSON("http://www.marinespecies.org/rest/AphiaRecordsByName/" + srch + "?like=true&marine_only=true", function(data) {
          $("#img-loader").hide();
          var $subitems = [];
          //$("#field-worms_id").empty();
          $(data).each(function() {
            if (this.scientificname != null && this.AphiaID != null) {
              $subitems.push($("<option>").attr('value',this.AphiaID).text(this.scientificname));
            }
          });
          $("#field-worms_id").empty().append($subitems).trigger('chosen:updated');
        })
        .fail(function() {
          $("#img-loader").hide();
        });

      }
    },500);
  }
}
$(function() {
  var response_cache = {};
  var response_data = {};
  if ($("#field-worms_id").is('select'))
    $("#field-worms_id").chosen();
  //$("#worms_id_input_box").after($('<img src="<?php echo base_url('assets/ajax-loader.gif'); ?>" style="display: none" id="img-loader"/>'));
  $("#worms_id_input_box").after($('<img>',
    {
      'id': 'img-loader',
      'src': "<?php echo base_url('assets/ajax-loader.gif'); ?>",
      'style': 'display: none'
    }
  ));
  $("#field-genus").keyup(taxonChange);
  $("#field-species").keyup(taxonChange);
  $(".get-worms-id").click(function(e) {
    e.preventDefault();
  });
  $("#field-worms_id").change(function() {
    var s = $("#field-worms_id  option:selected").text().split(/\s+/);
    if (s.length > 1) {
      if ($("#field-genus").val() != s[0]) $("#field-genus").val(s[0]);
      if ($("#field-species").val() != s[1]) $("#field-species").val(s[1]);
    } else {
      if ($("#field-genus").val() != s[0]) $("#field-genus").val(s[0]);
      $("#field-species").val('sp.');
    }
    var wid = $("#field-worms_id option:selected").val();
    if ($("#worms-popup").length == 0)
    {
      $("#worms_id_input_box").append($('<a>',
        {
          'id': 'worms-popup',
          'href': "http://marinespecies.org/aphia.php?p=taxdetails&id="+wid,
          'target': '_blank',
          'title': 'Open WORMS page in new tab/window'
        }).text('[?]')
      );
    }
    else {
      $("#worms-popup").attr('href',"http://marinespecies.org/aphia.php?p=taxdetails&id="+wid);
    }
  });
});
</script>
<div>
<?php echo $output; ?>
</div>
