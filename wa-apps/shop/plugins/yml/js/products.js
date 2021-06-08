$(document).on('change click','input#extern_optom', function(e){
    $(document).find('#extern_optom_save_button i').css('display', 'inline-block');
});
$(document).on('click', '#extern_optom_save_button i', function(e){
    e.preventDefault();
    e.stopImmediatePropagation();
    
    var eid = $(document).find('input#extern_optom').val();
    var iid = $(document).find('input[name=internid]').val();
    var datas = 'extid='+eid+'&intid='+iid;
    
    $.ajax({
        type: 'post',
        url: '?plugin=yml&action=products&method=editextid',
        data: datas,
        success: function(r){
            $('#extern_optom_save_button i').removeClass('disk');
            $('#extern_optom_save_button i').addClass('yes');
            setTimeout(function(){
                $(document).find('#extern_optom_save_button i').css('display', 'none');
                $('#extern_optom_save_button i').removeClass('yes');
                $('#extern_optom_save_button i').addClass('disk');
            }, 2000);
            
        }
    });   
});