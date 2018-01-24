function addTaxon(onOk=null,onCancel=null)
{
  var url = base_url + "samples/taxa/add/dlg";
  var $dialog = $("<div></div>")
    .attr("id","taxDlg")
    .dialog({
      autoOpen: false,
      modal: true,
      resizable: false,
      height: 325,
      width: 580,
      title: "Add taxon",
      buttons: {
        Ok: function() {
          var $that = $(this);
          $(this).find("#crudForm").ajaxSubmit({
            dataType: "json",
            success: function(data) {
              if (data.success) {
                $that.dialog("close");
                if (onOk !== null) {
                  onOk(data);
                }
              } else {
                $that.parent().find("#statusBar").css("color","red").html("Please fill out all fields");
              }
            }          
          });
          //$(this).dialog("close");
        },
        Cancel: function() {
          if (onCancel !== null) {
            onCancel();
          }
          $(this).dialog("close");
        }
      },
      close:function(event,ui) {
        $(this).dialog("destroy").remove();
      },
      open: function(event,ui) {
        $("#taxDlg").css("overflow","visible");
        $.get(url,function(data) {
          $("#taxDlg").html(data);
          $("<div>")
            .css("margin",".5em .4em .5em 0")
            .css("float","left")
            .append($("<div>")
              .attr("id","statusBar")
              //.attr("class","ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only")
              .css("padding",".4em 1em"))
              //.css("float","left")
              //.css("border","1px solid black"))
            .appendTo($(".ui-dialog-buttonpane"));
          $("#taxDlg").find('#hdrlist').hide();
          $("#taxDlg").find('.pDiv').hide();
          $("#taxDlg").find('.ptogtitle').hide();
          $("#taxDlg").find('.headsep').hide();
        });
      }
    });
  $dialog.dialog("open");    
}
