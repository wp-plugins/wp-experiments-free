(function($) {

	$(document).ready(function() {
		if(typeof _wpex_data == "undefined") return;

		////
		// SET UP GUI
		////
		if(_wpex_data.length > 1)  {
			for(var k in _wpex_data) {
				trow = _wpex_data[k];
				wpexSetupInput(trow);
			}
		}

		$("<h4 id='wpex-title-add'><a href='#''>+ Add New Title</a></h4>").prependTo("#edit-slug-box");

		$("[name=post_title]").change(function() {
			$("#orig-post-title").val($(this).val());
		});
		$("#wpex-title-add > a").click(wpexTitleAdd);
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
		$estatus = $("<div class='wpex-status wpex-"+trow['winner']+"'></div>");
		$e.append($estatus);
		$estatus.qtip({
			content: function() {
				if($(this).hasClass("wpex-unknown")) {
					return "More data is neccessary to determine a winner.";
				} else if($(this).hasClass("wpex-winner")) {
					return "The test case is a winner.";
				} else {
					return "The test case is a loser.";
				}
			},
			position: {
				my: 'top middle',
				at: 'bottom middle'
			},
			style: { classes: 'qtip-shadow qtip-light' }
		});

		$estats = $("<div class='wpex-stats' confidence='"+ trow.confidence +"'>"+trow.clicks+'/'+trow.impressions+" </div>");
		$e.append($estats);

		$esl = $("<div class='wpex-sl'><!--"+trow.stats_str+"--></div>");
		$e.append($esl);

		if(trow.title !== "__WPEX_MAIN__") {
			$edel = $("<div class='wpex-del'></div>");
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
					p = Math.round( (ms[1]/ms[2]) * 10000) / 100;
					if(isNaN(p)) {
						str =  (isNaN(p) ? "0" : p) + "%  ";
					} else {
						c = parseFloat($(this).attr("confidence"));
						str = (Math.round((p-c)*100)/100) + "% - " + (Math.round((p+c)*100)/100) + "%";
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
	};

})(jQuery);
