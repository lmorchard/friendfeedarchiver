<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<title><?php echo $error ?></title>

<style type="text/css">
/* <![CDATA[ */
* {padding:0;margin:0;border:0;}
body {background:#eee;font-family:sans-serif;font-size:85%;}
h1,h2,h3,h4 {margin-bottom:0.5em;padding:0.2em 0;border-bottom:solid 1px #ccc;color:#911;}
h1 {font-size:2em;}
h2 {font-size:1.5em;}
p,pre {margin-bottom:0.5em;}
strong {color:#700;}
#wrap {width:600px;margin:2em auto;padding:0.5em 1em;background:#fff;border:solid 1px #ddd;border-bottom:solid 2px #aaa;}
#stats {margin:0;padding-top: 0.5em;border-top:solid 1px #ccc;font-size:0.8em;text-align:center;color:#555;}
.message {margin:1em;padding:0.5em;background:#dfdfdf;border:solid 1px #999;}
.detail {text-align:center;}
.backtrace {margin:0 2em 1em;}
.backtrace pre {background:#eee;}
/* ]]> */
</style>
<!--
 This is a little <script> does two things:
   1. Prevents a strange bug that can happen in IE when using the <style> tag
   2. Accounts for PHP's relative anchors in errors
-->
<script type="text/javascript">document.write('<base href="http://php.net/" />')</script>
</head>
<body>
<div id="wrap">
<h1><?php echo $error ?></h1>
<p><?php echo $description ?></p>
<p class="message"><?php echo $message ?></p>
<?php if ($line != FALSE AND $file != FALSE): ?>
<p class="detail"><?php echo Kohana::lang('core.error_message', $line, $file) ?></p>
<?php endif; ?>
<?php if (isset($trace)): ?>
<h2><?php echo Kohana::lang('core.stack_trace') ?></h2>
<?php echo $trace ?>
<?php endif; ?>
<p id="stats"><?php echo Kohana::lang('core.stats_footer') ?></p>
</div>
</body>
</html>