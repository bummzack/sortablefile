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
							// get the field ID from the actual upload field
							var fieldID = $(this).parent().find("input.sortableupload").attr("id");
							// Use the current file ID to determine a URL to the correct sort action handler.
							var fileID = ui.item.data("fileid");
							var actionURL = $("#"+ fieldID +"_File_"+ fileID).data("action");

							// actionURL won't be available in unsaved data-records.
							// But since unsaved records don't need ajax sorting callbacks, it's fine to do
							// nothing in case of a missing actionURL.
							if(actionURL){
								$.get(actionURL, {
									newPosition: (ui.item.index()),
									oldPosition: ui.item.data("oldPosition")
								}, function(data, status){
									//window.console.log(data);
								});
							}
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
