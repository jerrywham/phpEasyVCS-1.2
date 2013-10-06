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
  
  $vcs = new FileVCS(DATAPATH, null, getUserName(), isReadOnly());

  template_header();
  $tags = $vcs->getTags();
?>
  <ul class="actions">
    <li><a href="tags.php?addtag" class="addtag">Create tag</a></li>
  </ul>
  <h2>Tags</h2>
  <form action="addtag.php" method="POST">
    <table class="list">
      <thead><tr><th>Tag</th><th title="<?php echo htmlspecialchars(TIMEZONE); ?>">Date</th><th></th></tr></thead>
      <tbody>
        <tr>
          <td class="name">
          	<img src="images/folder.png" alt=""/>
          	<a href="browse.php">current</a>
          </td>
          <td class="date"></td>
          <td></td>
        </tr>
        <?php foreach ($tags as $tag) { ?>
          <tr>
            <td class="name">
            	<img src="images/folder.png" alt=""/>
            	<a href="browse.php?tag=<?php echo urlencode($tag->name); ?>"><?php echo htmlspecialchars($tag->name); ?></a>
            </td>
            <td class="date"><?php echo timestamp2string($tag->date); ?></td>
            <td class="link delete"><a href="deletetag.php?name=<?php echo urlencode($tag->name); ?>">X</a></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </form>
  <a id="dialog-background" class="dialog-background" href="tags.php" style="display:<?php echo isset($_GET['addtag']) ? 'block' : 'none'; ?>"></a>
  <div class="dialog-container">
    <div id="addtag-dialog" class="dialog" style="display:<?php echo isset($_GET['addtag']) ? 'block' : 'none';?>;">
      <a href="tags.php"><img src="images/close.png" class="close" alt="Close dialog"/></a>
      <form action="addtag.php" method="POST">
        <h2><img src="images/folder.png" alt=""/> Add Tag</h2>
        <label for="name">Tag name:</label>
        <input type="text" name="name" value="" style="width:90%"/>
        <label for="name">Tag date:</label>
        <input type="text" name="date" value="" style="width:15em"/> (yyyy-mm-dd hh:mm)
        <br />
        <input type="submit" class="submit" name="addtag" value="Create"/>
      </form>
    </div>
  </div>
  <script type="text/javascript">
    $(function() {
	  $('a.addtag').click(function(e) {
        e.preventDefault();
        $('#addtag-dialog').dialog();
	  });
      $('.close').click(function(e) {
	    e.preventDefault();
        $(e.target).closest('.dialog').dialog('close');
	  })
	});
	</script>
<?php
  template_footer();