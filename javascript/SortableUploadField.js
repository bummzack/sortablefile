;(function($) {
	$(function(){
		$.entwine('ss', function($) {
			$(".sortableupload.ss-uploadfield ul.ss-uploadfield-files").entwine({
				onmatch: function() {
					// enable sorting functionality
					var self = $(this);
					
					self.sortable({ 
						handle: ".ss-uploadfield-item-preview",
						axis: "y",
						start: function(event, ui){
							// remove overflow on container
							ui.item.data("oldPosition", ui.item.index())
							self.css("overflow", "hidden");
						},
						stop: function(event, ui){
							// restore overflow
							self.css("overflow", "auto");
						},
						update: function(event, ui){
							var url = ui.item.find('button.ss-uploadfield-item-remove').data('href');
							if(!url){
								url = ui.item.find('button.ss-uploadfield-item-delete').data('href');
							}
							// horrible hack to get a valid endpoint for our sort query
							// still less cumbersome than overriding the required methods in UploadField though
							if(url.match(/\/(remove|delete)\?/)){
								url = url.replace(/\/(remove|delete)\?/, "/sort?");
							} else {
								// seems like we have an invald url
								return;
							}

							$.get(url, { 
								newPosition: (ui.item.index()), 
								oldPosition: ui.item.data("oldPosition") 
							}, function(data, status){
								//window.console.log(data);
							});
						}
					});
					this._super();
				},
				onunmatch: function(){
					// clean up
					try {
						$(this).sortable("destroy");
					} catch(e){};
					this._super();
				}
			});
		});
	});
}(jQuery));
