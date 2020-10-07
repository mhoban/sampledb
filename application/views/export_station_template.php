<script>
var base_url = '<?php echo base_url(); ?>';
</script>
<link type="text/css" rel="stylesheet" href="http://localhost:8888/sampledb/assets/grocery_crud/css/jquery_plugins/chosen/chosen.css" />
<link type="text/css" rel="stylesheet" href="<?php echo base_url('assets/grocery_crud/css/ui/simple/jquery-ui-1.10.1.custom.min.css'); ?>">
<script src="assets/grocery_crud/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/js.cookie.js"></script>
<script src="assets/js/download.js"></script>
<script src="assets/grocery_crud/js/jquery_plugins/jquery.chosen.min.js"></script>
<script src="assets/grocery_crud/js/jquery_plugins/config/jquery.chosen.config.js"></script>
<script src="assets/grocery_crud/js/jquery_plugins/ui/jquery-ui-1.10.3.custom.min.js"></script>
<style type="text/css">
table td, table th { padding:5px;border:1px solid #000; }
.float-container {
  width: 400px;
  align-items: center;
  display: flex;
}

.float-child {
  flex: 1;
}
.float-child:first-child {
  margin-right: 10px;
}
</style>
<script>
var table_data=null;

function copy_text(text) {
  const listener = function(ev) {
    ev.preventDefault();
    ev.clipboardData.setData('text/plain', text);
  };
  document.addEventListener('copy', listener);
  document.execCommand('copy');
  document.removeEventListener('copy', listener);
}

function do_headers(data,table=null)
{
  var headers = [];
  var row = table != null ? $("<tr>") : null;
  $.each(data,function(i,e) {
    for (k in e) {
      if ($.inArray(k,headers) == -1) {
        headers.push(k);
        if (table && row) 
          row.append($("<th>").html(k))
      }
    }
  });
  if (table && row)
    table.append(row);
  return(headers);
}

function clear_filter(e)
{
  $("#sample_ids").val("");
  $("#datagrid").html("");
  $(".clipboard_link").hide();
  table_data = null;
  e.preventDefault();
  return false;
}

function filter(e)
{
  var sids = $("#sample_ids").val().trim().split(/\n/);
  var which = $("input:radio[name='which']:checked").val();
  $.post(base_url + "/samples/export_station/json/" + which,{'sample_ids[]': sids},function(data,status,xhr) {
    table_data = data;
    var table = $('<table cellspacing="0">');
    $("#datagrid").html("");
    var headers = do_headers(data,table);
    for (i=0; i<data.length; i++) {
      var row = $("<tr>");
      for (c = 0; c < headers.length; c++) {
        var val = data[i][headers[c]];
        if (val == null) val = "";
        row.append($("<td>").html(val));
      }
      table.append(row);
    }
    $("#datagrid").append(table);
  },"json");
  // if (table_data != null && table_data.length > 0)
  $(".clipboard_link").show();
  e.preventDefault();
  return false;
}

function table_str(data,sep="\t",fancy=false)
{
  var rows = new Array();
  if (data != null && data.length > 0)
  {
    var headers = do_headers(data);
    rows.push(headers);
    for (i=0; i<data.length; i++) {
      var row = new Array(); 
      // var row = $("<tr>");
      for (c = 0; c < headers.length; c++) {
        var val = data[i][headers[c]];
        if (val == null) val = "";
        row.push(val);
      }
      rows.push(row);
    }
  }
  var table_str = "";
  if (!fancy)
    table_str = rows.map(x => x.join(sep)).join("\n");
  else {
    table_str = rows.map(function(d){
       return JSON.stringify(d);
    })
    .join('\n') 
    .replace(/(^\[)|(\]$)/mg, '');
  }
  return table_str;
}

function clipboard(e)
{
  e.preventDefault();
  copy_text(table_str(table_data));
  return false;
}

function export_csv(e)
{
  e.preventDefault();
  var s = table_str(table_data,",",true);
  download(s,'station_data.csv','text/csv');
  return false;
}

function get_all_edna(e) 
{
  e.preventDefault();
  $.getJSON(base_url + '/samples/edna/json',function(data) {
    $("#sample_ids").val(data.join("\n"));
  });
  $('input:radio[name="which"][value="edna"]').prop("checked",true);
  return false;
}

function get_all_fish(e)
{
  e.preventDefault();
  $.getJSON(base_url + "/samples/sample/json",function(data) {
    $("#sample_ids").val(data.join("\n"));
  });
  $('input:radio[name="which"][value="sample"]').prop("checked",true);
  return false;
}

$( function() {
  var panel_open = (Cookies.get("sample_panel_open") == "true") ? 0 : false;
  console.log("panel open: " + panel_open);
  $( "#accordion" ).accordion({
    collapsible: true,
    active: panel_open,
    activate: function(e,ui) {
      var active = ($("#accordion").accordion("option","active") !== false);
      Cookies.set("sample_panel_open",active);
      $("#expando").toggle();
    }
  });
  $("#clear").click(clear_filter);
  $("#filter").click(filter);
  $("#clipboard").click(clipboard);
  $("#csv").click(export_csv);
  $("#get_all_edna").click(get_all_edna);
  $("#get_all_fish").click(get_all_fish);
  console.log(panel_open);
  if (panel_open !== false)
    $("#expando").hide();
  else
    $("#expando").show();
} );

</script>
<div id="container" class="float-container">
  <div id="accordion" class="float-child">
    <h3>sample ID list <span class="display: inline;" id="expando">(click to expand)</span></h3>
    <div>
      <p>
        sample types:
        <input type="radio" id="edna" name="which" value="edna" checked>
        <label for="edna">eDNA</label>
        <input type="radio" id="sample" name="which" value="sample">
        <label for="sample">fish</label>
      </p>
      <p>
        sample IDs, one per line
        [<a href="" id="filter">get</a>]
        <!--<input type="button" id="filter" value="filter">-->
        [<a href="" id="clear">clear</a>]
      </p>
      <textarea id="sample_ids" rows="10" cols="35"></textarea>
      <p>
        [<a href="" id="get_all_edna">all eDNA</a>]
        [<a href="" id="get_all_fish">all fish</a>]
      </p>
    </div>
  </div>
</div>
<p>&nbsp;</p>
<div id="datacontainer">
  <div class="clipboard_link" style="display: none">
    [<a href="" id="clipboard">copy to clipboard (tab-separated)</a>]
    [<a href="" id="csv">export csv</a>]
  </div>
  <div id="datagrid"></div>
  <div class="clipboard_link" style="display: none">
    [<a href="" id="clipboard">copy to clipboard (tab-separated)</a>]
    [<a href="" id="csv">export csv</a>]
  </div>
</div>
