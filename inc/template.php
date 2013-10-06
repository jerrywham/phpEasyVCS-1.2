<?php
# +--------------------------------------------------------------------+
# | phpEasyVCS                                                         |
# | The file-based version control system                              |
# +--------------------------------------------------------------------+
# | Copyright (c) 2011 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+
function baseURL() {
    $dir = dirname($_SERVER['SCRIPT_NAME']);
    $protocol = (!empty($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] == 'on')?  'https://' : "http://";
    $servername = $_SERVER['HTTP_HOST'];
    $serverport = (preg_match('/:[0-9]+/', $servername) OR $_SERVER['SERVER_PORT'])=='80' ? '' : ':'.$_SERVER['SERVER_PORT'];
    $racine = rtrim($protocol.$servername.$serverport.$dir, '/').'/';
    return $racine;
}
function template_header() {
  $base = baseURL();
?>
<!DOCTYPE html>
<html>
<head>
  <title>easy VCS</title>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
  <link rel="stylesheet" type="text/css" href="<?php echo $base; ?>css/default.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="<?php echo $base; ?>css/prettify.css" media="screen" />
  <script type="text/javascript" src="<?php echo $base; ?>js/jquery-1.4.3.min.js"></script>
  <script type="text/javascript" src="<?php echo $base; ?>js/jquery.dialog.js"></script>
  <script type="text/javascript" src="<?php echo $base; ?>js/prettify.js"></script>
</head>
<body onload="prettyPrint()">
  <div id="container">
    <div id="header">
      <ul class="menu">
        <li><a href="browse.php">Browse</a></li>
        <li><a href="tags.php">Tags</a></li>
        <?php if (isUserLevel(USERLEVEL_ADMIN)) { ?>
          <li><a href="settings.php">Settings</a></li>
          <li><a href="users.php">Users</a></li>
        <?php } ?>
        <li><a href="help.php">Help</a></li>
      </ul>
      <h1>phpEasyVCS</h1>
    </div>
    <div id="content" style="clear:both;">
      <pre id="debug" style="display:none;"></pre>
      <script type="text/javascript">
        function debug(s) { $('#debug').show().text($('#debug').text()+"\r\n\r\n"+s); }
      </script>
<?php 
  if (@$_GET['msg']) {
?>    
      <div class="msg" style="clear:both;"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php
  }
  if (@$_GET['error']) {
?>
      <div class="error" style="clear:both;"><?php echo htmlspecialchars($_GET['error']); ?></div>
<?php    
  }
?>
<?php
}

function template_footer() {
?>
      <div style="clear:both"></div>
    </div>
    <div id="footer">
      <div class="copyright">(c) Martin Vlcek</div>
      <div class="about"><a href="about.php">About</a></div>
      <div style="clear:both"></div>
    </div>
  </div>
</body>
</html>
<?php
}