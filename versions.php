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

  $dir = sanitizeDir($_GET['dir']);
  $name = sanitizeName($_GET['name']);
  $vcs = new FileVCS(DATAPATH, @$_GET['tag'], getUserName(), isReadOnly());
  $tag = $vcs->getTag();
  $tagname = $tag && $tag->name ? $tag->name : ($tag ? $_GET['tag'] : null);
  $files = $vcs->getHistory($dir, $name);
  $currvcs = new FileVCS(DATAPATH, null, getUserName(), isReadOnly());
  $allfiles = $currvcs->getHistory($dir, $name);
  $currfile = $allfiles[count($allfiles)];
  $fullhistory = count($allfiles) == count($files);

  template_header();
  $tagtext = '';
  if ($tag && $tag->date) $tagtext .= ' <span>until</span> <span class="date">'.timestamp2string($tag->date).'</span>';
  if ($tag && $vcs->getTag($tag->name)) $tagtext .= ' ('.htmlspecialchars($tag->name).')';
  
  $numversions = 0;
  foreach ($files as $f) if (!$f->deleted) $numversions++;
  
  foreach ($files as $file) break;
  $quickview = preg_match('@^(text|image)/@',$file->mimetype);
  $diffable = preg_match('@^(text)/@',$file->mimetype);
  $icon = Filetype::getIcon($file->ext).'.png';
  $vfrom = 0;
  $vto = 0;
?>
    <ul class="actions">
      <li><a href="<?php echo 'browse.php?'.($tagname ? 'tag='.urlencode($tagname).'&amp;' : '').'dir='.urlencode($dir); ?>">Back to directory</a></li>
    </ul>

    <h2>
      History of <img src="images/<?php echo $icon; ?>" alt=""/> 
      <a href="<?php echo 'browse.php?'.($tagname ? 'tag='.urlencode($tagname).'&amp;' : '').'dir='.urlencode($dir); ?>">/<?php echo htmlspecialchars(substr($dir,0,-1)); ?></a><?php echo ($dir ? '/' : '').htmlspecialchars($name); ?>
      <?php echo $tagtext; ?>
    </h2>
    <form action="diff.php" method="GET">
      <input type="hidden" name="tag" value="<?php echo htmlspecialchars($tagname); ?>" />
      <input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir); ?>" />
      <input type="hidden" name="name" value="<?php echo htmlspecialchars($name); ?>" />
      <table class="list">
        <thead>
          <tr>
            <th colspan="2" style="text-align:center;">
              <?php if ($diffable && $numversions >= 2) { ?>
                <input type="submit" name="diff" value="Diff"/>
              <?php } ?>
            </th>
            <th class="version">Version</th>
            <th>Name</th>
            <th class="size">Size</th>
            <th class="date" title="<?php echo htmlspecialchars(TIMEZONE); ?>">Date</th>
            <th>User</th>
            <th>Comment</th>
            <?php if ($quickview && !isReadOnly()) { ?>
            <th colspan="2"></th>  
            <?php } else if ($quickview || !isReadOnly()) { ?>
            <th></th>
            <?php } ?>
          </tr>
        </thead>
        <tbody>
          <?php if (!$fullhistory) { ?>
            <tr>
              <td colspan="2"></td>
              <td colspan="<?php echo $quickview ? '8' : '7'; ?>">
                <a href="<?php echo 'versions.php?dir='.urlencode($dir).'&amp;name='.urlencode($name); ?>">Show full history</a>
              </td>
            </tr>
          <?php } ?>
          <?php foreach ($files as $file) { ?>
            <tr>
              <td class="radio">
                <?php if ($diffable && $numversions >= 2 && $vto && !$file->deleted) { ?>
                  <input type="radio" name="from" value="<?php echo $file->version; ?>" <?php echo !$vfrom && ($vfrom = $file->version) ? 'checked="checked"' : ''; ?> />
                <?php } ?>
              </td>
              <td class="radio">
                <?php if ($diffable && $numversions >= 2 && !$file->deleted) { ?>
                  <input type="radio" name="to" value="<?php echo $file->version; ?>" <?php echo !$vto && ($vto = $file->version) ? 'checked="checked"' : ''; ?> />
                <?php } ?>
              </td>
              <td class="version">
                <?php echo $file->version; ?>
              </td>
              <td class="name <?php echo $file->deleted ? 'deleted' : ''; ?>">
                <?php if (!$file->deleted) { ?>
                  <a href="<?php echo 'get.php?dir='.urlencode($dir).'&amp;name='.urlencode($file->name).'&amp;version='.$file->version; ?>"
                      title="Download: <?php echo htmlspecialchars($file->name); ?> (md5: <?php echo htmlspecialchars($file->md5); ?>)">
                    <?php echo htmlspecialchars($file->name); ?>
                  </a>
                <?php } else { ?>
                  (deleted)
                <?php } ?>
              </td>
              <td class="size"><?php echo !$file->deleted ? size2string($file->size) : ''; ?></td>
              <td class="date"><?php echo timestamp2string($file->date); ?></td>
              <td class="user"><?php echo htmlspecialchars(@$file->user); ?></td>
              <td class="comment">
                <?php echo str_replace('\n','<br/>',htmlspecialchars($file->comment)); ?>
                <?php display_move_copy($file); ?>
              </td>
              <?php if ($quickview && !isReadOnly() && $file->deleted) { ?>
                <td colspan="2"></td>
              <?php } else { ?>
                <?php if ($quickview && !$file->deleted) { ?>
                  <td class="link quickview">
                    <a href="<?php echo 'view.php?'.($tagname ? 'tag='.urlencode($tagname).'&amp;' : '').'dir='.urlencode($dir).'&amp;name='.urlencode($file->name).'&amp;version='.$file->version; ?>"
                        title="Quick view: <?php echo htmlspecialchars($file->name); ?>">Q</a>
                  </td>
                <?php } else if ($quickview) { ?>
                  <td></td>
                <?php } ?>
                <?php if (!isReadOnly() && $file->version < $currfile->version) { ?>
                  <td class="link revert">
                    <a href="<?php echo 'revert.php?dir='.urlencode($dir).'&amp;name='.urlencode($file->name).'&amp;version='.$file->version; ?>"
                        title="Revert to version <?php echo $file->version; ?>: <?php echo htmlspecialchars($file->name); ?>">R</a>
                  </td>
                <?php } else if (!isReadOnly()) { ?>
                  <td></td>
                <?php } ?>
              <?php } ?>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </form>
    
<?php
  template_footer();

function display_move_copy($entry) {
  if (($rel = $entry->movedfrom)) {
    echo ' <i>(moved from <a href="'.get_display_link($rel).'">'.
      htmlspecialchars($rel->dir.$rel->name).'</a>, '.$rel->version.')</i>';
  } else if (($rel = $entry->copyof)) {
    echo ' <i>(copy of <a href="'.get_display_link($rel).'">'.
      htmlspecialchars($rel->dir.$rel->name).'</a>, '.$rel->version.')</i>';
  } else if (($rel = $entry->movedto)) {
    echo ' <i>(moved to <a href="'.get_display_link($rel).'">'.
      htmlspecialchars($rel->dir.$rel->name).'</a>, '.$rel->version.')</i>';
  }
}

function get_display_link($entry) {
  return 'versions.php?dir='.urlencode($entry->dir).'&amp;name='.urlencode($entry->name);
}
