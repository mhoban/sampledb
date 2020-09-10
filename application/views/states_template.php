<script>
var base_url = '<?php echo base_url(); ?>';
</script>
<link type="text/css" rel="stylesheet" href="assets/grocery_crud/css/jquery_plugins/chosen/chosen.css" />
<link type="text/css" rel="stylesheet" href="assets/grocery_crud/css/ui/simple/jquery-ui-1.10.1.custom.min.css">
<script src="assets/grocery_crud/js/jquery-1.11.1.min.js"></script>
<script src="assets/grocery_crud/js/jquery_plugins/jquery.form.js"></script>
<script src="assets/grocery_crud/js/jquery_plugins/jquery.chosen.min.js"></script>
<script src="assets/grocery_crud/js/jquery_plugins/config/jquery.chosen.config.js"></script>
<script src="<?php echo base_url('assets/grocery_crud/js/jquery_plugins/ui/jquery-ui-1.10.3.custom.min.js'); ?>"></script>
<script>
function validate_ids()
{
  ids = $("#idlist").val().split(/\n/);
  if (ids.length > 0 && ids[0] != "") {
    return true;
  }
  return false;
}

function msg(message) {
  $("#msgbox")
    .html(message)
    // .center()
    .show()
    .delay(1000)
    .fadeOut(200);
}

$(function() {
  jQuery.fn.center = function () {
    this.css("position","absolute");
    this.css("top", Math.max(0, (($(window).height() - $(this).outerHeight()) / 2) + 
      $(window).scrollTop()) + "px");
    this.css("left", Math.max(0, (($(window).width() - $(this).outerWidth()) / 2) + 
      $(window).scrollLeft()) + "px");
    return this;
  }
  sel = $("select");
  sel.chosen({width: "170px"});
  $.getJSON(base_url + "samples/state/json",function(data) {
    var items = data.map(function(e,i,a) {
      return ($("<option>").attr('value',e.id).text(e.name));
    });
    sel.empty().append(items); 
    sel.trigger("chosen:updated");
  });
  $("#stateform").submit(function(e) {
    e.preventDefault();
    if (validate_ids()) {
      $("#stateform").ajaxSubmit({
        dataType: "json",
        success: function(data) {
          if (data.success) {
            msg("updated " + data.num + " records");
          } else {
            msg("didn't update any records");
          }
          $("#idlist").focus();
        }
      });
    }
    $("#idlist").select();
  });
});
</script>
<div>
<form id="stateform" method="post" action="<?php echo base_url("samples/editstate/$which/insert"); ?>">
<table border="0">
<tr><th align="left" colspan="2">Enter sample IDs to update status</th></tr>
<tr><td colspan="2">
<textarea rows="20" cols="30" id="idlist" name="sampleids">
</textarea>
</td></tr>
<tr>
<td width="50%" align="left"><select id="statesel" name="state_id"></select></td>
<td width="50%" align="left"><input type="submit" value="submit"></td>
</tr>
<tr><td colspan="2">
<div id="msgbox" style="width: 200px; display: none; border: solid black 1px; background: #ffffff; padding: 10px;"></div>
</td></tr>
</table>
</form>
</div>
