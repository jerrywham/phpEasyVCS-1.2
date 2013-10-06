<?php
# +--------------------------------------------------------------------+
# | phpEasyVCS                                                         |
# | The file-based version control system                              |
# +--------------------------------------------------------------------+
# | Copyright (c) 2011 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

  require_once('inc/basic.php');
  require_once('inc/template.php');
  require_once('inc/filetype.class.php');
  
  $dir = sanitizeDir(@$_REQUEST['dir']);
  $vcs = new FileVCS(DATAPATH, @$_REQUEST['tag'], getUserName(), isReadOnly());
  $tag = $vcs->getTag();
  $tagname = $tag && $tag->name ? $tag->name : ($tag ? $_REQUEST['tag'] : null);

  $v = @filemtime(ROOTPATH.'applet/vcsapplet.jar');
  $uri = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http";
  $uri .= "://".$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"];
  $uri = substr($uri,0,strlen($uri)-10) . 'rest.php/current';

  template_header();
  if (is_dir(DATAPATH.$dir)) {
    $showdeleted = @$_REQUEST['all'] ? true : false;
    $listing = $vcs->getListing($dir, $showdeleted);
    $currlink = 'browse.php?'.($tagname ? 'tag='.urlencode($tagname).'&amp;' : '').'dir='.urlencode($listing->dir);
    if (!$listing->dir) {
      $uplink = 'tags.php';
    } else {
      $updir = substr($listing->dir,0,strlen($listing->dir)-1);
      $pos = strrpos($updir,'/');
      $updir = $pos !== false ? substr($dir,0,$pos) : '';
      $uplink = 'browse.php?'.($tagname ? 'tag='.urlencode($tagname).'&amp;' : '').'dir='.urlencode($updir);
    }
    $tagtext = '';
    if ($tag && $tag->date) $tagtext .= ' <span>at</span> <span class="date">'.timestamp2string($tag->date).'</span>';
    if ($tag && $vcs->getTag($tag->name)) $tagtext .= ' ('.htmlspecialchars($tag->name).')';
    $names = array();
    if (@$_REQUEST['name'] && is_array($_REQUEST['name'])) {
      foreach ($_REQUEST['name'] as $name) $names[] = sanitizeName($name);
    }
?>
    <ul class="actions">
      <li><a href="<?php echo $currlink; ?>">Refresh</a></li>
      <?php if (!@$tag && !isReadOnly()) { ?>
      <li><a href="<?php echo $currlink; ?>&amp;addfolder" class="addfolder">Create folder</a></li>
      <li><a href="<?php echo $currlink; ?>&amp;addfile" class="addfile">Upload file</a></li>
      <?php } ?>
      <?php if (false && !@$tag) { ?>
      <li><a href="sync.php?dir=<?php echo htmlspecialchars($dir); ?>" class="sync">Synchronize</a></li>
      <?php } ?>
    </ul>
    <h2>Directory /<?php echo htmlspecialchars(substr($dir,0,-1)); ?> <?php echo $tagtext; ?></h2>
    <form action="action.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir); ?>" />
      <input type="hidden" name="tag" value="<?php echo isset($_REQUEST['tag']) ? htmlspecialchars($_REQUEST['tag']) : ''; ?>" />
      <table class="list">
        <thead>
          <tr>
            <th class="check">
              <div class="pulldown"><div><img src="images/down.png" alt=""/></div>
                <ul>
                  <?php if (!isReadOnly()) { ?>
                    <?php if (!@$tag) { ?>
                    <li><input type="submit" name="start-delete" value="Delete selected files"/></li>
                    <?php } ?>
                    <li><input type="submit" name="start-copy" value="Copy selected files"/></li>
                    <?php if (!@$tag) { ?>
                    <li><input type="submit" name="start-move" value="Move selected files"/></li>
                    <?php } ?>
                  <?php } ?>
                  <li><input type="submit" name="download" value="Download selected files"/></li>
                  <?php /*
                  <li style="height:22px; display:none" id="downloadAppletItem">
  	                <div style="position:relative">
  	                  <applet id="downloadApplet" name="DownloadApplet" code="net.sf.phpeasyvcs.DownloadApplet" 
  							           archive="applet/vcsapplet.jar?v=<?php echo $v; ?>" width="150"  height="20">
  		                  <param name="root" value="<?php echo htmlspecialchars($uri) . ($dir ? '/'.substr($dir,0,-1)    : ''); ?>">
  		                </applet>
   				         </div>
                  </li>
                  */ ?>
                </ul>
              </div>  
            </th>
            <th>Name
              <?php if ($showdeleted) { ?>
                (<a href="<?php echo $currlink; ?>">Hide deleted</a>)
              <?php } else { ?>
                (<a href="<?php echo $currlink; ?>&amp;all=1">Show deleted</a>)
              <?php } ?>
            </th>
            <th class="version">Version</th>
            <th class="size">Size</th>
            <th class="date" title="<?php echo htmlspecialchars(TIMEZONE); ?>">Date</th>
            <th>User</th>
            <th>Comment</th>
            <?php if (!@$tag && !isReadOnly()) { ?><th colspan="4"></th><?php } else { ?><th colspan="2"></th><?php } ?>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="check"></td>
            <td class="name">
              <img src="images/upfolder.png" alt=""/> 
              <a href="<?php echo $uplink.($showdeleted ? '&amp;all=1' : ''); ?>">..</a>
            </td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <?php if (!@$tag && !isReadOnly()) { ?><td colspan="4"></td><?php } else { ?><td colspan="2"></td><?php } ?>
          </tr>
          <?php foreach ($listing->directories as $directory) { ?>
            <tr>
              <td class="check">
                <?php if (!$directory->deleted) { ?>
                  <input type="checkbox" name="name[]" value="<?php echo htmlspecialchars($directory->name); ?>" <?php if (in_array($directory->name, $names)) echo 'checked="checked"'; ?>/>
                <?php } ?>
              </td>
              <td class="name">
                <img src="images/folder.png" alt=""/>
                <a href="<?php echo 'browse.php?'.($tagname ? 'tag='.urlencode($tagname).'&amp;' : '').'dir='.urlencode($dir.$directory->name).($showdeleted ? '&amp;all=1' : ''); ?>" class="<?php echo ($directory->deleted ? 'deleted' : ''); ?>"><?php echo htmlspecialchars($directory->name); ?></a>
              </td>
              <td></td>
              <td></td>
              <td class="date"><?php echo timestamp2string($directory->date); ?></td>
              <td><?php echo htmlspecialchars(@$directory->user); ?></td>
              <td>
                <?php echo str_replace('\n','<br/>',htmlspecialchars($directory->comment)); ?>
                <?php display_move_copy($directory); ?>
              </td>
              <?php if (!@$tag && !$directory->deleted && !isReadOnly()) { ?>
                <td colspan="2"></td>
                <td class="link rename">
                  <a href="<?php echo 'browse.php?dir='.urlencode($dir).'&amp;name='.urlencode($directory->name).'&amp;rename'; ?>" 
                      title="Rename directory: <?php echo htmlspecialchars($directory->name); ?>">R</a>
                </td>
                <td class="link delete">
                  <a href="<?php echo 'delete.php?dir='.urlencode($dir).'&amp;name='.urlencode($directory->name); ?>" 
                      title="Delete directory: <?php echo htmlspecialchars($directory->name); ?>">X</a>
                </td>
              <?php } else if (!@$tag && !isReadOnly()) { ?>
                <td colspan="3"></td>
                <td class="link undelete">
                  <a href="<?php echo 'undelete.php?dir='.urlencode($dir).'&amp;name='.urlencode($directory->name); ?>" 
                      title="Undelete directory: <?php echo htmlspecialchars($directory->name); ?>">U</a>
                </td>
              <?php } else { ?>
                <td colspan="2"></td>
              <?php } ?>
            </tr>
          <?php } ?>
          <?php foreach ($listing->files as $file) { ?>
            <?php $quickview = preg_match('@^(text|image)/@',$file->mimetype); ?>
            <?php $icon = Filetype::getIcon($file->ext).'.png'; ?>
            <tr>
              <td class="check">
                <?php if (!$file->deleted) { ?>
                  <input type="checkbox" name="name[]" value="<?php echo htmlspecialchars($file->name); ?>" <?php if (in_array($file->name, $names)) echo 'checked="checked"'; ?>/>
                <?php } ?>
              </td>
              <td class="name <?php echo ($file->deleted ? 'deleted' : ''); ?>">
                <img src="images/<?php echo $icon; ?>" alt="" />
                <?php if (!$file->deleted) { ?>
                  <a href="<?php echo 'get.php?dir='.urlencode($dir).'&amp;name='.urlencode($file->name).'&amp;version='.$file->version; ?>"
                      title="Download: <?php echo htmlspecialchars($file->name); ?> (md5: <?php echo htmlspecialchars($file->md5); ?>)">
                    <?php echo htmlspecialchars($file->name); ?>
                  </a>
                <?php } else { ?>
                  <?php echo htmlspecialchars($file->name); ?>
                <?php } ?>
              </td>
              <td class="version <?php echo ($file->deleted ? 'deleted' : ''); ?>">
                <?php echo $file->version; ?>
              </td>
              <td class="size"><?php echo !$file->deleted ? size2string($file->size) : ''; ?></td>
              <td class="date"><?php echo timestamp2string($file->date); ?></td>
              <td class="user"><?php echo htmlspecialchars(@$file->user); ?></td>
              <td class="comment">
                <?php echo str_replace('\n','<br/>',htmlspecialchars($file->comment)); ?>
                <?php display_move_copy($file); ?>
              </td>
              <?php if ($quickview && !$file->deleted) { ?>
                <td class="link quickview">
                  <a href="<?php echo 'view.php?'.($tagname ? 'tag='.urlencode($tagname).'&amp;' : '').'dir='.urlencode($dir).'&amp;name='.urlencode($file->name).'&amp;version='.$file->version; ?>"
                      title="Quick view: <?php echo htmlspecialchars($file->name); ?>">Q</a>
                </td>
              <?php } else { ?>
                <td></td>
              <?php } ?>
              <td class="link versions">
                <a href="<?php echo 'versions.php?'.($tagname ? 'tag='.urlencode($tagname).'&amp;' : '').'dir='.urlencode($dir).'&amp;name='.urlencode($file->name); ?>"
                    title="Show history: <?php echo htmlspecialchars($file->name); ?>">H</a>
              </td>
              <?php if (!@$tag && !$file->deleted && !isReadOnly()) { ?>
                <td class="link rename">
                  <a href="<?php echo 'browse.php?dir='.urlencode($dir).'&amp;name='.urlencode($file->name).'&amp;rename'; ?>" 
                      title="Rename file: <?php echo htmlspecialchars($file->name); ?>">R</a>
                </td>
                <td class="link delete">
                  <a href="<?php echo 'delete.php?dir='.urlencode($dir).'&amp;name='.urlencode($file->name); ?>" 
                      title="Delete file: <?php echo htmlspecialchars($file->name); ?>">X</a>
                </td>
              <?php } else if (!@$tag && !isReadOnly()) { ?>
                <td></td>
                <td class="link undelete">
                  <a href="<?php echo 'undelete.php?dir='.urlencode($dir).'&amp;name='.urlencode($file->name); ?>" 
                      title="Undelete file: <?php echo htmlspecialchars($file->name); ?>">U</a>
                </td>
              <?php } ?>
            </tr>
          <?php } ?>
        </tbody>
      </table>
      <a id="dialog-background-start" class="dialog-background" href="<?php echo $currlink; ?>" 
          style="display:<?php echo isset($_REQUEST['start-delete']) || isset($_REQUEST['start-copy']) || isset($_REQUEST['start-move']) ? 'block' : 'none'; ?>"></a>
      <div class="dialog-container">
        <?php 
          $dialogclass = '';
          if (isset($_REQUEST['start-delete'])) $dialogclass = 'delete-dialog'; else
          if (isset($_REQUEST['start-copy'])) $dialogclass = 'copy-dialog'; else
          if (isset($_REQUEST['start-move'])) $dialogclass = 'move-dialog';
        ?>
        <div id="start-dialog" class="dialog <?php echo $dialogclass; ?>" style="display:<?php echo isset($_REQUEST['start-delete']) || isset($_REQUEST['start-copy']) || isset($_REQUEST['start-move']) ? 'block' : 'none';?>;">
          <a href="<?php echo $currlink; ?>"><img src="images/close.png" class="close" alt="Close dialog"/></a>
          <h2 class="delete-item"><img src="images/delete.png" alt=""/> Delete Files</h2>
          <h2 class="copy-item"><img src="images/copy.png" alt=""/> Copy Files</h2>
          <h2 class="move-item"><img src="images/move.png" alt=""/> Move Files</h2>
          <label for="comment">Comment:</label>
          <textarea name="comment" style="width:90%; height:4em;"></textarea>
          <label for="targetdir" class="move-item copy-item">To directory:</label>
          <div id="tree" class="tree-container move-item copy-item">
            <?php if (isset($_REQUEST['start-copy']) || isset($_REQUEST['start-move'])) display_tree('targetdir'); ?>
          </div>
          <div id="overwriteItem" class="move-item copy-item">
            <input type="checkbox" name="overwrite" value="1" /> Overwrite
          </div>
          <input type="submit" class="submit delete-item" name="delete" value="Delete selected files"/>
          <input type="submit" class="submit copy-item" name="copy" value="Copy selected files"/>
          <input type="submit" class="submit move-item" name="move" value="Move selected files"/>
        </div>
      </div>
    </form>
    <?php if (!@$tag) { ?>
    <a id="dialog-background" class="dialog-background" href="<?php echo $currlink; ?>" 
        style="display:<?php echo isset($_REQUEST['rename']) || isset($_REQUEST['addfolder']) || isset($_REQUEST['addfile']) ? 'block' : 'none'; ?>"></a>
    <div class="dialog-container">
      <div id="progress-dialog" class="dialog" style="display:none">
        <h2>Downloading...</h2>
        <div id="progress" class="progress">
          <div class="progress-bar"></div>
          <div class="progress-text"></div>
        </div>
      </div>
    </div>
    <?php if (isset($_REQUEST['name'])) :
    foreach ($_REQUEST['name'] as $key => $name) :?>
    
    <div class="dialog-container">
      <div id="rename-dialog" class="dialog" style="display:<?php echo isset($_REQUEST['rename']) ? 'block' : 'none';?>;">
        <a href="<?php echo $currlink; ?>"><img src="images/close.png" class="close" alt="Close dialog"/></a>
        <form action="rename.php" method="POST">
          <h2>Rename <span class="name"><?php echo htmlspecialchars($name); ?></span></h2>
          <label for="comment">Comment:</label>
          <textarea name="comment" style="width:90%; height:4em;"></textarea>
          <input type="hidden" name="sourcedir" value="<?php echo htmlspecialchars($dir); ?>" />
          <input type="hidden" name="sourcename" value="<?php echo htmlspecialchars($name); ?>" />
          <label for="targetname">New name:</label>
          <input type="text" name="targetname" value="<?php echo htmlspecialchars($name); ?>" style="width:90%"/>
          <br />
          <input type="submit" class="submit" name="rename" value="Rename"/>
        </form>
      </div>
    </div>
    <?php endforeach;
    endif; ?>
    <div class="dialog-container">
	    <div id="addfolder-dialog" class="dialog" style="display:<?php echo isset($_REQUEST['addfolder']) ? 'block' : 'none';?>;">
        <a href="<?php echo $currlink; ?>"><img src="images/close.png" class="close" alt="Close dialog"/></a>
	      <form action="add.php" method="POST">
	        <h2><img src="images/folder.png" alt=""/> Create Folder</h2>
	        <label for="comment">Comment:</label>
	        <textarea name="comment" style="width:90%; height:4em;"></textarea>
          <input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir); ?>" />
	        <label for="name">Folder name:</label>
	        <input type="text" name="name" value="" style="width:90%"/>
          <br />
	        <input type="submit" class="submit" name="addfolder" value="Create"/>
	      </form>
	    </div>
	  </div>
    <div class="dialog-container">
	    <div id="addfile-dialog" class="dialog" style="display:<?php echo isset($_REQUEST['addfile']) ? 'block' : 'none';?>;">
        <a href="<?php echo $currlink; ?>"><img src="images/close.png" class="close" alt="Close dialog"/></a>
	      <form action="add.php" method="POST" enctype="multipart/form-data">
	        <h2><img src="images/unknown.png" alt=""/> Upload file(s)</h2>
          <input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir); ?>" />
	        <label for="comment">Comment:</label>
	        <textarea name="comment" style="width:90%; height:4em;"></textarea>
	        <label for="file-0">File(s):</label>
          <div class="applet" style="width:1px; height:1px; overflow:hidden;">
            <div style="float:left; margin-right:10px;">
              <button id="selectfile" name="selectfile">Select files</button> or
            </div>
            <applet id="uploadApplet" name="UploadApplet" code="net.sf.phpeasyvcs.UploadApplet" 
	 			            archive="applet/vcsapplet.jar?v=<?php echo $v; ?>" width="200"  height="25">
              <param name="root" value="<?php echo htmlspecialchars($uri) . ($dir ? '/'.substr($dir,0,-1)    : ''); ?>">
            </applet>
            <!--
            <object type="application/x-java-applet;version=1.5" style="float:left;"
                    width="200px" height= "25px" id="uploadApplet" name="UploadApplet">  
                                <param name="archive" value="applet/vcsapplet.jar?v=<?php echo $v; ?>">
                                <param name="code" value="net.sf.phpeasyvcs.UploadApplet">
                                <param name="MAYSCRIPT" value="yes">
                                <param name="root" value="<?php echo htmlspecialchars($uri) . ($dir ? '/'.substr($dir,0,-1)    : ''); ?>">
            </object>
            -->
            <div style="clear:both"></div>
            <div class="filelist">
              <table id="files" class="list">
                <tbody></tbody>
              </table>
            </div>
          </div>
	        <input type="file" name="file-0" style="width:90%;"/>
	        <input type="file" name="file-1" style="width:90%;"/>
	        <input type="file" name="file-2" style="width:90%;"/>
          <input type="file" name="file-3" style="width:90%;"/>
          <input type="file" name="file-4" style="width:90%;"/>
          <input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
          <div id="extractItem"><input type="checkbox" name="extract" value="1" /> Extract ZIP files</div>
	        <input type="submit" class="submit" name="addfile" value="Upload"/>
	      </form>
        <script type="text/javascript">
          function showSelectResult(path) {
            var $td = $('<td/>').text(path);
            $td.append($('<input type="hidden" name="path" value=""/>').val(path));
            $td.append('<div class="progress-line"></div>');
            var $tr = $('<tr/>').append($td).append('<td class="link delete"><a title="Remove file" href="#">X</a></td>');
            $('#addfile-dialog table tbody').append($tr);
          }
          function showUploadProgress(path, progress, total, percent) {
            $('#addfile-dialog input[name=path]').each(function(i,input) {
              if (input.value == path) {
                if (progress > 0 && total > 0) {
                  $(input).closest('td').find('.progress-line').removeClass('failed').css('width',percent+'%');
                } else {
                  $(input).closest('td').find('.progress-line').addClass('failed').css('width','100%');
                }
              }
            });
          }
          function showUploadResult(result) {
            if (result == true) {
              window.location = <?php echo json_encode($currlink); ?>;
            } else {
              var failedFiles = $.parseJSON(result);
              alert("The following files could not be uploaded:\r\n - "+failedFiles.join('\r\n - '));
              window.location = <?php echo json_encode($currlink); ?>;
            }
          }
          function showDownload() {
            $('#downloadAppletItem').show();
          }
          function showUpload() {
            // called from the applet when it finished initializing
            // the applet must be in a visible div (1px/1px), otherwise it won't load in some browsers
            //$('#addfile-dialog .applet').show();
            $('#addfile-dialog .applet').attr('style','');
            $('#addfile-dialog input[type=file]').hide();
            $('#addfile-dialog #extractItem').hide();
            $('#addfile-dialog input[type=submit]').click(function(e) {
              e.preventDefault();
              var comment = $('#addfile-dialog [name=comment]').val();
              var extract = $('#addfile-dialog [name=extract]:checked').size() > 0
              document.UploadApplet.setComment(comment);
              document.UploadApplet.upload(extract);
            });
            $('#selectfile').click(function(e) {
              e.preventDefault();
              document.UploadApplet.selectLocalFiles();
            });
            $('#addfile-dialog table').delegate('td.delete a','click',function(e) {
              var path = $(e.target).closest('tr').find('input[name=path]').val();
              document.UploadApplet.removeLocalFile(path);
              $(e.target).closest('tr').remove();
            })
          }
        </script>
	    </div>
	  </div>
    <?php } ?>
	<script type="text/javascript">
    var downloading = false;
    function startDownload() {
      downloading = true;
    }
    function showDownloadProgress(text, progress, total, percent) {
      if ($('#progress-dialog:visible').size() == 0) {
        $('#progress-dialog').dialog();
      }
      if (progress == 0) {
        $('#progress-dialog .progress-text').text(text);
      } else {
        $('#progress-dialog .progress-text').text("Downloading...");
      }
      $('#progress-dialog .progress-bar').show().css('width',percent+'%');
    }
    function showDownloadResult(ok) {
      if ($('#progress-dialog:visible').size() != 0) {
        $('#progress-dialog').dialog('close');
      }
      downloading = false;
    }    
    function showError(error) {
      alert("Error: "+error);
    }
	  $(function() {
      var isPulldownOpen = false;
      $('td.rename a').click(function(e) {
        e.preventDefault();
        var name = $(e.target).closest('tr').find('input[type=checkbox]').val();
        $('#rename-dialog span.name').text(name);
        $('#rename-dialog [name=sourcename]').val(name);
        $('#rename-dialog [name=targetname]').val(name);
        $('#rename-dialog').dialog({background:$('#dialog-background')});
      });
	    $('a.addfolder').click(function(e) {
	      e.preventDefault();
	      $('#addfolder-dialog').dialog({background:$('#dialog-background')});
	    });
	    $('a.addfile').click(function(e) {
	      e.preventDefault();
	      $('#addfile-dialog').dialog({background:$('#dialog-background')});
	    });
	    $('.close').click(function(e) {
	      e.preventDefault();
	      $(e.target).closest('.dialog').dialog('close');
	    });
      if (document.DownloadApplet) {
        $('.pulldown').mouseenter(function(e) {
          // In Firefox applet is only initialized, when shown and destroyed, when hidden.
          // Additionally mouseleave (see below) is triggered in Ubuntu, when the mouse moves onto the applet.
          // Thus we have to force the pulldown menu to stay open:
          $(e.target).closest('.pulldown').addClass('open');
          isPulldownOpen = true;
          // add checked files:
          document.DownloadApplet.removeAllFiles();
          $('.list tbody [type=checkbox]:checked').each(function(i,cb) {
            document.DownloadApplet.addFile(cb.value);
          })
        });
        $('.pulldown').mouseleave(function(e) {
          // We have to force the pulldown menu to stay open, while the applet is needed (see above)
          if (isPulldownOpen && !downloading && e.relatedTarget) {
            $(e.target).closest('.pulldown').removeClass('open');
          }
        });
        $('.list tbody td').mousemove(function(e) {
          // We have to force the pulldown menu to stay open, while the applet is needed (see above)
          if (isPulldownOpen && !downloading) {
            $('.list thead .pulldown').removeClass('open');
          }
        });
        $('input[name=download]').click(function(e) {
          e.preventDefault();
          document.DownloadApplet.download();
        });
      }
	  });
	</script>
