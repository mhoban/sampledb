var master_taxa_list = null;
var working_taxa_list = null;

function validateObservations()
{
  var good = false;
  $("#observations .datarow").each(function() {
    var taxon = $(this).find(".taxselect").val();
    var count = $(this).find(".countinput").val();
    if ((taxon && taxon.length) && (count && count.length)) {
      $(this).data("valid_row",true);
      good = true;
      //return false;
    } else {
      $(this).data("valid_row",false);
    }
  });
  return good;
}

function refreshList(data,taxSelect=null,observations=null)
{
  if (data != null) {
    if (!taxSelect)
      taxSelect = ".taxselect";
    $(taxSelect).each(function() {
      if (!$(this).data("setup")) {
        $(this).data("setup",true);
        $(this).chosen();
        $(this).on("change",function(e) {
          $(this).data("prev_sel",$(this).val());
          var $inputs = $(this).closest("form").find(":input");
          var $ip = $inputs.eq($inputs.index(this)+2);
          setTimeout(function(ip) { ip.focus(); },0,$ip); // not sure why this settimeout hack works but it does
        });
      }
      var $items = data.map(function(e,i,a) {
        return ($("<option>").attr('value',e.taxon_id).text(e.genus + " " + e.species));
      });
      $(this).empty().append($items);
      $(this).val($(this).data("prev_sel"));
      $(this).trigger("chosen:updated");
    });
  }
}

function updateTaxa(qry=true,taxSelect=null,observations=null)
{
  if (qry) {
    $.getJSON(base_url + "samples/taxa/json",function(data) {
      master_taxa_list = data;
      refreshList(master_taxa_list,taxSelect,observations);
    }); 
  } else {
    refreshList(master_taxa_list,taxSelect,observations);
  }
}

function newRow(type="form",row=null) 
{
  if (type == "form") {
    var html = "<tr class='datarow'>"
    html += "<td><a class='delrow' title='Remove row' href=''>[x]</a></td>"
    html += "<td><select class='taxselect' name='species[]' style='width: 200px'></select></td>"
    html += "<td><input type='text' class='sizeinput' name='size[]' size=25></td>"
    html += "<td><input type='text' class='countinput' name='count[]' size=25></td>"
    html += "</tr>";
    var $newRow = $(html).appendTo($("#observations"));
    var $last_td = $("#observations tr:last-child td:last-child").append("&nbsp;").append($("#newrow").detach());
    if (!$("#newrow").length) {
      $("<a id='newrow' href='' title='Add new observation'>[+]</a>")
        .on("click",function(e) {
          var $r = newRow();
          updateTaxa(false,$r.find(".taxselect"));
          e.preventDefault();
        })
        .appendTo($last_td);
    }
    $(".countinput, .sizeinput").off("keydown");
    $(".countinput, .sizeinput").on("keydown",function(e) {
      if (e.which == 13 || (e.which == 9 && !e.shiftKey)) {
        var $inputs = $(this).closest("form").find(":input");
        $inputs.eq($inputs.index(this)+1).focus().trigger("chosen:activate");
        e.preventDefault();
      }
    });
    $(".countinput:last").on("keydown",function(e) {
      if ((!e.shiftKey && e.which == 9) || e.which == 13) {
        var $r = newRow();
        updateTaxa(false,$r.find(".taxselect"));
        $(".taxselect:last").trigger("chosen:activate");
        e.preventDefault();
      }
    });
    $newRow.find(".delrow").on("click",function(e) {
      e.preventDefault();
      if ($(".datarow").length > 1) {
        // does this result in some weirdly growing number of &nbsp's at the end of some lines?
        var $row = $(this).closest("tr").detach();
        if ($row.find("#newrow").length) {
          $("#observations tr:last-child td:last-child")
            .append("&nbsp;")
            .append($row.find("#newrow"));
        }
      }
    });
    if (row && (("taxon_id" in row) && ("count" in row) && ("size" in row))) {
      if (parseInt(row.size) === 0)
        row.size = "";
      $newRow.find(".taxselect").val(row.taxon_id).data("prev_sel",row.taxon_id).trigger("chosen:updated");
      $newRow.find(".sizeinput").val(row.size);
      $newRow.find(".countinput").val(row.count);
    }
    return $newRow;
  } else if (type == "view") {
    if (row && (("genus" in row) && ("species" in row) && ("count" in row) && ("size" in row))) {
      var html = "";
      if (parseInt(row.size) === 0)
        row.size = "";
      html += "<tr>";
      html += "<td width='1%'></td>";
      html += "<td width='33%'>" + row.genus + " " + row.species + "</td>";
      html += "<td width='33%'>" + row.size + "</td>";
      html += "<td width='33%'>" + row.count + "</td>";
      html += "</tr>";
      var $newRow = $("#observations").append($(html)).show();
      return $newRow;
    }
  }
}

function fcSubmit(e) 
{
  if (validateObservations()) {
    //preload the form with my form stuff;
    $("#crudForm :input[name|=fc]").remove();
    $("#obsform :input").each(function() {
      if ($(this).closest(".datarow").data("valid_row")) {
        if ($(this).attr("name")) {
          var n = "fc-" + $(this).attr("name");
          var v = $(this).val();
          $("<input type='hidden'>")
            .attr("name",n)
            .val(v)
            .appendTo($("#crudForm"));
          return true;
        }
      }
    });
    return true;
  } else {
    e.preventDefault();
    form_error_message("<p>Please enter some fish count/size data");
  }
}

function editSetup(fcid)
{
  try {
    if (parseInt(fcid) > 0) {
      $.getJSON(base_url + "samples/fishcount/observations/" + fcid,function(data) {
        $("#observations tr:not(.obs-header)").remove();
        $(data).each(function() {
          newRow("form",this);
        })
        updateTaxa(true);
      });
      $("#save-and-go-back-button").on("click",fcSubmit);
      $("#form-button-save").on("click",fcSubmit);
    }
  } catch(e) { 
    console.log(e);
  }
}

function addSetup()
{
  updateTaxa(true); 
  $('#newtax').on("click",function(e) {
    try {
      addTaxon(function(data) {
        updateTaxa(true);
      });
    } catch(ex) {}
    e.preventDefault();
  });
  newRow();
  $("#save-and-go-back-button").on("click",fcSubmit);
  $("#form-button-save").on("click",fcSubmit);
}

function listSetup()
{
  $(".ajax_list").on("click","tr:not(.hDiv)",function() {
    $(".trSelected").removeClass("trSelected");
    $(this).addClass("trSelected");
    if (!$(this).data("fishcount_id")) {
      var uri = $(this).find(".delete-row").attr("href");
      var segment = uri.substr(uri.lastIndexOf("/")+1);
      $(this).data("fishcount_id",segment);
    }

    var fcid = $(this).data("fishcount_id");
    $.getJSON(base_url + "samples/fishcount/observations/" + fcid,function(data) {
      $("#observations tr:not(.obs-header)").remove()
      $(data).each(function() {
        newRow("view",this);
      });
    });
  });
}
/* Page setup */
$(function() {
  //this stuff is taken care of within the fishcount template, depending on the view mode
});

