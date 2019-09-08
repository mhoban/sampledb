<script>
var base_url = '<?php echo base_url(); ?>';
</script>
<link type="text/css" rel="stylesheet" href="http://localhost:8888/sampledb/assets/grocery_crud/css/jquery_plugins/chosen/chosen.css" />
<script src="assets/grocery_crud/js/jquery-1.11.1.min.js"></script>
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

function filterMap()
{
  var text_filter = $("#station_filter").val();
  var grouping_filter = $("#grouping").val();
  var island_filter = $("#islands").val();
  var country_filter = $("#countries").val();
  //console.log("text: " + text_filter);
  //console.log("grouping: " + grouping_filter);
  //console.log("island: " + island_filter);
  //console.log("country: " + country_filter);
  $.getJSON(base_url + "samples/station_map/filter",{
    "filter": text_filter,
    "grouping": grouping_filter,
    "island": island_filter,
    "country": country_filter
  }, function(data) {
    deleteMarkers();
    data.map(function(e,i,a) {
      var point = new google.maps.LatLng(parseFloat(e.lat),parseFloat(e.lon));
      var marker_options = {
        map: map,
        position: point,
        title: e.station_name
      };
      createMarker_map(marker_options);
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
  updateSelect("#grouping");
  updateSelect("#islands");
  updateSelect("#countries");
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
</div>
<div id="bottom">
<?php echo $map['js']; ?>
<?php echo $map['html']; ?>
</div>