<?php
  } else {
?>
<p>Directory does not exist!</p>
<?php    
  }
  template_footer();

function display_move_copy($entry) {
  if (($rel = $entry->movedfrom)) {
    echo ' <i>(moved from <a href="'.get_display_link($rel).'">'.
      htmlspecialchars($rel->dir.$rel->name).'</a>'.
      ($rel->isFile ? ', '.$rel->version : '').')</i>';
  } else if (($rel = $entry->copyof)) {
    echo ' <i>(copy of <a href="'.get_display_link($rel).'">'.
      htmlspecialchars($rel->dir.$rel->name).'</a>'.
      ($rel->isFile ? ', '.$rel->version : '').')</i>';
  } else if (($rel = $entry->movedto)) {
    echo ' <i>(moved to <a href="'.get_display_link($rel).'">'.
      htmlspecialchars($rel->dir.$rel->name).'</a>'.
      ($rel->isFile ? ', '.$rel->version : '').')</i>';
  }
}

function get_display_link($entry) {
  if ($entry->isDirectory) {
    return 'browse.php?dir='.urlencode($entry->dir.$entry->name).($entry->movedfrom ? '&amp;all=1' : '');
  } else {
    return 'versions.php?dir='.urlencode($entry->dir).'&amp;name='.urlencode($entry->name);
  }
}
  
