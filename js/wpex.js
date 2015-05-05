(function($) {

	$(document).ready(function() {
		$("[data-nag-id]").click(wpexHideSaleNag);
		if($("[data-nag-id]").length === 0 && (typeof _wpex_pro_nag != "undefined") && _wpex_pro_nag) {
			$('<div class="updated nag below-h2"><p>Want more detailed statistics from your title experiments? Check out <a target="_blank" href="http://wpexperiments.com/title-experiments-pro">Title Experiments Pro.</a></p></div>').insertAfter("div.wrap>h2:first");
		}
		
		if(typeof _wpex_data == "undefined") return;
		////
		// SET UP GUI
		////

		if(_wpex_data.length > 1)  {
			// First, let's normalize the probabilities because it's been reported that they are over 100% sometimes
			var total_probability = 0;
			for(var x in _wpex_data) {
				total_probability += parseInt(_wpex_data[x].probability);
			}
			var scaleFactor = 100/total_probability;
			for(var k in _wpex_data) {
				trow = _wpex_data[k];
				trow.probability = Math.round(parseInt(trow.probability)*scaleFactor);
				wpexSetupInput(trow);
			}
		}

		$("<h4 id='wpex-title-reset'><a href='#''>[reset stats]</a></h4>").appendTo("#edit-slug-box");
		$("<h4 id='wpex-title-add'><a href='#''>+ Add New Title</a></h4>").prependTo("#edit-slug-box");

		$("[name=post_title]").change(function() {
			$("#orig-post-title").val($(this).val());
		});
		$("#wpex-title-add > a").click(wpexTitleAdd);
		$("#wpex-title-reset > a").click(wpexResetStats);
	});

	wpexSetupInput = function(trow) {
		if(trow.title == "__WPEX_MAIN__") {
			$("#title").addClass("wpex-title-exp");
			$elm = $("#title");
			$("#title-prompt-text").addClass("wpex-title-label").text("Enter title test case here");
			$elm.attr("tabindex",0);
		} else {
			$label = $("<label class='wpex-title-label' for='wpex-titles["+(trow.id ? "_"+trow.id:"")+"]'>Enter title test case here</label>");
			$("#titlewrap").append($label);

			$elm = $("<input autocomplete='off' type='text' class='wpex-title-exp' "+(trow.id ? "wpex-id='"+trow.id+"'":"")+" name='wpex-titles["+(trow.id ? "_"+trow.id:"")+"]'/>");
			$elm.val(trow.title); //set the title like this to allow for magic escaping
			$elm.attr("tabindex",$("#titlewrap > input").length);
			$("#titlewrap").append($elm);

			if(trow.title) {
				$label.hide();
			}

			$elm.focus(function(){
				$(this).prev().prev().hide();
			});

			$elm.blur(function(){
				if($(this).val() === "") {
					$(this).prev().prev().show();
				} else {
					$(this).prev().prev().hide();
				}
			});
			$label.click(function(){
				$(this).next().next().focus();
			});
		}

		$e = $("<div class='wpex-title-exp-addon' />");

		$estats = $("<div class='wpex-stats'>"+trow.clicks+'/'+trow.impressions+"</div>");
		$e.append($estats);

		$esl = $("<div class='wpex-sl'><!--"+trow.stats_str+"--></div>");
		$e.append($esl);

		if(typeof trow.probability !== "undefined") {
			$eprob = $("<div class='wpex-prob'>"+trow.probability+"%</div>");
			$e.append($eprob);
		}

		if(trow.title !== "__WPEX_MAIN__") {
			$edel = $("<div class='wpex-del dashicons dashicons-no'></div>");
			$edel.click(function(){
				$this = $(this);
				var id = $this.parent().prev().attr("wpex-id");
				if(id) {
					$("form#post").append("<input type='hidden' name='wpex-removed[]' value='"+id+"' />");
				}
				$this.parent().prev().remove();
				$this.parent().prev().remove();
				$this.parent().prev().remove();
				$this.parent().remove();

				if($(".wpex-title-exp").length == 1) {
					wpexMainTitleRemove();
				}
			});
			$e.append($edel);
		}

		$e.insertAfter($elm);

		$esl.sparkline('html', { type:'bar', barColor:'#AAA', height: "16px", tooltipFormatter: function(sl, pts, fs){
			days = ["Today","Yesterday","2 days ago","3 days ago","4 days ago","5 days ago","6 days ago"];
			day = Math.abs(fs[0].offset-6);
			if(fs[0].value == 1) {
				return "<b>"+days[day]+ ":</b> 1 view";
			}else{
				return "<b>"+days[day]+ ":</b> " +fs[0].value + " views";
			}
		}});

		$estats.qtip({
			content: function() {
				ms = $(this).text().match(/(\d+)\/(\d+)/);
				if(ms) {
					p = Math.round( (ms[1]/ms[2]) * 1000) / 10;
					if(isNaN(p)) {
						str = (isNaN(p) ? "0" : p) + "%";
					} else {
						str = p+"%";
					}
					str += "<br/>";
					str +=  ms[1] + " view" + ((ms[1] == "1") ? "" : "s") + "<br/>";
					str +=  ms[2] + " impression"+ ((ms[2] == "1") ? "" : "s");
					return str;
				}
				return false;
			},
			position: {
				my: 'top middle',
				at: 'bottom middle'
			},
			style: { classes: 'qtip-shadow qtip-light' }
		});

		$("<div class='cf'/>").insertAfter($e);

		$e = $("<div class='wpex-title-exp-pre "+(trow.enabled=='1'?"'":"disabled")+"' title='Test qtip'><input type='checkbox' name='wpex-enabled["+(trow.id ? "_"+trow.id:"")+"]' "+(trow.enabled=='1'?"checked='checked'":"")+"/></div>");
		$e.insertBefore($elm);
		$e.click(function(){
			if($(this).hasClass("disabled")) {
				$(this).removeClass("disabled");
				$(this).children("input").get(0).checked = true;
			} else {
				$(this).addClass("disabled");
				$(this).children("input").get(0).checked = false;
			}
			var qapi = $(this).data('qtip');
			var newtip = wpexStatusQtipContent(this);
			qapi.options.content.html = newtip; // update content stored in options
			qapi.elements.content.html(newtip); // update visible tooltip content
			qapi.render(); // redraw to adjust tooltip borders
		});

		$e.qtip({
			content: function() {
				return wpexStatusQtipContent(this);
			},
			position: {
				my: 'top middle',
				at: 'bottom middle'
			},
			style: { classes: 'qtip-shadow qtip-light'}
		});
	};

	wpexStatusQtipContent = function(elm) {
		if($(elm).hasClass("disabled")) {
			return "Test case is <b>disabled</b>.<br/><em>Click to enable.</em>";
		} else {
			return "Test case is <b>enabled</b>.<br/><em>Click to disable.</em>";
		}
	};

	wpexResetStats = function(ev){
		var data = {
			'action': 'wpex_stat_reset',
			'id': $("#post_ID").val()
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		$.post(ajaxurl, data, function(response) {
			window.location.reload();
		});
		return false;
	};

	wpexHideSaleNag = function(ev){
		var data = {
			'action': 'wpex_hide_nag',
			'id': $(ev.target).data("nag-id")
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		$.post(ajaxurl, data, function(response) {
			$(ev.target).closest("div.update-nag").remove();
		});
		return false;
	};


	wpexTitleAdd = function(ev){
		// We are adding one for the first time for this post so setup the orginal title
		if(!$("#title").hasClass("wpex-title-exp")) {
			wpexSetupInput({id:null,stats_str:"0,0,0,0,0,0,0",title:"__WPEX_MAIN__",clicks:0,impressions:0,enabled:1});
		}
		wpexSetupInput({id:null,stats_str:"0,0,0,0,0,0,0",title:"",clicks:0,impressions:0,enabled:1});
		return false;
	};

	wpexMainTitleRemove = function() {
		$(".wpex-title-exp-pre").remove();
		$(".wpex-title-exp-addon").remove();
		$(".wpex-title-exp").removeClass("wpex-title-exp");
		$("#title-prompt-text").removeClass("wpex-title-label").text("Enter title here");
	};

})(jQuery);
