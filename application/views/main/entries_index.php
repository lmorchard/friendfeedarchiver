<?php slot::start('head') ?>
    <link rel="shortcut icon" href="http://friendfeed.s3.amazonaws.com/pictures-<?php out::H( str_replace('-', '', $profile['id']) ) ?>-small.jpg" />
<?php slot::end() ?>

<ul class="dates">
    <?php foreach ($dates as $date): ?>
        <?php
            $date_url =  url::base() . "$nickname/$date";
        ?>
        <li class="date">
            <a href="<?php echo $date_url ?>"><?php echo $date ?></a>
        </li>
    <?php endforeach ?>
</ul>
