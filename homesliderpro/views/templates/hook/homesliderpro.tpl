<!-- Module SlidersEverywhere -->

{if isset($homeslider_slides) && $homeslider_slides|@count > 0}
	<div class="SEslider notLoaded seslider_{$hookid} {$slideName} mode_{$configuration.mode} {if $configuration.autoControls}withControls{/if} sliderFigo {if $configuration.center}center{/if} {if $configuration.side}side{/if}" style="max-width:{$configuration.width}px;">
		<ul id="SEslider_{$hookid}" style="margin:0;">
		{assign "number" "0"} {* short-hand *}
		{foreach from=$homeslider_slides item=slide}
			{$number = $number+1}
			{if $slide.active && $slide.image != ''}
				<li {if $number == 1}class="primo active"{/if} title="{$slide.description|escape:'htmlall':'UTF-8'}" style="padding:0;">
					{if $slide.url != ''}
						<a class="SElink" href="{$slide.url|escape:'htmlall':'UTF-8'}" {if $slide.new_window == 1}target="_blank"{/if}>
					{/if}
						<img class="SEimage" src="{$smarty.const._MODULE_DIR_}/homesliderpro/images/{$slide.image|escape:'htmlall':'UTF-8'}" alt="{$slide.legend|escape:'htmlall':'UTF-8'}" height="{$configuration.height|intval}" width="{$configuration.width|intval}" />
					{if $slide.url != ''}
						</a>
					{/if}
					{if $slide.description != ''}
						<span class="slide_description">{$slide.description|escape:'htmlall':'UTF-8'}</span>
					{/if}
					{if $configuration.show_title == 1 && $slide.title != ''}
						<h1 class="slidetitle{if $configuration.title_pos == 1} right{else} left{/if}">{$slide.title|escape:'htmlall':'UTF-8'}</h1>
					{/if}
					{if $slide.has_area && !empty($slide.areas)}
						{foreach from=$slide.areas item=area}
							{if $area->url && ($area->button == '' || (!isset($area->style) || $area->style == 'simple'))}<a href="{$area->url}"{else}<span{/if} class="areaslide{if isset($area->style) } {$area->style}{/if}{if isset($area->color)} {$area->color}{/if}" style="left:{$area->left}%;top:{$area->top}%;width:{$area->selWidth}%;height:{$area->selHeight}%;">
								{if $area->title != ''}<span class="areatitle">{$area->title}</span>{/if}
								{if $area->description != ''}<span class="areadesc">{$area->description}</span>{/if}
								{if $area->button != '' && (isset($area->style) && $area->style != 'simple')}<span class="areabuttcont">{if $area->url}<a href="{$area->url}">{/if}<span class="areabutt">{$area->button}</span>{if $area->url}</a>{/if}</span>{/if}
							{if $area->url && ($area->button == '' || (!isset($area->style) || $area->style == 'simple'))}</a>{else}</span>{/if}
						{/foreach}
					{/if}
				</li>
			{/if}
		{/foreach}
		</ul>
	</div>
{/if}

{if $homeslider_slides|@count > 1}
	<script type="text/javascript">

	
	function initSlide_{$hookid}() {
		{if $homeslider_slides|@count > 1}
			var auto = {$configuration.auto},
				controls = {$configuration.controls},
				pager = {$configuration.pager};
			{if isset($configuration.autoControls) && $configuration.autoControls}
				var autoControls = true,
					autoControlsCombine = true;
			{else}
				var autoControls = false,
					autoControlsCombine = false;
			{/if}
		{else}
			var auto = false,
				controls = false,
				pager = false;
		{/if}
		
		var confWidth = {$configuration.width};
		var confHeight = {$configuration.height};
		var imgnum = {$homeslider_slides|@count};
	
	
		var $slider = $('#SEslider_{$hookid}'); //cache for performance
		$slider.seSlider({
			arrowClass: 'myarrow',
			nextClass: 'mynext',
			prevClass: 'myprev',
			playClass: 'fa fa-play',
			stopClass: 'fa fa-pause',
			pagerClass: 'mypager',
			infiniteLoop: {$configuration.loop},
			hideControlOnEnd: true,
			controls: controls,
			pager: pager,
			autoHover: true,
			preloadImages: 'visible',
			auto: auto,
			speed: {$configuration.speed},
			pause: {$configuration.pause},
			autoControls: autoControls,
			autoControlsCombine : autoControlsCombine,
			slideMargin: {$configuration.margin},
			mode: '{if $configuration.mode != "carousel"}{$configuration.mode}{else}horizontal{/if}',
			autoDirection: '{$configuration.direction}',
			onSlideBefore: function($slideElement, oldIndex, newIndex){
				$slider.find('li').removeClass('old active-slide next prev');
				if (oldIndex < newIndex || (oldIndex == $slider.find('li').length-1 && newIndex == 0) ) {
					$slider.find('li').removeClass('left');
					$slider.find('li').eq(oldIndex).addClass('old next');	
					$slideElement.addClass('active-slide next');
				} else {
					$slider.find('li').addClass('left');
					$slider.find('li').eq(oldIndex).addClass('old prev');		
					$slideElement.addClass('active-slide prev');
				}
			},
			onSlideNext: function($slideElement, oldIndex, newIndex){
			},
			onSlidePrev: function($slideElement, oldIndex, newIndex){		
			},
			onSlideAfter: function($slideElement, oldIndex, newIndex){
			},
			onSliderLoad: function (currentIndex) {
				$('.seslider_{$hookid}').removeClass('notLoaded');
				$slider.find('li').eq(currentIndex).addClass('active-slide');
				if ('{$configuration.direction}' != 'next')
					$slider.find('li').addClass('left');
				var perspective = $slider.width()*3+'px';
				$slider.css({
					'perspective':perspective ,
					'-webkit-perspective':perspective 
				})
			}
			{if $configuration.mode == 'carousel'},
				minSlides: {$configuration.min_slides},
				maxSlides: {$configuration.max_slides},
				slideWidth:{$configuration.width}/{$configuration.max_slides}
			{/if}
		});
		
		if ({$configuration.restartAuto}){
			$slider.mouseleave(function(){
				$slider.startAuto();
			})
		}
		
		
	}
	
	initSlide_{$hookid}();

	</script>
{/if}
<!-- /Module SlidersEverywhere -->