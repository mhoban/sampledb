<script>
var base_url = '<?php echo base_url(); ?>';
</script>
<link type="text/css" rel="stylesheet" href="http://localhost:8888/sampledb/assets/grocery_crud/css/jquery_plugins/chosen/chosen.css" />
<script src="assets/grocery_crud/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/js.cookie.js"></script>
<script src="assets/grocery_crud/js/jquery_plugins/jquery.chosen.min.js"></script>
<script src="assets/grocery_crud/js/jquery_plugins/config/jquery.chosen.config.js"></script>

<script>
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
      m.addListener("click",function(e) {
        showSamples(this.title);
      });
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
<?php echo $map['js']; ?>
<?php echo $map['html']; ?>
</div>