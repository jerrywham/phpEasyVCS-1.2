<?php
# +--------------------------------------------------------------------+
# | phpEasyVCS                                                         |
# | The file-based version control system                              |
# +--------------------------------------------------------------------+
# | Copyright (c) 2011 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

  require_once('inc/basic.php');

  $msg = $err = '';
  $dir = sanitizeDir($_REQUEST['dir']);
  $name = sanitizeName($_REQUEST['name']);
  $vcs = new FileVCS(DATAPATH, null, getUserName(), isReadOnly());
  $result = $vcs->delete($dir, $name, @$_REQUEST['comment']);
  if ($result >= 0) {
    $msg = 'File/directory '.$name.' was successfully deleted. ';
  } else if ($result == VCS_NOACTION || $result == VCS_NOTFOUND) {
    $err = 'File/directory '.$name.' not found. ';
  } else {
    $err = 'Error deleting file/directory '.$name.'. ';
  }
  $url = 'browse.php?dir='.urlencode($dir);
  if ($msg) $url .= '&msg='.urlencode($msg);
  if ($err) $url .= '&error='.urlencode($err);
  header('Location: '.$url);
