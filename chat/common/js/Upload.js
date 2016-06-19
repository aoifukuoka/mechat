
var start_upload = function(){ 

    var FormId = $("#upform");  
    var fd     = new FormData(FormId[0]);

    jQuery.ajax({
        async: true,
        xhr : function(){
            var XHR = $.ajaxSettings.xhr();
            return XHR;
        },
        url:  "common/php/upload.php",
        type: "post",
        data:fd,
        contentType: false,
        processData: false,
        async:false
			
    }).done(function( xml ) { 
			   
        $(xml).find("item").each(function(){
            var file = $(this).find("file").text();
            var flag = $(this).find("flag").text();
        });
			   
    });
}

