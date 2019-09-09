<script>
var base_url = '<?php echo base_url(); ?>';
</script>
<link type="text/css" rel="stylesheet" href="http://localhost:8888/sampledb/assets/grocery_crud/css/jquery_plugins/chosen/chosen.css" />
<script src="assets/grocery_crud/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/js.cookie.js"></script>
<script src="assets/js/oms.min.js"></script>
<script src="assets/grocery_crud/js/jquery_plugins/jquery.chosen.min.js"></script>
<script src="assets/grocery_crud/js/jquery_plugins/config/jquery.chosen.config.js"></script>
<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=<?php echo $apiKey; ?>"></script>
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
      grouping_filter: $("#grouping").val(),
      island_filter: $("#islands").val(),
      country_filter: $("#countries").val()
    };
  } else {
    $("#station_filter").val(filter.text_filter);
    $("#grouping").val(filter.grouping_filter).trigger("chosen:updated");
    $("#islands").val(filter.island_filter).trigger("chosen:updated");
    $("#countries").val(filter.country_filter).trigger("chosen:updated");
  }
	oms.removeAllMarkers();
  Cookies.set("station_map_filter",filter);
  //var text_filter = $("#station_filter").val();
  //var grouping_filter = $("#grouping").val();
  //var island_filter = $("#islands").val();
  //var country_filter = $("#countries").val();
  $.getJSON(base_url + "samples/station_map/filter",{
    "filter": filter.text_filter,
    "grouping": filter.grouping_filter,
    "island": filter.island_filter,
    "country": filter.country_filter
  }, function(data) {
    deleteMarkers();
    data.map(function(e,i,a) {
      var point = new google.maps.LatLng(parseFloat(e.lat),parseFloat(e.lon));
      var marker_options = {
        map: map,
        position: point,
        title: e.station_name,
        station_id: e.station_id
      };
      var m = createMarker_map(marker_options);
      m.addListener("spider_click",function(e) {
        showSamples(this.title);
      });
			oms.addMarker(m);
    });
    fitMapToBounds_map();
  });
}

function updateSelect(element,qry="")
{
  $e = $(element);
  if (qry == "")
    qry = element.replace(/^#/,"");
  if ($e.length > 0) {
    $.getJSON(base_url + "samples/" + qry + "/json", function(data) {
      var $e = $(element);
      data.splice(0,0,{id: "", name: "*any*"}); // insert a blank option for any value
      var items = data.map(function(e,i,a) {
        return ($("<option>").attr('value',e.id).text(e.name));
      });
      $e.empty().append(items);
      $e.trigger("chosen:updated");
    });
  }
}

function setup()
{
	initialize_map();
  $("select").chosen({width: "200px"});
  $("select").on("change",function(e) {
    filterMap();
  });
  $("#station_filter").on("keydown",function(e) {
    if (e.which == 13) {
      filterMap();
      e.preventDefault();
    }
  });
  $("#clearfilter").click(function() {
    filterMap({
      text_filter: "",
      grouping_filter: "",
      island_filter: "",
      country_filter: "" 
    });
  });
  // make these things load synchronously so when we filter the map the dropdowns will have content
  $.ajaxSetup({async: false});
  updateSelect("#grouping");
  updateSelect("#islands");
  updateSelect("#countries");
  $.ajaxSetup({async: true});
  // something weird about the load order of different components allow the below hack to make things work
  //setTimeout(function() { 
    filter = Cookies.getJSON("station_map_filter");
    filterMap(filter); 
  //},0);
}

$(function() {
  setup();
});
</script>
<div id="top">
Filter stations by: <br>
Station name: <input id="station_filter" type="text"> |
Station grouping: <select id="grouping"></select> | 
Island: <select id="islands"></select> |
Country: <select id="countries"></select>
<input type="button" id="clearfilter" value="Clear filters">
</div>
<div id="bottom">
<?php #echo $map['js']; ?>
<?php #echo $map['html']; ?>
<div id="map_canvas" style="width:100%; height:450px;"></div>
</div>