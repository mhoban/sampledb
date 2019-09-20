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
      grouping: $("#grouping").val(),
      islands: $("#islands").val(),
      countries: $("#countries").val()
    };
  } else {
    $("#station_filter").val(filter.text_filter);
    $("#grouping").val(filter.grouping).trigger("chosen:updated");
    $("#islands").val(filter.islands).trigger("chosen:updated");
    $("#countries").val(filter.countries).trigger("chosen:updated");
  }
	oms.removeAllMarkers();
  Cookies.set("station_map_filter",filter);
  $.getJSON(base_url + "samples/station_map/filter",{
    "filter": filter.text_filter,
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
        title: e.station_name,
        station_id: e.station_id
      };
      var m = createMarker_map(marker_options);
      m.addListener("spider_click",function(e) {
        console.log(this.constructor.name);
        console.log(e.constructor.name);
        //showSamples(this.title);
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
        console.log(this.qry);
        console.log(this.filter);
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
      grouping: "",
      islands: "",
      countries: "" 
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