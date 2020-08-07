<script>
var base_url = '<?php echo base_url(); ?>';
</script>
<link type="text/css" rel="stylesheet" href="http://localhost:8888/sampledb/assets/grocery_crud/css/jquery_plugins/chosen/chosen.css" />
<link type="text/css" rel="stylesheet" href="<?php echo base_url('assets/grocery_crud/css/ui/simple/jquery-ui-1.10.1.custom.min.css'); ?>">
<script src="assets/grocery_crud/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/js.cookie.js"></script>
<script src="assets/js/oms.min.js"></script>
<script src="assets/grocery_crud/js/jquery_plugins/jquery.chosen.min.js"></script>
<script src="assets/grocery_crud/js/jquery_plugins/config/jquery.chosen.config.js"></script>
<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=<?php echo $apiKey; ?>"></script>
<script src="<?php echo base_url('assets/grocery_crud/js/jquery_plugins/ui/jquery-ui-1.10.3.custom.min.js'); ?>"></script>
<script>
var map; // Global declaration of the map
var oms; 
var lat_longs_map = new Array();
var markers_map = new Array();
var iw_map;
iw_map = new google.maps.InfoWindow();

function initialize_map() 
{
	var myLatlng = new google.maps.LatLng(37.4419, -122.1419);
	var myOptions = {
		zoom: 13,
		center: myLatlng,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};
	map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
	oms = new OverlappingMarkerSpiderfier(map, { 
		markersWontMove: true,   // we promise not to move any markers, allowing optimizations
		markersWontHide: true,   // we promise not to change visibility of any markers, allowing optimizations
		basicFormatEvents: true  // allow the library to skip calculating advanced formatting information
	});
}


function createMarker_map(markerOptions) 
{
	var marker = new google.maps.Marker(markerOptions);
	markers_map.push(marker);
	lat_longs_map.push(marker.getPosition());
	return marker;
}

function fitMapToBounds_map() 
{
	var bounds = new google.maps.LatLngBounds();
	if (lat_longs_map.length>0) {
		for (var i=0; i<lat_longs_map.length; i++) {
			bounds.extend(lat_longs_map[i]);
		}
		map.fitBounds(bounds);
	}
}

function setMapMarkers(gmap,markers_list)
{
  for (var i=0; i < markers_list.length; i++) {
    markers_list[i].setMap(gmap);
  }
}

function clearMarkers()
{
  setMapMarkers(null,markers_map);
}

function showMarkers()
{
  setMapMarkers(map,markers_map);
}

function deleteMarkers()
{
  clearMarkers();
  markers_map = [];
  lat_longs_map = [];
}

function showSamples(station_id)
{
  window.location.href = base_url + "samples/edna?station_filter=" + encodeURI(station_id);
}

function filterMap(filter=null)
{
  if (!filter || filter == null) {
    filter = {
      text_filter: $("#station_filter").val(),
      number_filter: $("#sample_filter").val().split(/,/),
      grouping: $("#grouping").val(),
      islands: $("#islands").val(),
      countries: $("#countries").val()
    };
  } else {
    $("#station_filter").val(filter.text_filter);
    $("#sample_filter").val(filter.number_filter.join(","));
    $("#grouping").val(filter.grouping).trigger("chosen:updated");
    $("#islands").val(filter.islands).trigger("chosen:updated");
    $("#countries").val(filter.countries).trigger("chosen:updated");
  }
	oms.removeAllMarkers();
  Cookies.set("station_map_filter",filter);
  $.getJSON(base_url + "samples/station_map/filter",{
    "filter": filter.text_filter,
    "sample": filter.number_filter,
    "grouping": filter.grouping,
    "island": filter.islands,
    "country": filter.countries
  }, function(data) {
    deleteMarkers();
    data.map(function(e,i,a) {
      var point = new google.maps.LatLng(parseFloat(e.lat),parseFloat(e.lon));
      var marker_options = {
        map: map,
        position: point,
        title: e.station_name + " (eDNA: " + e.ecount + ", fish: " + e.scount + ")",
        qry: e.station_name,
        station_id: e.station_id
      };
      var m = createMarker_map(marker_options);
      m.addListener("spider_click",function(e) {
        var content = "<div>" + 
          "[<a href=\"" + base_url + "samples/edna?station_filter=" + encodeURI(this.qry) + "\">show edna</a>]" + " " +
          "[<a href=\"" + base_url + "samples/sample?station_filter=" + encodeURI(this.qry) + "\">show fish</a>]" +
          "<iframe style=\"border: 0px;\" src=\"" + 
          base_url + "samples/station/read/" + this.station_id + "/display\" " +
          "width=\"100%\" height=\"100%\"></iframe>";
        iw_map.setContent(content);
        iw_map.open(this.map,this);
        //$.ajax({
          //url: base_url + "samples/station/json/" + this.station_id, 
          //type: "GET",
          //dataType: "json",
          //map: this.map,
          //mark: this,
          //success: function(data) {
            //var content = "<div>" + 
              //"<table border=1>" + 
              //"<tr><th colspan=2>" + data.station_name + "</th></tr>" + 
              //"<tr><td>Grouping:</td><td>" + data.grouping_name + "</td>" + 
              //"<tr><td>Protection status:</td><td>" + data.status_name + "</td>" +
              //"<tr><td>Island:</td><td>" + data.island + "</td>" + 
              //"<tr><td>Country:</td><td>" + data.country + "</td>" + 
              //"<tr><td>Position:</td><td>" + data.lat + "," + data.lon + "</td>" +
              //"<tr><td>Depth:</td><td>" + data.depth_min + " - " + data.depth_max + " (m)" + 
              //"<tr><td colspan=2 align=\"left\">Notes:<br>" + data.notes + "</td></tr>" + 
              //"</table>" + 
              //"</div>";
            //iw_map.setContent(content);
            //iw_map.open(this.map,this.mark);
          //}
        //});
      });
			oms.addMarker(m);
    });
    fitMapToBounds_map();
  });
}

