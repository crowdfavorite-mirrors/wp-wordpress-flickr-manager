
jQuery(function( $ ) {
	var loadingImg = '<img src="{0}" alt="{1}" />'.format(loadingImage, loadingText);
	var currentPage = 1;
	var photoPanel = $( '#photoPanel' );
	var insertPanel = $( '#insertPanel' );
	var slideInterval = 500;
	
	var wfmAJAX = {
	
		post: function(func, params, callback, type) {
			var settings = $.extend({
				func: func
				,'_ajax_nonce': WFMAjaxNonce
				,tab: WFMTab
			}, params);
			
			if(!type) type = '';
		
			$.post(WFMAjaxURL,settings,callback,type);
		}
		
		,loadPhotos: function(filter, everyone) {
			photoPanel.html(loadingImg);
			
			this.post("func.getPhotos", { filter: filter, page: currentPage }, this.displayPhotos, 'json');
		}
		
		,loadPhotoset: function(photoset) {
			photoPanel.html(loadingImg);
			
			if(!photoset)
				photoset = '';
			
			this.post("func.getPhotoset", { 'photoset': photoset, page: currentPage,  }, function(data) {
				wfmAJAX.displayPhotos(data);
				
				var setSelect = $('<select id="photoset"></select>');
				
				$.each(data.photosets, function(k, v) {
					setSelect.append('<option value="{0}">{1}</option>'.format(v.id, v.title));
				});
				
				setSelect.change(function() {
					wfmAJAX.loadPhotoset($(this).val());
				}).val(photoset);
				
				var insertSet = $('<input type="button" id="insertSet" value="{0}" />'.format(data.insertText)).click(function() {
					wfmAJAX.insertPhotoset($('#photoset').val());
				});
				
				$('#insertSet').remove();
				$('#filter,#photoset').before(setSelect).after(insertSet).remove();
			}, 'json');
		}
		
		,displayPhotos: function(photoJSON) {
			var html = "";
			
			for(var i=0; i < photoJSON.photos.length; i++) {
				var photo = photoJSON.photos[i];
				
				html += '<img src="{0}" alt="{1}" class="panelImage" />'.format(photo.square, photo.title);
			}
			
			photoPanel.html(html);
			
			photoPanel.find('.panelImage').click(function() {
				wfmAJAX.insertPhoto(this);
			});
			
			Paginate(currentPage, photoJSON.pages);
		}
		
		,insertPhoto: function(image) {
			var photoid = GetPhotoID(image);
			
			OpenInsertPanel();
			var container = insertPanel.find('.inner');
			
			this.photoInfo(photoid, container, function() {
				if(WFMTab == 'flickr_public') {
					container.find('span.ownerControls').remove();
				}
				
				$('.deletePhoto', container).unbind('click').click(function() {
					wfmAJAX.deletePhoto(photoid, container, function() {
						$(image).hide();
						insertPanel.slideUp( slideInterval );
					});
				});
			});
			
		}
		
		,insertPhotoset: function(photoset) {
			OpenInsertPanel();
			var container = insertPanel.find('.inner');
			
			this.post("func.photosetInfo", {'photoset': photoset}, function(data) {
				container.html(data);
				
				$('#sendSet').click(function() {
					SendPhotosetToEditor(photoset);
				});
				
				var overlay = $('.enableOverlay', container).change(function() {
					$('.overlayRow', container).toggle($(this).is(':checked'));
				});
				
				$('.overlayRow', container).toggle(overlay.is(':checked'));
			});
		}
		
		,savePhoto: function(photoid, container, callback) {
			var title = container.find('#title').val();
			var tags = container.find('#tags').val();
			var desc = container.find('#description').val();
			
			container.html(loadingImg);
			
			this.post("func.saveInfo"
				,{
					pid: photoid
					,title: title
					,tags: tags
					,description: desc
				},
				function() { 
					wfmAJAX.photoInfo(photoid, container, callback);
				}
			);
		}
		
		,deletePhoto: function(photoid, container, callback) {
			if(confirm(deleteConfirm)) {
				$(container).html(loadingImg);
				
				if(typeof(callback) !== 'function') {
					callback = function() {};
				}
				
				this.post("func.deletePhoto", { pid: photoid }, callback);
			}
		}
		
		,photoInfo: function(photoid, container, callback) {
			container.html(loadingImg);
			
			wfmAJAX.post("func.photoInfo", {pid: photoid}, function(data) {
				container.html(data);
				
				// Insert button
				$('.insertPhoto', container).click(function() {
					SendPhotoToEditor(photoid, container);
				});
				
				// Save button
				$('.savePhoto', container).click(function() {
					wfmAJAX.savePhoto(photoid, container, callback);
				});
				
				// Delete button
				$('.deletePhoto', container).click(function() {
					wfmAJAX.deletePhoto(photoid, container, function() {
						container.slideUp(slideInterval);
					});
				});
				
				var overlay = $('.enableOverlay', container).click(function() {
					$('.overlayRow', container).toggle($(this).is(':checked'));
				});
				
				$('.overlayRow', container).toggle(overlay.is(':checked'));
				
				if(typeof(callback) === 'function') {
					callback();
				}
			});
		}
		
	};
	
	$(document).ready(function() {
		
		photoPanel.html(loadingImg);
		
		if(typeof(WFMTab) !== 'undefined') {
			switch(WFMTab) {
				case 'flickr_sets':
					$('#filter').hide();
					wfmAJAX.loadPhotoset();
					break;
				default:
					wfmAJAX.loadPhotos("");
					break;
			}
		}
		
		
		$('#filter').click(function() {
			var filter = $(this);
			
			filter.css({
				color: "black"
				,fontStyle: "normal"
			});
			
			filter.attr('value', '');
			filter.unbind('click');
		}).keypress(function(e) {
			if(e.which == 13) {
				wfmAJAX.loadPhotos(GetFilter());
				event.preventDefault();
			}
		});
		
		RegisterUploadHandlers();
	});
	
	function RegisterUploadHandlers() {
		var flickrRegex = /flickr_(\d+)/;
					
		//Upload Handler
		if(window.uploadSuccess) {
			var orig = window.uploadSuccess;
			window.uploadSuccess = function(fileObj, serverData) {
				var matches = serverData.match(flickrRegex);
				if(matches[1]) {
					// Successful upload
					var photoid = matches[1];
					var container = $('#media-item-' + fileObj.id);
					
					window.WFMTab = 'flickr_upload'
					wfmAJAX.photoInfo(photoid, container);
				} else {
					orig(fileObj, serverData);
				}
			}
		}
		
		var errorPanel = $('#media-upload-error');
		if(errorPanel.size() > 0) {
			var errorText = errorPanel.html();
			
			if(flickrRegex.test(errorText)) {
				errorPanel.hide();
				
				var matches = errorText.match(flickrRegex);
				var photoid = matches[1];
				
				var container = $('<div id="media-item-{0}"></div>'.format(photoid)).appendTo($('#media-items'));
				
				window.WFMTab = 'flickr_upload'
				wfmAJAX.photoInfo(photoid, container);
			}
		}
        
        // Save all
        $('input#save.button').click(function() {
            $('.savePhoto').each(function() {
                $(this).click();  
            })
            return false; 
        });
	}
	
	function OpenInsertPanel() {
	
		insertPanel.find('.inner').html(loadingImg);
		
		insertPanel.find('.close').click(function() {
			// Hide - slide up.
			insertPanel.slideUp( slideInterval );
			
			return false;
		});
		
		// Show - slide down.
		insertPanel.slideDown( slideInterval );
	}
	
	function SendPhotosetToEditor(photosetID) {
		var doc = $(document);
		
		var shortcode = ' [flickrset id="{0}" thumbnail="{1}" photos="{2}" overlay="{3}" size="{4}"] '.format(photosetID
							,doc.find("input[name='imageSize']:checked").val()
							,doc.find("input[name='numPhotos']").val()
							,doc.find("input[name='enableOverlay']").is(":checked")
							,doc.find("input:radio[name='overlaySize']:checked").val().replace(' ','_'));
		
		SendToEditor(shortcode);
	}
	
	function SendPhotoToEditor(photoID, container) {
		var doc = (!container) ? $(document) : $(container);
		
		var shortcode = ' [flickr id="{0}" thumbnail="{1}" overlay="{2}" size="{3}" group="{4}" align="{5}"] '.format(photoID
							,doc.find("input:radio[name=imageSize]:checked").val()
							,doc.find("input[name='enableOverlay']").is(":checked")
							,doc.find("input:radio[name='overlaySize']:checked").val().replace(' ','_')
							,doc.find('#setName').val()
                            ,doc.find("input[name='align']:checked").val());
		
		SendToEditor(shortcode);
	}
	
	function SendToEditor(html)
	{
		var win = window.opener ? window.opener : window.dialogArguments;
		if ( !win ) win = top;
		tinyMCE = win.tinyMCE;
		var edCanvas = win.document.getElementById('content');
		
		if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
			ed.focus();
			if (tinyMCE.isIE)
				ed.selection.moveToBookmark(tinyMCE.EditorManager.activeEditor.windowManager.bookmark);
	
			ed.execCommand('mceInsertContent', false, html);
		} else if ( typeof edInsertContent == 'function' ) {
			edInsertContent(edCanvas, html);
		} else {
			var jCanvas = $(edCanvas);
			jCanvas.val(jCanvas.val() + html);
		}
	}
	
	function Paginate(page, pages) {
		var start = 1;
		var end = pages;
		var cont = $('#pagination').html('');
		
		if(pages > 3) {
			start = page - 3;
			BuildNav(cont, 1);
			
			if(start <= 2)
				start = 2;
			else
				cont.append('<span>...</span>');
			
			if(pages > page + 3) {
				end = page + 3;
			}
		}
		
		for(var i=start; i <= end; i++) {
			BuildNav(cont, i);
		}
		
		if(end < pages) {
			if(end < pages - 1) 
				cont.append('<span>...</span>');
				
			BuildNav(cont, pages);
		}
	}
	
	function BuildNav(cont, page) {
		var link = $('<a href="#">{0}</a>'.format(page)).click(function() {
			currentPage = parseFloat(this.innerHTML);
			
			wfmAJAX.loadPhotos(GetFilter());
		}).appendTo(cont);
		
		if(page == currentPage) {
			link.addClass('current');
		}
	}
	
	function GetFilter() {
		var filter = $('#filter').val();
		
		return (filter == "Search") ? "" : filter;
	}
});
