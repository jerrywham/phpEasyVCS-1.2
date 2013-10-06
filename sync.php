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

  $dir = sanitizeDir($_GET['dir']);
  $vcs = new FileVCS(DATAPATH, @$_GET['tag'], getUserName(), isReadOnly());
  $tag = $vcs->getTag();
  $tagname = $tag && $tag->name ? $tag->name : ($tag ? $_GET['tag'] : null);

  template_header();  
  $v = filemtime(ROOTPATH.'applet/vcsapplet.jar');
  $uri = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http";
  $uri .= "://".$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"];
  $uri = substr($uri,0,strlen($uri)-8) . 'rest.php/current';
  $currlink = 'browse.php?'.($tagname ? 'tag='.urlencode($tagname).'&' : '').'dir='.urlencode($dir);
?>

<noscript>
  <div class="error">This page requires Javascript and a Java Applet!</div>
</noscript>
<ul class="actions">
  <?php if (!$tagname) { ?>
  <li style="display:none"><a href="#" id="open-start">Start</a></li>
  <?php } ?>
  <li style="display:none"><a href="#" id="refresh-all">Refresh</a></li>
  <li style="display:none"><a href="#" id="open-all">Open all</a></li>
  <li style="display:none"><a href="#" id="close-all">Close all</a></li>
  <li style="display:none"><a href="#" id="show-identical" class="toggle">Show identical files</a></li>
  <li><a href="<?php echo htmlspecialchars($currlink); ?>">Back to directory</a></li>
</ul>
<h2>
  Synchronize 
  <a href="<?php echo htmlspecialchars($currlink); ?>">/<?php echo htmlspecialchars(substr($dir,0,-1)); ?></a>
</h2>
<p id="localrootp">
  Local Directory: <span id="localroot"></span> 
  <button id="selectlocalroot">Select</button>
</p>
<div id="progress" class="progress" style="display:none;">
  <div class="progress-bar"></div>
  <div class="progress-text"></div>
</div>
<table id="entries" class="list hide-identical" style="display:none;">
  <thead>
    <tr><th rowspan="2">Name</th><th colspan="3" class="side">Local</th><th rowspan="2" colspan="5" class="action">Action</th><th colspan="3" class="side">Remote</th><th rowspan="2" colspan="3"></th></tr>
    <tr><th class="version">Version</th><th class="size">Size</th><th class="date">Date</th><th class="version">Version</th><th class="size">Size</th><th class="date">Date</th></tr> 
  </thead>
  <tbody>
  </tbody>
</table>

<?php if (!$tagname) { ?>
<a id="dialog-background" class="dialog-background" href="<?php echo $currlink; ?>" style="display:<?php echo isset($_GET['addfolder']) || isset($_GET['addfile']) ? 'block' : 'none'; ?>"></a>
<div class="dialog-container">
  <div id="start-dialog" class="dialog" style="display:<?php echo isset($_GET['addfolder']) ? 'block' : 'none';?>;">
    <a href="<?php echo $currlink; ?>"><img src="images/close.png" class="close" alt="Close dialog"/></a>
    <form action="add.php" method="POST">
      <h2>Start Synchronisation</h2>
      <p>This will start copying and deleting files as indicated.</p>
      <label for="comment">Comment:</label>
      <textarea name="comment" style="width:90%; height:4em;"></textarea>
      <br />
      <input type="submit" class="submit" id="start" name="start" value="Start"/>
    </form>
  </div>
</div>
<?php } ?>

<p style="text-align: center;">
  <object type="application/x-java-applet;version=1.5"
          width="1" height= "1" id="applet" name="SyncApplet">  
                      <param name="archive" value="applet/vcsapplet.jar?v=<?php echo $v; ?>">
                      <param name="code" value="net.sf.phpeasyvcs.SynchronizerApplet">
                      <param name="MAYSCRIPT" value="yes">
                      <param name="root" value="<?php echo htmlspecialchars($uri) . ($dir ? '/'.substr($dir,0,-1)    : ''); ?>">
  </object>
</p>

