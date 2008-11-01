<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<title><?php echo $error ?></title>

<style type="text/css">
/* <![CDATA[ */
* {padding:0;margin:0;border:0;}
body { background: #fff; color: #111; font-family: sans-serif; font-size: 100%; }
h1 { font-size: 1.5em; padding: 0.2em 0.2em 0.5em; }
div#wrap { width: 40em; margin: 2em auto; text-align: center; }
p.error { color: #500; }
/* ]]> */
</style>
</head>
<body>
<div id="wrap">
<h1><?php echo $error ?></h1>
<p class="error"><?php echo $message ?></p>
</div>
</body>
</html>