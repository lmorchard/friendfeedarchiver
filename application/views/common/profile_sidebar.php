<?php
    $nickname_u = out::U($profile['nickname'], FALSE);
?>
<?php slot::start('sidebar') ?>
<div class="profile">
    <div class="avatar">
        <a href="<?php echo url::base() . $nickname_u ?>" title="<?php out::H($profile['nickname']) ?>">
            <img src="http://friendfeed.s3.amazonaws.com/pictures-<?php out::H( str_replace('-', '', $profile['id']) ) ?>-large.jpg" 
                width="75" height="75" alt="<?php out::H($profile['nickname']) ?>" />
        </a>
    </div>
    <h2 class="name"><a href="<?php echo url::base() . $nickname_u ?>"><?php out::H($profile['name']) ?></a></h2>
</div>
<?php slot::end() ?>
