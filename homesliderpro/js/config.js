$(function() {
	function select_all(el) {
        if (typeof window.getSelection != "undefined" && typeof document.createRange != "undefined") {
            var range = document.createRange();
            range.selectNodeContents(el);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        } else if (typeof document.selection != "undefined" && typeof document.body.createTextRange != "undefined") {
            var textRange = document.body.createTextRange();
            textRange.moveToElementText(el);
            textRange.select();
        }
    }
	

	function reloadPage(currentURL) {
		window.location.href = currentURL;
	}
	
	window.manageAreas = function(areaData){
		var actualArea = [];
		//var areaData = {};
		
		var n = 0;
		
		function cangeTabTitles(){
			$('.areaTitle').each(function(){
				$(this).unbind('keyup');
				$(this).keyup(function(){
					var value = $(this).val();
					if (value != '')
						$('.mapsTabs:visible li[aria-selected="true"] a').text(value);
					else
						$('.mapsTabs:visible li[aria-selected="true"] a').text(tabEmptyName);
				})
				
			})
		}
		
		cangeTabTitles();
		
		$('#imgchooser img.preview').each(function(i){
				n = parseInt($(this).attr('data-areanum'));
				if (typeof(actualArea[i]) !== 'undefined') {
					actualArea[i].setOptions({disable:1});
					actualArea[i].cancelSelection();
				}
				actualArea[i] = $(this).imgAreaSelect({
					parent:$('#imgchooser'),
					handles: true,
					disable:1,
					active:n,
					resizeMargin : 10,
					instance: true,
					fadeSpeed : 150,
					onSelectEnd: function(img, selection){
						//console.log(img);
						var width = $(img).width();
						var height = $(img).height();
						// cet selection values in % for responsiveness;
						var imgLang = $(img).attr('data-lang');
						if (typeof areaData[imgLang] == 'undefined' || !areaData[imgLang])
							areaData[imgLang] = {};
						areaData[imgLang][n] = {};
						areaData[imgLang][n].left = 100/width*selection.x1;
						areaData[imgLang][n]['top'] = 100/height*selection.y1;
						areaData[imgLang][n]['selWidth'] = 100/width*selection.width;
						areaData[imgLang][n]['selHeight'] = 100/height*selection.height;
					}
				});
			})
		
		$('#add_area').click(function(e){
			e.preventDefault();
			var actualPic = $('#imgchooser img.preview').filter(':visible');
			actualPic.addClass('workingArea');
			actualPic.imgAreaSelect({enable:1});
			n = parseInt(actualPic.attr('data-areanum'));
		})
		
		var Tabs = [];
		$('.mapsTabs').each(function(i){
			Tabs[i] = $(this).tabs({
				activate: function( event, ui ) {
					var prevId = ui.newTab.attr('data-prev');
					$('.area_prev').removeClass('active');
					$('#'+prevId).addClass('active');
				}
			});
		})
		
		var areaTemplate = '<div class="area_prev" ><div class="iconA"></div></div>';
		
		$('#save_area').click(function(e){
			
			e.preventDefault();
			var updateNumber = parseInt($('#imgchooser .workingArea').attr('data-areanum'))+1;
			$('#imgchooser .workingArea').attr('data-areanum', updateNumber).removeClass('workingArea');
			for (i in actualArea)
				if (typeof(actualArea[i]) !== 'undefined') {
					actualArea[i].setOptions({disable:1});
					actualArea[i].cancelSelection();
				}
			for (lang in areaData) {	
				$('#area_prev_'+lang).html('');
				for (num in areaData[lang]){
					//console.log($('#area_'+num+'_l_'+lang).length);
					if ($('#area_'+num+'_l_'+lang).length < 1) { // check if tab exists
						$('#tabsLang_'+lang+' ul').append('<li class="tab" data-lang="'+lang+'" data-num="'+num+'" data-prev="prev_'+num+'_l_'+lang+'"><a href="#area_'+num+'_l_'+lang+'">'+tabEmptyName+'</a></li>');
						$('#tabsLang_'+lang).append('<div id="area_'+num+'_l_'+lang+'"><div class="textfields"><label>'+areaTitleLabel+'<input type="text" class="areaTitle" name="areas['+lang+']['+num+'][title]" value="" /></label><label>'+areaUrlLabel+'<input type="text" name="areas['+lang+']['+num+'][url]" value="" /></label><label>'+areaButtLabel+'<input type="text" name="areas['+lang+']['+num+'][button]" value="" /></label><label>'+areaDescLabel+'<textarea class="areadesc" name="areas['+lang+']['+num+'][description]"></textarea></label></div><div class="stylfields"></div><div class="hiddens"/></div>');
						
						var styles = '<fieldset><legend>'+areaStyleLegend+'</legend><label>'+areaStyleSimple+'<input type="radio" name="areas['+lang+']['+num+'][style]" checked="checked" value="simple" /></label><label>'+areaStyleBlock+'<input type="radio" name="areas['+lang+']['+num+'][style]" value="block" /></label></fieldset>';
						
						styles +='<fieldset><legend>'+areaColorLegend+'</legend><label>'+areaColorLight+'<input type="radio" name="areas['+lang+']['+num+'][color]" checked="checked" value="light" /></label><label>'+areaColorDark+'<input type="radio" name="areas['+lang+']['+num+'][color]" value="dark" /></label><label>'+areaColorTrans+'<input type="radio" name="areas['+lang+']['+num+'][color]" value="transparent" /></label></fieldset>';
						
						$('#area_'+num+'_l_'+lang+' .stylfields').html(styles);
					} 
					var contents = '<input type="hidden" name="areas['+lang+']['+num+'][left]" value="'+areaData[lang][num].left+'" />';
					contents += '<input type="hidden" name="areas['+lang+']['+num+'][top]" value="'+areaData[lang][num].top+'" />';
					contents += '<input type="hidden" name="areas['+lang+']['+num+'][selWidth]" value="'+areaData[lang][num].selWidth+'" />';
					contents += '<input type="hidden" name="areas['+lang+']['+num+'][selHeight]" value="'+areaData[lang][num].selHeight+'" />';
					$('#area_'+num+'_l_'+lang+' .hiddens').html(contents);
					
					$('#area_prev_'+lang).append( $(areaTemplate).css({
						left : areaData[lang][num].left+'%',
						top : areaData[lang][num].top+'%',
						width : areaData[lang][num].selWidth+'%',
						height : areaData[lang][num].selHeight+'%'
					}).attr({
						'data-tab' : 'area_'+num+'_l_'+lang,
						'id' : 'prev_'+num+'_l_'+lang
					})
					);
				}
			}
			$('.mapsTabs').each(function(i){
				Tabs[i].tabs( "refresh" );
				Tabs[i].tabs( {active:n} );
			})
			
			cangeTabTitles();
			has_area();
			//console.log(areaData);
		})
		
		//update has_area Value
		function has_area(){
			if ($('.area_prev').length > 0)
				$('#has_area').val('1');
			else
				$('#has_area').val('0');
		}
		
		$('#removeArea').click(function(e){
			e.preventDefault();
			$('.mapsTabs').each(function(i){
				if ($(this).is(':visible')){
					var tab = $('.slideAreaTabs li[aria-selected="true"]', $(this));
					var id = tab.attr('aria-controls');
					var langId = tab.attr('data-lang');
					var areanum = tab.attr('data-num');
					$('#'+id).remove(); //remove tab content
					$('.area_prev[data-tab="'+id+'"]').remove(); //remove preview from image
					tab.remove(); //remove tab (li element)
					// need to unset data from areaData object
					delete areaData[langId][areanum];
					var tempAreas = {};
					tempAreas[langId] = {};
					var c = 0;
					for (i in areaData[langId]) {
						tempAreas[langId][c] = areaData[langId][i];
						c++;
					}
					areaData[langId] = tempAreas[langId];
				}
			})
			has_area();
		})
	}

	$(document).ready(function(){ //doc ready!
		$.ajaxSetup({
			cache: false,
			xhrFields: {
			   withCredentials: true
			},
			crossDomain: true
		});
		
		var currentURL = window.location.href;
		
		currentURL = currentURL.replace(window.location.hash,'');
				
		var alerts = $('<div id="alerts"><span class="fa fa-times closeme"></span><span class="wait fa fa-cog fa-spin"></span><span id="alertmsg"></span></div>');
		$('body').append(alerts);
		
		var alertTimeout;
		
		function showalert(msg, f, undo){
			clearTimeout(alertTimeout);
			var $al = $('#alerts');
			if (msg == '')
				msg = '...';
			$('#alertmsg').text(msg);
			$al.fadeIn('fast');
			if (typeof f == "function") {
				var alertButtons = $('<div class="alertButtons"></div>');
				var accept = $('<span class="accept fa fa-check"></span>');
				var cancel = $('<span class="cancel fa fa-times"></span>');
				$('#alertmsg').append(alertButtons);
				alertButtons.append(accept);
				if (undo){
					alertButtons.append(cancel);
				}
				accept.click(function(){
					f();
				})
				cancel.click(function(){
					hidealert();
				})
			} else {
				alertTimeout = setTimeout(function(){hidealert()},1500)
			}
		}
			
		function hidealert(){
			$('#alerts').fadeOut('fast',function(){
				$('#alertmsg').html('');
			});
		}
		
		$('#alerts .closeme').click(function(){
			hidealert();
		})
		
		//reposition
		var $mySlides = $(".slides");
		$mySlides.each(function(){
		$(this).sortable({
			placeholder: "ui-state-highlight",
			axis: "y",
			cursor: "move",
			update: function() {
				var order = $(this).sortable("serialize") ;
				//console.log(order)
				$.post(ajaxUrl+"&action=updateSlidesPosition", order);
				}
			});
		})
		$mySlides.hover(function() {
			$(this).css("cursor","move");
			},
			function() {
			$(this).css("cursor","auto");
		});
		function activationSetup() {
			$('.activationForm').each(function(){
				$(this).submit(function(e){
					e.preventDefault();
					var action = $(this).attr('id');
					var message = $('.message', this).text();
					
					showalert(message,function(){
						$.post(ajaxUrl+"&action="+action, function(data){
							//console.log(data);
							showalert(data, function(){reloadPage(currentURL)});		
							//showalert(data);		
						})
					}, true);
				})
			})
		}

		activationSetup();
		
		$('#showAct').click(function(e){
			e.preventDefault();
			$('table.activations').fadeToggle();
		})
		
		// permissions
		$('#accessEdit input').each(function(){
			$(this).change(function(){
				var data = $('#accessEdit').serialize();
				$.post(ajaxUrl+"&action=editPermissions&"+data, function(data){
					//alert(data);
					reloadPage(currentURL);
				})
			})
		})
				
		$('.resConf').each(function(){
			var oldVal = $(this).val();
			$(this).blur(function(){
				var newVal = $(this).val();
				if (newVal != oldVal) {
					var hook = $(this).attr('data-hook');
					$('.batchResize[data-hook="'+hook+'"]').slideDown();
				}
			})
		})
		
		// update slider Configuration
		$('#sliders_config').submit(function(e){
			e.preventDefault();
			var data = $(this).serializeArray();
			//console.log(data);
			$.post(ajaxUrl+"&action=updateConfiguration", data, function(data){
				showalert(data);
			});
		})
		
		//resize images
		$('.batchResize').click(function(e){
			e.preventDefault();
			var hook = $(this).attr('data-hook');
			var sendhook = {
				'hookname' : hook
			}
			var data = $('#sliders_config').serializeArray();
			$.post(ajaxUrl+"&action=updateConfiguration", data, function(data){
				showalert(data);
				$.post(ajaxUrl+"&action=resizeImages", sendhook, function(data){
					showalert(data);
					$('.batchResize[data-hook="'+hook+'"]').slideUp();
				});
			});
		})
		
		//change status
		$('.changeStatus').each(function(){
			$(this).click(function(e){
				e.preventDefault();
				var clicked = $(this);
				var slideId = clicked.attr('data-slide-id');
				$.post(ajaxUrl+"&action=changeStatus&id_slide="+slideId, function(data){
					var response = jQuery.parseJSON(data);
					if (response.success == 1) {
						$('i.fa',clicked).toggleClass('fa-times').toggleClass('fa-check');
					}
					showalert(response.message);
				});
			})
		})
		
		// update DB
		$('#updateDb').click(function(e){
			e.preventDefault();
			$.post(ajaxUrl+"&action=updateDB", function(data){
				showalert(data, function(){reloadPage(currentURL);});
				
			});
		})
		// update Module
		$('#moduleUpdate').submit(function(e){
			e.preventDefault();
			$.post(ajaxUrl+"&action=updateModule", function(data){
				showalert(data, function(){reloadPage(currentURL);});
			});
		})
		
		if ($('.updateFakeMessage').length > 0) {
			var updates = parseInt($('#box-update-modules .value').text());
			$('#box-update-modules .value').text(updates+1);
		}
		
		/** allow only numbers **/
		
		$(".catnumber").keydown(function (e) {
			//console.log(e.keyCode);
			if ((e.keyCode > 47 && e.keyCode < 58) //standard nums
				|| (e.keyCode > 95 && e.keyCode < 106) //block nums
				|| e.keyCode == 8 //canc
				|| e.keyCode == 46 //del
				|| (e.keyCode >36 && e.keyCode < 41)) //arrows
				return true;
			return false;
		})
		
		//select hook code with a single click
		$('.hookCode').each(function(){
			$(this).click(function(){
				select_all($(this)[0]);
			})
		})
		
		var Index, CurrentVal;
		var $catTree = $('.catTree');
		$catTree.append('<i class="smallClose fa fa-times"/>')
		var overlay = $('#overlayer');
		$('.catnumber').each(function(i){
			$(this).click(function(){
				Index = i;
				CurrentVal = $(this).val();
				overlay.append($catTree);
				$('li i', $catTree).removeClass('fa-check-circle-o').addClass('fa-circle-o');
				$('li[data-cat="'+CurrentVal+'"] i', $catTree).removeClass('fa-circle-o').addClass('fa-check-circle-o');
				$catTree.addClass('processed');
				
				overlay.fadeIn();
			})
		})
		
		$('.closeme', $catTree).click(function(){
			overlay.fadeOut();
			$('.catnumber').eq(Index).val('');
		});
		
		$('.smallClose', $catTree).click(function(){
			overlay.fadeOut();	
		});
		$('li', $catTree).click(function(){
			overlay.fadeOut();
			$('.catnumber').eq(Index).val($(this).attr('data-cat'));
		})
		
		//animation configs
		var cont = $('.slideChooserCont');
		var conf = $('.position');
		var chooseButtons = $('.slideChoose');
		conf.not('.open').css({
			opacity:0
		})
		var fixsize = $('.fixsize')
		chooseButtons.each(function(){
			$(this).click(function(e){
				e.preventDefault();
				var height = fixsize.height();
				chooseButtons.removeClass('active');
				$(this).addClass('active');
				fixsize.css('height',height+'px');
				var Target = $(this).attr('href');
				conf.not(Target).stop(true,true).animate({
					opacity : 0
				}, 300,function(){
					$(this).hide();
					$(Target).show().stop(true,true).animate({
						opacity : 1
					}, 300, function(){
						fixsize.css('height',$(Target).height());
					});
				});
				
			})
		})
		
		
		
				
	}) // end doc ready
	
	
}(jQuery));
