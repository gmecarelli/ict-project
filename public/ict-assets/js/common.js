$("document").ready(function() {
    $(document).on('click', ".btnDel", function(e) {
        var clickId = e.currentTarget;
        var data_type = $("#"+clickId.id).data('form');
        var div_id = data_type+"_child-"+clickId.id.replace('btnDelForm_','');
        console.log(div_id);
        $('div#'+div_id).remove();
    });

    
});