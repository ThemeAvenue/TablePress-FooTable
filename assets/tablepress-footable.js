/*global jQuery:false */
(function ($) {
	'use strict';

	$(function () {

		// check if jQuery FooTable is loaded
		if (jQuery().footable) {

			var bpPhone = 480,
				bpTable = 992;

			if ($.mobile !== undefined) {
				// initialiaze normally
				$('.tablepress').footable({
					breakpoints: {
						phone: bpPhone,
						tablet: bpTable
					}
				});
			} else {
				// if you use jQuery Mobile, this is how to initialize FooTable the right way
				$(document).on('pageshow', function () {
					$('.tablepress').footable({
						breakpoints: {
							phone: bpPhone,
							tablet: bpTable
						}
					});
				});
			}

		}

	});

}(jQuery));