<script type="text/javascript">
  // <![CDATA[
  var num = 0;
  var dirIds = { };
  var icons = <?php echo json_encode(Filetype::getIcons()); ?>;
  function showLocalRoot(localRoot) {
    $('#localroot').text(localRoot);
  }
  function showScanProgress(progress, total, percent) {
    $('#selectlocalroot').hide();
    $('#progress').show();
    $('#progress .progress-text').text(progress+" directories of "+total);
    $('#progress .progress-bar').css('width',percent+'%');
  }
  function showScanResult(dir, jsonEntries) {
    var entries = $.parseJSON(jsonEntries);
    displayEntries(dir, entries);
    $('#progress').hide();
    $('#entries').show();
    $('ul.actions, ul.actions li').show();
  }
  function displayEntries(dir, entries) {
    var id = null;
    var level = 0;
    var dirclasses = '';
    var $prevRow = null;
    if (dir) {
      parentId = dirIds[dir];
      if (!parentId) return;
      $('#entries tbody tr.'+parentId).remove();
      dirclasses = $('#'+parentId).attr('class');
      dirclasses = dirclasses.replace(/[^\s]+-curr|open|closed|directory|root/g, '').replace(/\s+/g, ' ').trim();
      dirclasses = dirclasses + ' ' + parentId + ' ' + parentId + '-curr';
      level = dir.split("/").length;
      $prevRow = $('#'+parentId);
    } else {
      dirclasses = 'root';
      $('#entries tbody tr').remove();
    }
    var $tbody = $('#entries tbody');
    for (var i in entries) {
      var entry = entries[i];
      var isdir = (entry['local'] && entry['local']['dir']) || (entry['remote'] && entry['remote']['dir']);
      var dirId = null;
      var dirName = null;
      var entryNum = num++;
      if (isdir) {
        dirId = 'dir-'+entryNum;
        dirName = (dir ? dir+'/' : '')+entry['name'];
        dirIds[dirName] = dirId;
      }
      var $row = $('<tr '+(dirId ? 'id="'+dirId+'" ' : '')+'class="'+(isdir ? 'open directory' : entry['action'])+' '+dirclasses+'"/>');
      var $td = $('<td class="name" style="padding-left:'+(7+16*level)+'px"></td>').text(entry['name']);
      $td.append($('<input type="hidden" name="entry-'+entryNum+'" value=""/>').val((dir ? dir+'/' : '')+entry['name']));
      if (isdir) {
        $td.prepend('<img class="closed" src="images/folder.png" alt="Open folder" /> ');
        $td.prepend('<img class="open" src="images/folder_open.png" alt="Close folder" /> ');
        $td.append('<img class="refresh" src="images/refresh.png" alt="Refresh" />');
      } else {
        var pos = entry['name'].lastIndexOf('.');
        var icon = icons[entry['name'].substring(entry['name'].lastIndexOf('.')+1)];
        if (!icon) icon = 'unknown';
        $td.prepend('<img src="images/'+icon+'.png" alt=""/> ');
      }
      $row.append($td);
      if (entry['local']) {
        var size = entry['local']['size'];
        if (!size) size ='';
        var version = entry['local']['version'];
        if (version < 0) version = "";
        $row.append('<td class="version">'+version+'</td>');
        $row.append('<td class="size">'+size+'</td>');
        $row.append('<td class="date">'+entry['local']['date']+'</td>');
      } else {
        $row.append('<td colspan="3"></td>');
      }
      if (isdir) {
        $row.append('<td colspan="5"></td>');
      } else {
        if (entry['local']) {
          $row.append('<td class="link delete DELETE_LOCAL"><a href="#" title="Delete local">X</a></td>');
        } else $row.append('<td class="nolink"></td>');
        if (entry['remote'] && entry['action'] != 'IDENTICAL') {
          $row.append('<td class="link COPY_TO_LOCAL"><a href="#" title="Copy to local">&lt;&lt;</a></td>');
        } else $row.append('<td class="nolink"></td>');
        $row.append('<td class="link NONE"><a href="#" title="Do nothing">-</a></td>');
        if (entry['local'] && entry['action'] != 'IDENTICAL') {
          $row.append('<td class="link COPY_TO_REMOTE"><a href="#" title="Copy to remote">&gt;&gt;</a></td>');
        } else $row.append('<td class="nolink"></td>');
        if (entry['remote']) {
         $row.append('<td class="link delete DELETE_REMOTE"><a href="#" title="Delete remote">X</a></td>');
        } else $row.append('<td class="nolink"></td>');
      }
      if (entry['remote']) {
        var size = entry['remote']['size'];
        if (!size) size = "";
        $row.append('<td class="version">'+entry['remote']['version']+'</td>');
        $row.append('<td class="size">'+size+'</td>');
        $row.append('<td class="date">'+entry['remote']['date']+'</td>');
      } else {
        $row.append('<td colspan="3"></td>');
      }
      if (isdir) {
        $row.append('<td colspan="3"></td>');
      } else if (entry['local'] && entry['remote']) {
        $row.append('<td class="link quickview"><a href="#" title="Quick view local">&lt;Q</a></td>');
        if (entry['action'] != 'IDENTICAL') {
          $row.append('<td class="link merge"><a href="#" title="Compare/Merge">M</a></td>');
        } else {
          $row.append('<td class="nolink"></td>');
        }
        $row.append('<td class="link quickview"><a href="#" title="Quick view remote">Q&gt;</a></td>');
      } else if (entry['local']) {
        $row.append('<td class="link quickview"><a href="#" title="Quick view local">&lt;Q</a></td>');
        $row.append('<td colspan="2"></td>');
      } else {
        $row.append('<td colspan="2"></td>');
        $row.append('<td class="link quickview"><a href="#" title="Quick view remote">Q&gt;</a></td>');
      }
      if ($prevRow) $prevRow.after($row); else $tbody.append($row);
      $prevRow = $row;
      if (isdir) {
        $prevRow = displayEntries(dirName, entry['entries']);
        if ($('#entries tr.'+dirId+':not(.IDENTICAL)').size() <= 0) $row.addClass('IDENTICAL');
      } 
    }
    return $prevRow;
  }
  $(function() {
    $('#localrootp').show();
    var localroot = document.SyncApplet.getLocalRoot();
    $('#localroot').text(localroot);
    $('#selectlocalroot').click(function(e) {
      e.preventDefault();
      document.SyncApplet.selectLocalRoot(true);
    });
    $('#synchronize').click(function(e) {
      e.preventDefault();
      document.SyncApplet.scan();
    });
    $('#entries').delegate('td.link a','click',function(e) {
      e.preventDefault();
      var $td = $(e.target).closest('td');
      var $tr = $(e.target).closest('tr');
      var cl = "";
      if ($td.hasClass('DELETE_LOCAL') && $tr.hasClass('DELETE_REMOTE')) {
        cl = 'DELETE_BOTH';
      } else if ($td.hasClass('DELETE_REMOTE') && $tr.hasClass('DELETE_LOCAL')) {
        cl = 'DELETE_BOTH';
      } else if ($td.hasClass('DELETE_LOCAL')) {
        cl = 'DELETE_LOCAL';
      } else if ($td.hasClass('DELETE_REMOTE')) {
        cl = 'DELETE_REMOTE';
      } else if ($td.hasClass('COPY_TO_LOCAL')) {
        cl = 'COPY_TO_LOCAL';
      } else if ($td.hasClass('COPY_TO_REMOTE')) {
        cl = 'COPY_TO_REMOTE';
      } else if ($td.hasClass('NONE')) {
        cl = 'NONE';
      }
      $tr.removeClass('DELETE_LOCAL COPY_TO_LOCAL NONE COPY_TO_REMOTE DELETE_REMOTE MERGE DELETE_BOTH');
      $tr.addClass(cl);
    });
    $('#entries').delegate('tr.directory td.name img.open, tr.directory td.name img.closed','click',function(e) {
      e.preventDefault();
      var $tr = $(e.target).closest('tr');
      var id = $tr.attr('id');
      if ($tr.hasClass('open')) {
        $('#entries tr.'+id).addClass('hidden');
        $('#entries tr.open.'+id).removeClass('open').addClass('closed');
        $tr.removeClass('open').addClass('closed');
      } else {
        $('#entries tr.'+id+'-curr').removeClass('hidden');
        $tr.removeClass('closed').addClass('open');
      }
    });
    $('#entries').delegate('tr.directory td.name img.refresh','click',function(e) {
      e.preventDefault();
      var dir = $(e.target).closest('td').find('[name^=entry-]').val();
      document.SyncApplet.scan(dir);
    });
    $('#refresh-all').click(function(e) {
      e.preventDefault();
      document.SyncApplet.scan();
    });
    $('#show-identical').click(function(e) {
      e.preventDefault();
      $(e.target).toggleClass('on');
      $('#entries').toggleClass('hide-identical');
    });
    $('#open-all').click(function(e) {
      e.preventDefault();
      $('#entries tbody tr').removeClass('hidden');
      $('#entries tbody tr.directory').removeClass('closed').addClass('open');
    });
    $('#close-all').click(function(e) {
      e.preventDefault();
      $('#entries tbody tr:not(.root)').addClass('hidden');
      $('#entries tbody tr.directory').removeClass('open').addClass('closed');
    });
    $('#open-start').click(function(e) {
      e.preventDefault();
      $('#start-dialog').dialog();
    });
    $('#start').click(function(e) {
      e.preventDefault();
      document.SyncApplet.sync();
    });
    $('.close').click(function(e) {
      e.preventDefault();
      $(e.target).closest('.dialog').dialog('close');
    })
  });
  // ]]>
</script>

<?php
  template_footer();