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
  $srcdir = sanitizeDir($_REQUEST['sourcedir']);
  $srcname = sanitizeName($_REQUEST['sourcename']);
  $tgtname = sanitizeName($_REQUEST['targetname']);
  $vcs = new FileVCS(DATAPATH, null, getUserName(), isReadOnly());
  $result = $vcs->move($srcdir, $srcname, $srcdir, $tgtname, false, @$_REQUEST['comment']);
  if ($result >= 0) {
    $msg = 'File/directory '.$srcname.' was successfully renamed to '.$tgtname.'. ';
  } else if ($result == VCS_NOACTION || $result == VCS_NOTFOUND) {
    $err = 'File/directory '.$srcname.' not found';
  } else {
    $err = 'Error renaming file/directory '.$srcname;
  }
  $url = 'browse.php?dir='.urlencode($srcdir);
  if (@$msg) $url .= '&msg='.urlencode($msg);
  if (@$err) $url .= '&error='.urlencode($err);
  header('Location: '.$url);
