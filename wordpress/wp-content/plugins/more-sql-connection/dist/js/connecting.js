jQuery(document).ready(function($) {

    const ajax_url = connecting_obj.ajax_url;

    $('#test_connection').click(function(){
        
        jQuery.ajax({
            type: 'POST',
            dataType: "json",
            url: ajax_url,
            data: {
                action: 'test_connection',
                drive: $('select#sql_drive').find(':selected').val(),
                host: $('input#host_name').val(),
                port: $('input#port').val(),
                db_user: $('input#db_user').val(),
                db_password:  $('input#db_pass').val(),
                db_name: $('input#db_name').val()
            },
            success: function (response){
                
                    if (response.code == 200) {
                       $('input[type="submit"]').removeAttr('disabled');
                       $('p.message_connection').css('color', 'green');
                    } else {
                        $('input[type="submit"]').attr('disabled','disabled');
                        $('p.message_connection').css('color', 'red');
                    }
                    $('p.message_connection').text(response.message);
            }
        });
    });
  })