<?php
    $nickname_u = out::U($nickname, FALSE);
?>

<?php slot::start('head') ?>
    <link rel="shortcut icon" href="http://friendfeed.s3.amazonaws.com/pictures-<?php out::H( str_replace('-', '', $profile['id']) ) ?>-small.jpg" />
    <script type="text/javascript">
        FriendFeedArchiver.main.setNickname(<?php echo json_encode($nickname) ?>); 
    </script>
<?php slot::end() ?>

<div class="profile">

    <div class="avatar">
        <a href="<?php echo url::base() . $nickname_u ?>" title="<?php out::H($profile['nickname']) ?>">
            <img src="http://friendfeed.s3.amazonaws.com/pictures-<?php out::H( str_replace('-', '', $profile['id']) ) ?>-large.jpg" 
                width="75" height="75" alt="<?php out::H($profile['nickname']) ?>" />
        </a>
    </div>
    
    <h2 class="name"><a href="<?php echo url::base() . $nickname_u ?>"><?php out::H($profile['name']) ?></a></h2>

    <ul class="services">
        <?php foreach ($profile['services'] as $service): ?>
            <li class="service">
                <a href="<?php out::H(isset($service['profileUrl']) ? $service['profileUrl'] : $service['url']) ?>" title="<?php out::H($service['name']) ?>"><img src="<?php out::H($service['iconUrl']) ?>" /></a>
            </li>
        <?php endforeach ?>
        
    </ul>
    
</div>

<ul class="date_nav date_nav_top">

    <!-- <li class="current"><a href="<?php echo url::base() . $nickname_u . "/$current_date" ?>"><?php echo $current_date ?></a></li> -->
    <li class="current"><a href="<?php echo url::base() . $nickname_u . '/' . $current_date ?>">
        <?php echo date( 'l, F d, Y' , strtotime($current_date) ) ?>
    </a></li>


    <?php if ($prev_date): ?>
        <li class="prev"><a href="<?php echo url::base() . $nickname_u . "/$prev_date" ?>"><?php echo $prev_date ?></a></li>
    <?php endif ?>
    <?php if ($next_date): ?>
        <li class="next"><a href="<?php echo url::base() . $nickname_u . "/$next_date" ?>"><?php echo $next_date ?></a></li>
    <?php endif ?>
</ul>

<?php 
    View::factory('common/entries')->set(array( 'entries' => $entries ))->render(TRUE) 
?>

<ul class="date_nav date_nav_bottom">
    <?php if ($prev_date): ?>
        <li class="prev"><a href="<?php echo url::base() . $nickname_u . "/$prev_date" ?>"><?php echo $prev_date ?></a></li>
    <?php endif ?>
    <?php if ($next_date): ?>
        <li class="next"><a href="<?php echo url::base() . $nickname_u . "/$next_date" ?>"><?php echo $next_date ?></a></li>
    <?php endif ?>
</ul>

<?php if (FALSE): ?>
<ul class="subscriptions">
    <?php foreach ($profile['subscriptions'] as $subscription): ?>
        <li class="subscription">
            <a href="<?php out::H($subscription['profileUrl']) ?>" title="<?php out::H($subscription['nickname']) ?>">
                <img src="http://friendfeed.s3.amazonaws.com/pictures-<?php out::H( str_replace('-', '', $subscription['id']) ) ?>-small.jpg" 
                    width="25" height="25" />
            </a>
        </li>
    <?php endforeach ?>
    <li><br style="clear:both" /></li>
</ul>
<?php endif ?>

<br style="clear: both" />
