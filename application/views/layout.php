<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
    <head>
        <?php 
            $theme = isset($theme) ? $theme : "default" ;
            $pagerole = isset($pagerole)  ? $pagerole  : 
                ( slot::exists('pagerole') ? slot::get('pagerole') : 'default' );
            $body_classes = array(
                'theme_'.$theme, 
                'pagerole_'.$pagerole, 
                'page_'.Router::$controller.'_'.Router::$method,
                'controller_'.Router::$controller,
                'method_'.Router::$method,
                slot::exists('sidebar') ? 'with_sidebar' : 'no_sidebar'
            );
        ?>

        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title><?php echo isset($title) ? $title.' :: ' : '' ?><?php out::H(Config::item('config.site_title')); ?></title>
        <script type="text/javascript" src="<?php echo url::base() ?>js/jquery.js"></script>
        <script type="text/javascript" src="<?php echo url::base() ?>js/main.js"></script>
        <link type="text/css" rel="stylesheet" href="<?php echo url::base() ?>css/reset-fonts-grids.css" />
        <link type="text/css" rel="stylesheet" href="<?php echo url::base() ?>css/main.css" />

        <script type="text/javascript">
            FriendFeedArchiver.main.setBaseURL(<?php out::JSON(url::base()) ?>);
        </script>

        <?php slot::output('head') ?>

    </head>
    <body id="<?php echo Router::$controller ?>_<?php echo Router::$method ?>" class="<?php echo join(' ', $body_classes) ?>">
        <div id="doc" class="<?php echo slot::exists('sidebar') ? 'yui-t1' : 'yui-t7' ?> <?php echo join(' ', $body_classes) ?>">
            <ul id="accessibility">
                <li><a href="#content" accesskey="1">Skip to content</a></li>
                <li><a href="#nav" accesskey="2">Skip to navigation</a></li>
                <li><a href="#side">Skip to sidebar</a></li>
            </ul>
            <div id="hd">
                <?php if (!isset($title)) $title = slot::get('title') ?>

                <?php if (FALSE): ?>
                <h1><!-- &#x25BC; --><a href="<?php echo url::base() ?>"><?php out::H(Config::item('config.site_title')); ?></a></h1>

                <ul id="nav" class="nav">
                    <li class="first"><a href="<?php echo url::base() ?>">home</a></li>
                </ul>
                <?php endif ?>

                <?php slot::output('header') ?>
            
            </div>
            <div id="bd">
                <div id="yui-main">
                    <div class="yui-b">
                        <div id="content"><?php echo $content ?></div>
                    </div>
                </div>
                <?php if ( slot::exists('sidebar') ): ?>
                    <div id="side" class="yui-b"><?php slot::output('sidebar') ?></div>
                <?php endif ?>
            </div>
            <div id="ft">
                <?php slot::output('footer') ?>
                <div id="download_link">
                    Download the software maintaining this archive from <a href="http://decafbad.com/hg/FriendFeedArchiver">http://decafbad.com/hg/FriendFeedArchiver</a>
                </div>
            </div>
        </div>
    </body>
</html>
