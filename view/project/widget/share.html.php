<?php
use Goteo\Library\Text;

$project = $this['project'];
$level = (int) $this['level'] ?: 3;

$share_title = $project->name;

$share_url = SITE_URL . '/project/' . $project->id;
$facebook_url = 'http://facebook.com/sharer.php?u=' . rawurlencode($share_url) . '&t=' . rawurlencode($share_title . ' | Goteo.org');
$twitter_url = 'http://twitter.com/home?status=' . rawurlencode($share_title . ': ' . $share_url . ' #Goteo');

$url = SITE_URL . '/widget/project/' . $project->id;
$widget_code = '<iframe frameborder="0" height="380px" src="'.$url.'" width="200px"></iframe>';


?>
<div class="widget project-share">    
    <h<?php echo $level ?> class="title"><?php echo Text::get('project-share-header'); ?></h<?php echo $level ?>>
    <ul>
        <li class="twitter"><a target="_blank" href="<?php echo htmlspecialchars($twitter_url) ?>" onclick="alert('desactivado hasta puesta en marcha'); return false;"><?php echo Text::get('regular-twitter'); ?></a></li>
        <li class="facebook"><a target="_blank" href="<?php echo htmlspecialchars($facebook_url) ?>" onclick="alert('desactivado hasta puesta en marcha'); return false;"><?php echo Text::get('regular-facebook'); ?></a></li>
        <li onclick="$(this).children('input').focus(); return false;" class="url"><span>URL: </span> <input type="text" onfocus="this.select();" readonly="readonly" size="35" value="<?php echo htmlspecialchars($share_url) ?>" /></li>
    </ul>
    <h<?php echo $level ?> class="title"><?php echo Text::get('project-spread-header'); ?></h<?php echo $level ?>>
    <ul>
        <li onclick="$(this).children('input').focus(); return false;" class="embed"><span><?php echo Text::get('project-spread-widget'); ?></span> <input type="text" onfocus="this.select();" readonly="readonly" size="84" value="<?php echo htmlspecialchars($widget_code) ?>" /></li>
    </ul>
</div>