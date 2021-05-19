$(document).ready(function (){
	$('#hidset-save').click(function(){
		$('#hidset-errmsg').hide();
		$('#hidset-errtxt').hide();
		$('#hidset-yes').hide();
		$('#hidset-no').hide();
		$('#hidset-save').attr('disabled', 'disabled');
		$('#hidset-gif').show();
		var form = $('#hidset-form');
		var exp_process = $.post('?plugin=hidset&action=save', form.serialize(), function callback(data){
			$('#hidset-gif').hide();
			$('#hidset-save').attr('disabled', false);
			if(data.status != 'ok'){
				$('#hidset-errmsg').text(data.errors);
				$('#hidset-errtxt').show();
				$('#hidset-errmsg').show();
				$('#hidset-no').show();
			}else{
				for(var i in data.data){
					var tmp = data.data[i];
					if(typeof(tmp) == "object"){
						for(var j in tmp){
							$('#'+i+"_"+j).attr('value', tmp[j]);
						}
					}else{
						$('#'+i).attr('value', data.data[i]);
					}
				}
				$('#hidset-yes').show();
			}
		}, 'json');
	});
	
});