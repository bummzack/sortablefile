(function($) {
	$.entwine('ss', function($) {
		$("ul.ss-uploadfield-files").entwine({
			onmatch: function() {
				// enable sorting functionality
				var self = $(this);
				self.sortable({ 
					handle: ".ss-uploadfield-item-preview",
					axis: "y",
					start: function(event, ui){
						// remove overflow on container
						self.css("overflow", "hidden");
					},
					stop: function(event, ui){
						// restore overflow
						self.css("overflow", "auto");
					},
					update: function(event, ui){
						var url = ui.item.find('button.ss-uploadfield-item-remove').data('href');
						// horrible hack to get a valid endpoint for our sort query
						// still less cumbersome than overriding the required methods in UploadField though
						if(url.match(/\/remove\?/)){
							url = url.replace(/\/remove\?/, "/sort?");
						} else {
							// seems like we have an invald url
							return;
						}
						
						$.get(url, { newPosition: (ui.item.index() + 1) }, function(data, status){
							
						});
					}
				});
				this._super();
			},
			onunmatch: function(){
				// clean up
				$(this).sortable("destroy");
				this._super();
			}
		});
	});
}(jQuery));
