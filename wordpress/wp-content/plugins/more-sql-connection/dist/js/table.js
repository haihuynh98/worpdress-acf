jQuery(document).ready(function($) {

    const ajax_url = connecting_obj.ajax_url;
    const form = $('form#connection_list_table');

    form.find('input.toggle-status-action').click(function(){
        jQuery.ajax({
            type: 'POST',
            dataType: "json",
            url: ajax_url,
            data: {
                action: 'toggle_status',
                action_name: $(this).data('action_name'),
                item_id: $(this).data('connect_id')
            },
            success: function (response){
                
                    if (response.code == 200) {
                    location.reload();
                    } else {
                        
                    }

            }
        });
    });
  })