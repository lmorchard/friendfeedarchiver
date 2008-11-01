<ul class="profiles">
    <?php foreach ($profiles as $profile): ?>
        <li class="profile">
            <div class="name">
                <a href="<?php echo url::base(); out::U($profile['nickname']) ?>"><?php out::H($profile['name']) ?></a>
            </div>
            <div class="avatar">
                <a href="<?php echo url::base(); out::U($profile['nickname']) ?>" title="<?php out::H($profile['nickname']) ?>">
                    <img src="http://friendfeed.s3.amazonaws.com/pictures-<?php out::H( str_replace('-', '', $profile['id']) ) ?>-large.jpg" 
                        width="75" height="75" alt="<?php out::H($profile['nickname']) ?>" />
                </a>
            </div>
        </li>
    <?php endforeach ?>
</ul>

<br style="clear: both" />