function updateSelect(element,config)
{
  $(element).each(function(index) {
    var q = config.qry;
    if (typeof(q) == "undefined" || q == "")
      q = $(this).attr("id");
    $.ajax({
      url: base_url + "samples/" + q + "/json",
      type: "GET",
      dataType: "json",
      sel: $(this),
      qry: q,
      filter: config.filter,
      success: function(data) {
        data.splice(0,0,{id: "", name: "*any*"}); // insert a blank option for any value
        var items = data.map(function(e,i,a) {
          return ($("<option>").attr('value',e.id).text(e.name));
        });
        this.sel.empty().append(items);
        if (typeof(this.filter) != "undefined") {
          if (typeof(this.filter[this.qry]) != "undefined") {
            this.sel.val(this.filter[this.qry]);
          }
        }
        this.sel.trigger("chosen:updated");
      }
    });
  });
}

function filter_import(dlg)
{
  var sampleids = $(dlg).find("#sampleids").val().trim().split(/\n/);
  $("#sample_filter").val(sampleids.join(","));
  filterMap();
}

function import_filter(e)
{
  var $dialog = $("#dlg")
    .dialog({
      autoOpen: true,
      modal: true,
      height: 420,
      width: 290,
      resizable: false,
      title: 'Filter by sample IDs',
      buttons: {
        Ok: function() {
          filter_import(this);
          $(this).dialog('close');
        },
        Cancel: function() {
          $(this).dialog('close');
        }
      },
      open: function(e,ui) {
        ids = $("#sample_filter").val().split(/,/);
        nl = ids[0] != "" ? "\n" : "";
        $(this).find('#sampleids').val(ids.join("\n") + nl); 
      }
    });
  e.preventDefault();
}

function setup()
{
	initialize_map();
  $("select").chosen({width: "200px"});
  $("select").on("change",function(e) {
    filterMap();
  });
  $("#import_filter").click(import_filter);
  $("#station_filter").on("keydown",function(e) {
    if (e.which == 13) {
      filterMap();
      e.preventDefault();
    }
  });
  $("#sample_filter").on("keydown",function(e) {
    if (e.which == 13) {
      filterMap();
      e.preventDefault();
    }
  });
  $("#clearfilter").click(function() {
    filterMap({
      text_filter: "",
      grouping: "",
      islands: "",
      countries: "",
      number_filter: []
    });
  });
  filter = Cookies.getJSON("station_map_filter");
  updateSelect("select",{filter: filter});
  filterMap(filter); 
}

$(function() {
  setup();
});
</script>
<div id="top">
<!--
<table>
<tr><td colspan=4>Filter stations by:</td></tr>
<tr><td>Station name:</td><td><input id="station_filter" type="text"></td>
<td>Sample number:</td><td><input id="sample_filter" type="text"></td></tr>
<tr><td>Station grouping:</td><td><select id="grouping"></select></td>
<td>Island:</td><td><select id="islands"></select></td></tr>
<tr><td>Country:</td><td><select id="countries"></select></td>
<td><input type="button" id="clearfilter" value="Clear filters"></td></tr>
</table>
-->
<div id="dlg" style="display: none">
Enter one sample ID per line<br>
<textarea id='sampleids' rows='14' cols='30'></textarea>
</div>
<table border=1>
<tr>
<td ><strong>Filter stations</strong></td>
<td align="left" colspan=3><input type="button" id="clearfilter" value="Clear filters"></td>
</tr>
<tr>
<td>Sample/eDNA number:</td><td colspan=3><input id="sample_filter" type="text"> [<a href="" id="import_filter">list</a>]</td>
</tr>
<tr>
<td>Station name:</td><td><input id="station_filter" type="text"></td>
<td>Station grouping:</td><td><select id="grouping"></select></td>
</tr>
<tr>
<td>Island:</td><td><select id="islands"></select></td>
<td>Country:</td><td><select id="countries"></select></td>
</tr>
</table>

</div>
<div id="bottom">
<?php #echo $map['js']; ?>
<?php #echo $map['html']; ?>
<div id="map_canvas" style="width:100%; height:600px;"></div>
</div>