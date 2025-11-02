var item_type;

//広告主に応じて広告を読み込む
function add_adwares(elm){
var form = elm.form;
elm.blur();

  $(".adwares_span",form).html('<img height="16px" width="50px" src="./img/ajax-minibar.gif" />');
  
   jQuery.ajax({
      url : 'api.php' ,
      type : 'POST',
      dataType : "text",
	  data : "post=load_adwares&js=true&type="+item_type,
      success : function(res){ $(".adwares_span",form).html(res);setDisable("adwares",form);},
      error   : function(xml, status, e){$(".adwares_span",form).attr('id','error_msg'); $(".adwares_span",form).html("通信エラー"); }
    });

  $(".s_adwares_span",form).html('<img height="16px" width="50px" src="./img/ajax-minibar.gif" />');
  
   jQuery.ajax({
      url : 'api.php' ,
      type : 'POST',
      dataType : "text",
	  data : "post=load_secret_adwares&js=true&type="+item_type,
      success : function(res){ $(".s_adwares_span",form).html(res);setDisable("s_adwares",form);},
      error   : function(xml, status, e){$(".s_adwares_span",form).attr('id','error_msg'); $(".s_adwares_span",form).html("通信エラー"); }
    });
}

function setDisable(name,form)
{
	elm  = $( "." + name + "_span select" , form );

	for( i = 0 ; i < form.adwares_type.length ; ++i )
	{
		if( false != form.adwares_type[ i ].checked )
			{ setValue = ''; }
		else
			{ setValue = 'disabled'; }

		if( 'adwares' == form.adwares_type[ i ].value )
		{
			if( 'adwares' == name )
				{ elm.attr( 'disabled' , setValue ); }
		}

		if( 'secretAdwares' == form.adwares_type[ i ].value )
		{
			if( 's_adwares' == name )
				{ elm.attr( 'disabled' , setValue ); }
		}
	}
}