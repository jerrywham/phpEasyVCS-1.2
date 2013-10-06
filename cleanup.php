<?php
# +--------------------------------------------------------------------+
# | phpEasyVCS                                                         |
# | The file-based version control system                              |
# +--------------------------------------------------------------------+
# | Copyright (c) 2011 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

  require_once('inc/basic.php');

  header('Content-Type: text/plain; charset=UTF-8');
  $delete = isset($_GET['delete']);
  $dir = @opendir(DATAPATH) or die("Unable to open data directory");
  if (!$delete) {
    echo "The following files will be deleted, if you call cleanup.php?delete:\r\n";
  } else {
    echo "Deleting files:\r\n";
  }
  while ($filename = readdir($dir)) {
    if (strpos($filename,'\\') !== false) {
      echo " - $filename\r\n";
      if ($delete) unlink(DATAPATH.$filename);
    }
  }
