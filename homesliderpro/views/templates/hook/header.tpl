<style type="text/css" class="slidersEverywhereStyle">


{foreach from=$configuration key=hook item=conf}
.SEslider.{$hook} {ldelim}
	padding:{$conf.vspace}px {$conf.hspace}px {$conf.vspace}px {$conf.hspace}px;
{rdelim}
{/foreach}

</style>