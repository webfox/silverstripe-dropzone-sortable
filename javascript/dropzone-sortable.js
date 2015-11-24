;(function ($) {
    $(function () {
        $.entwine('ss', function ($) {
            $(".fileattachment.is-sortable").entwine({
                onmatch  : function () {
                    // enable sorting functionality
                    var self      = $(this);
                    var container = $('ul', this);
                    var settings  = $('.dropzone-holder', this).data('config');

                    // Get the action URL template (only thing that will change is the file ID).
                    var actionURL = settings['sortable-action'];

                    container.sortable({
                        items      : '.dropzone-image',
                        handle     : '.file-icon',
                        cursor     : 'move',
                        opacity    : 0.5,
                        containment: container,
                        distance   : 20,
                        tolerance  : 'pointer',
                        start      : function (event, ui) {
                            // remove overflow on container
                            ui.item.data("oldPosition", ui.item.index());
                            //self.css("overflow", "hidden");
                        },
                        stop       : function (event, ui) {
                            // restore overflow
                            //self.css("overflow", "auto");
                        },
                        update     : function (event, ui) {
                            // Get the current file ID
                            var fileID = ui.item.data("id");

                            // actionURL won't be available in unsaved data-records.
                            // But since unsaved records don't need ajax sorting callbacks, it's fine to do
                            // nothing in case of a missing actionURL.
                            if (actionURL) {
                                $.get(actionURL + '/' + fileID, {
                                    newPosition: (ui.item.index()),
                                    oldPosition: ui.item.data("oldPosition")
                                }, function (data, status) {
                                    window.console.log(data);
                                });
                            }
                        }
                    });
                    container.find('.find-icon').first().click();
                    console.log('clicking on find icon');
                    this._super();
                },
                onunmatch: function () {
                    // clean up
                    try {
                        var container = $('ul', this);
                        container.sortable("destroy");
                    } catch (e) {
                    }
                    ;
                    this._super();
                }
            });
        });
    });
}(jQuery));
