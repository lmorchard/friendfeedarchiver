<?php
    $time_fields = array( 'yy'=>'Y', 'mm'=>'M', 'dd'=>'j', 'da' => 'D', 'h'=>'g', 'm'=>'i', 'a'=>'A' );
    $updated = strtotime($entry['updated']);
?>

<div class="service">
    <a class="service_icon" href="<?php echo $entry['service']['profileUrl'] ?>" title="<?php out::H($entry['service']['name']) ?>" >
        <img src="<?php out::H($entry['service']['iconUrl']) ?>" alt="<?php out::H($entry['service']['name']) ?>" />
    </a>
    <span class="service_message">
    <?php
        $s_url  = $entry['service']['profileUrl'];
        $s_id   = $entry['service']['id'];
        $s_name = $entry['service']['name'];
        $s_link = '<a href="'.out::H($s_url, FALSE).'" title="'.$s_id.'">'.out::H($s_name, FALSE).'</a>';
        $s_url_link = '<a href="'.out::H($s_url, FALSE).'" title="'.$s_id.'">'.out::H($s_url, FALSE).'</a>';
    ?>
    <?php switch($s_id) { 
        case 'delicious':?>
            bookmarked a page on <?php echo $s_link ?>
        <?php break; case 'flickr': ?>
            shared or favorited a photo on <?php echo $s_link ?>
        <?php break; case 'twitter': case 'identica': ?>
            posted a message on <?php echo $s_link ?>
        <?php break; case 'blog': ?>
            posted an update on <?php echo $s_url_link ?>
        <?php break; case 'googlereader': ?>
            shared an item on <?php echo $s_link ?>
        <?php break; case 'amazon': ?>
            added a wishlist item at <?php echo $s_link ?>
        <?php break; case 'digg': ?>
            dugg a story on <?php echo $s_link ?>
        <?php break; case 'upcoming': ?>
            added an event on <?php echo $s_link ?>
        <?php break; default: ?>
            shared an item with <?php echo $s_link ?>
        <?php break ?>
    <?php } ?>
    &#8212; <a class="comment_action" href="http://friendfeed.com/e/<?php out::H($entry['id']) ?>?login=1&comment=<?php out::H($entry['id']) ?>" title="click here to comment on the original entry at FriendFeed">comment on this</a>
    </span>
</div>

<h3 class="entry-title"><a href="<?php out::H($entry['link']) ?>" rel="bookmark"><?php out::H($entry['title']) ?></a></h3>

<abbr class="updated" title="<?php out::H($entry['updated']) ?>"><a href="http://friendfeed.com/e/<?php out::H($entry['id']) ?>" title="View FriendFeed entry">
    <?php foreach( $time_fields as $field => $fmt ): ?>
        <span class="field <?php echo $field ?>"><?php echo date($fmt, $updated) ?></span>
    <?php endforeach ?>
</a></abbr>

<div class="entry-content">

    <?php if (!empty( $entry['media'] )): ?>
        <ul class="media_items">
            <?php foreach ($entry['media'] as $media): ?>
                <li class="media">
                    <?php if (!empty( $media['thumbnails'] )): ?>
                        <?php $thumb = $media['thumbnails'][0]; ?>
                        <a href="<?php out::H($entry['link']) ?>" title="<?php out::H($media['title']) ?>">
                            <img src="<?php out::H($thumb['url']) ?>" alt="<?php out::H($media['title']) ?>"  
                                width="<?php out::H($thumb['width']) ?>" height="<?php out::H($thumb['height']) ?>" />
                        </a>
                    <?php endif ?>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
    
    <?php if (!empty( $entry['likes'] )): ?>
        <ul class="likes">
            <?php foreach ($entry['likes'] as $like): ?>
                <li class="like">
                    <a class="author" href="<?php out::H($like['user']['profileUrl']) ?>" 
                        title="<?php out::H($like['user']['nickname']) ?>"><?php out::H($like['user']['name']) ?></a>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
    
    <?php if (!empty( $entry['comments'] )): ?>
        <ul class="comments">
            <?php foreach ($entry['comments'] as $comment): ?>
                <li class="comment" id="<?php out::H($comment['id']) ?>">
                    <a class="author" href="<?php out::H($comment['user']['profileUrl']) ?>" 
                        title="<?php out::H($comment['user']['nickname']) ?>"><?php out::H($comment['user']['name']) ?></a>
                    <div class="body"><?php out::H($comment['body']) ?></div>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>

</div>