function display_tree($paramname, $aDir=null, $i=0) {
  global $vcs, $dir, $names;
  if ($aDir === null) {
    echo '<ul>';
    echo '<li>';
    echo '<input type="radio" name="'.$paramname.'" value="/" checked="checked"/>';
    echo '<label for="folder'.$i.'">/</label>';
    echo '<input type="checkbox" id="folder'.$i.'" class="toggle" checked="checked"/>';
    $i++;
    $i = display_tree($paramname, '', $i);
    echo '</li>';
    echo '</ul>'; 
  } else {
    $listing = $vcs->getListing($aDir);
    if (count($listing->directories) > 0) {
      echo '<ul>';
      foreach ($listing->directories as $entry) {
        if ($entry->dir != $dir || !in_array($entry->name, $names)) {
          echo '<li>';
          echo '<input type="radio" name="'.$paramname.'" value="'.htmlspecialchars($entry->dir.$entry->name).'"/>';
          echo '<label for="folder'.$i.'">'.htmlspecialchars($entry->name).'</label>';
          echo '<input type="checkbox" id="folder'.$i.'" class="toggle"/>';
          $i++;
          $i = display_tree($paramname, $entry->dir.$entry->name, $i);
          echo '</li>';
        }
      }
      echo '</ul>'; 
    }
  }
  return $i;
}
