jQuery(function($){
	$(document).on('change', '#new_user_type_topbar', function(){

		var currentVal = $(this).val();
		if(currentVal == 1 || currentVal == 0)
		{
			$('#login_document').hide();
		}
		else
		{
			$('#login_document').show();
		}
	});

	
	jQuery('#wp-submit-register_topbar').on('click',function(e){
     	e.preventDefault();
     	var currentD = $('#new_user_type_topbar').val();
     	
     	if(currentD != 1 &&	 currentD != 0)
     	{
     		var fd = new FormData();
		    var files_data = $('#login_document'); 
		   
		    
			$.each($(files_data), function(i, obj) {
				$.each(obj.files,function(j,file){
				    fd.append('files[' + j + ']', file);
				})
			});
     		
     		var interval = setInterval(function(){
     			if($("#register_message_area_topbar .login-alert").text() == 'Your account was created and you can login now!')
	     		{
	     			clearInterval(interval);
					// our AJAX identifier
					fd.append('action', 'cvf_upload_files');  
						// uncomment this code if you do not want to associate your uploads to the current page.
						//fd.append('post_id', id);
						//ajax data
					$.ajax({
						type: 'POST',
						url: ajaxurl,
						data: fd,
						contentType: false,
						processData: false,
						success: function(response){
						  	console.log(response);
						}
					});
	     		}
	     		
     		});
     	}
     	
	 	
  	});
	/*User dashboard upload certificate code*/
	jQuery('#certificate-uploader').on('click',function(e){
     	e.preventDefault();
     	
 		var fd = new FormData();
	    var files_data = $('#certificate'); 
	   	var id = $('#certificate_id').val();
	    
		$.each($(files_data), function(i, obj) {
			$.each(obj.files,function(j,file){
			    fd.append('files[' + j + ']', file);
			})
		});
 		
 		fd.append('action', 'cvf_upload_certificate_files');  
		// uncomment this code if you do not want to associate your uploads to the current page.
		fd.append('post_id', id);
		//ajax data
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: fd,
			contentType: false,
			processData: false,
			beforeSend: function(){
			 $("#loading_display").show();
			},
			complete: function(){
			 $("#loading_display").hide();
			},
			success: function(response){
			  	console.log(response);
			  	window.location.reload();
			}
		});
  	});
	/*End user dashboard upload certificate code*/
	/*Start renew membership code here*/
	jQuery('#renew_membership_old').on('click',function(e){
		var userID = $(this).attr('data-id');
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: "action=renew_membership_one_month&id="+userID,
			success: function(response){
			  	$('#renew_membership_old').hide();
			  	window.location.reload();
			}
		});
	});
	/*End renew membership code here*/
	/* Open and close menu in mobile section 17-03-2021 */	
	$('#menu-item-20722').on( 'click', function(event) {
        jQuery('.mobile-trigger-user').trigger('click');
        jQuery('#widget_login_mobile').trigger('click');
    });

    $('#menu-item-20723').on( 'click', function(event) {
        jQuery('.mobile-trigger-user').trigger('click');
        jQuery('#widget_register_mobile').trigger('click');

    });
    /*Endsection Open and close menu in mobile section 17-03-2021 */	

    var location_url = window.location.pathname;
    if(location_url == '/dashboard-profile-page/')
    {
    	
    	if($('.add-estate #userphone').val().length == 0 )
    	{
    		$('.add-estate #userphone').val('+243');
    		
    	}
    	if($('.add-estate #usermobile').val().length == 0)
    	{
    		$('.add-estate #usermobile').val('+243');
    	}
    	
    } 
    /*Sold property status ajax 27-03-2021*/
   jQuery('.sold_property').on('click',function(event){
   	  	event.preventDefault();
      	event.stopPropagation();
 		var prop_id     =   $(this).attr('data-postid');
 		var cat_id     	=   $(this).attr('data-cat');
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
			  'action'       :   'sold_property_update',
			  'pid'     	 :   prop_id,
			  'cid'      	 :   cat_id,
			},
		beforeSend: function(){
		 	$("#loader_display").css("display", "block");
		},
		complete: function(){
	 		$("#loader_display").css("display", "none");
		},
		success: function(response){
			
			window.location.reload();
		}
		});
   });
   /*End Sold property status ajax 27-03-2021*/
});




