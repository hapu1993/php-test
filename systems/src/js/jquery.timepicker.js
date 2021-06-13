(function( $ ){

	$.fn.timepicker = function() {

		var element = '<div class="ui-timepicker ui-widget-content">'+
			'<div class="ui-widget-header">'+
				'<select class="ui-timepicker-hour"><option value="00">00</option><option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option><option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option><option value="09">09</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option></select>'+
				'<select class="ui-timepicker-minute"><option value="00">00</option><option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option><option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option><option value="09">09</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option><option value="24">24</option><option value="25">25</option><option value="26">26</option><option value="27">27</option><option value="28">28</option><option value="29">29</option><option value="30">30</option><option value="31">31</option><option value="32">32</option><option value="33">33</option><option value="34">34</option><option value="35">35</option><option value="36">36</option><option value="37">37</option><option value="38">38</option><option value="39">39</option><option value="40">40</option><option value="41">41</option><option value="42">42</option><option value="43">43</option><option value="44">44</option><option value="45">45</option><option value="46">46</option><option value="47">47</option><option value="48">48</option><option value="49">49</option><option value="50">50</option><option value="51">51</option><option value="52">52</option><option value="53">53</option><option value="54">54</option><option value="55">55</option><option value="56">56</option><option value="57">57</option><option value="58">58</option><option value="59">59</option></select>'+
				'<a class="ui-timepicker-close" title="Close">Close</a>'+
			'</div>'+
			'<div class="ui-timepicker-main">'+
				'<ul class="first">'+
					'<li>00:00</li><li>01:00</li><li>02:00</li><li>03:00</li><li>04:00</li><li>05:00</li><li>06:00</li><li>07:00</li>'+
				'</ul>'+
				'<ul class="middle">'+
					'<li>08:00</li><li>09:00</li><li>10:00</li><li>11:00</li><li>12:00</li><li>13:00</li><li>14:00</li><li>15:00</li>'+
				'</ul>'+
				'<ul class="last">'+
					'<li>16:00</li><li>17:00</li><li>18:00</li><li>19:00</li><li>20:00</li><li>21:00</li><li>22:00</li><li>23:00</li>'+
				'</ul>'+
			'</div>'+
		'</div>';

		$(this).click(function(e){
			e.stopPropagation();
			
			el = $(this).parents("div.inputwrap");
			
			// remove any previous timepicker
			$('.active-timepicker').removeClass('active-timepicker');
			$('.ui-timepicker').remove();

			el.addClass('active-timepicker');
			$('body').append(element);
			$('.ui-timepicker').css('top', ( el.offset().top+10)+'px').css('left', el.offset().left+'px');

			e.stopPropagation();

			// set the dropdowns value on timepicker open
			$('select.ui-timepicker-hour option[value="'+$('.active-timepicker .h_hour').text()+'"]').attr('selected', 'selected'); 
			$('select.ui-timepicker-minute option[value="'+$('.active-timepicker .h_minute').text()+'"]').attr('selected', 'selected');

			// change minutes in the list
			if ( $('.active-timepicker .h_minute').text() != '') {
				$('div.ui-timepicker-main li').each(function(){
					var current = $(this).html().split(/\:+/);
					$(this).html(current[0]+':'+$('.active-timepicker').find('.h_minute').text());
				});
			}

			// click outside removes the timepicker
			$("body *").on("click", function(e){

				// close all dropdown menus (if clicked outside)
				if ( !$(this).hasClass("ui-timepicker") && $(this).parents("div.ui-timepicker").length == 0 && !$(this).data("time-trigger")) {
					$('.active-timepicker').removeClass('active-timepicker');
					$('.ui-timepicker').remove();
				}

			});

			// close on a X click
			$('.ui-timepicker-close').click(function(){
				$('.ui-timepicker').remove(); 
				$('.active-timepicker').removeClass('active-timepicker');
			});

			// set hour and minute by LI click
			$('div.ui-timepicker-main li').click(function(){
				var hour_minute = $(this).html().split(/\:+/);
				$('.active-timepicker .h_hour').text( hour_minute[0] );
				$('.active-timepicker .h_minute').text( hour_minute[1] );
				$('.active-timepicker select.ui-timepicker-hour option[value="'+hour_minute[0]+'"]').attr('selected','selected'); 
				$('.active-timepicker select.ui-timepicker-minute option[value="'+hour_minute[1]+'"]').attr('selected','selected');
				updateHidden(el);

				// close timepicker
				$('.ui-timepicker').remove();
				$('.active-timepicker').removeClass('active-timepicker'); 

			});

			// set hour and minute by dropdowns
			$('select.ui-timepicker-hour').change(function(e){
				e.stopPropagation();

				$('.active-timepicker .h_hour').text( $(this).val() );
				updateHidden(el);
			});

			$('select.ui-timepicker-minute').change(function(e){
				e.stopPropagation();
				$('.active-timepicker .h_minute').text( $(this).val() );

				// change minutes in the list
				$('div.ui-timepicker-main li').each(function(){
					var current = $(this).html().split(/\:+/);
					$(this).html(current[0]+':'+$('select.ui-timepicker-minute').val());
				});

				updateHidden(el);
			});

			return false;
		});

	};

	function updateHidden(el){
		if ( el.find('.h_hour').text() == '') {
			h_value = '00'; 
		} else {
			var zero_val = String( el.find('.h_hour').text() );
			if (zero_val.length < 2) h_value = "0" + zero_val;
			else h_value = zero_val;
		}			

		if ( el.find('.h_minute').text() == '') {
			m_value = '00';
		} else { 
			var zero_val = String( el.find('.h_minute').text() );
			if (zero_val.length < 2) m_value = "0" + zero_val;
			else m_value = zero_val;
		}

		el.find('.v_date_time').val( h_value + ':' + m_value + ':00');

		// add hasdate class
		el.addClass("hasdate");

		// if there was a self submit attached to the datepicker element
		if ( el.find("input.self_submit").length ) {

			// close timepicker
			$('.ui-timepicker').remove();
			$('.active-timepicker').removeClass('active-timepicker'); 

			self_submit( el.find("input.self_submit"), el.parents("form").attr("action"));
		}
		
		if ( el.find("input.required").length ) {
			el.find("label.error").remove();
		}

		// set the form's 'has changed' variable
		if ( el.parents("form").find("input[name=haschng]").val() == "") {
			el.parents("form").find("input[name=haschng]").val("1");
			formHasChanged();
			saveNotification();
		}

	};

})( jQuery );