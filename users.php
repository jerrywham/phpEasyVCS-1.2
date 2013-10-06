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

  checkUserLevel(USERLEVEL_ADMIN);
  $errors = array();
  # get settings
  $settings = @simplexml_load_file(ROOTPATH.'data/settings.xml', 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOENT);
  # get users
  if (file_exists(ROOTPATH.'data/users.xml')) {
    $users = simplexml_load_file(ROOTPATH.'data/users.xml', 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOENT);
  } else {
    $users = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><users></users>');
  }
  //print_r($users);
  # get repositories
  $repositories = array();
  $dh = opendir(ROOTPATH.'data');
  while (($filename = readdir($dh)) !== false) {
    if (substr($filename,0,1) != '.' && is_dir(ROOTPATH.'data/'.$filename)) $repositories[] = $filename;
  }
  sort($repositories);
  # process request
  $user = null;
  if (@$_REQUEST['name']) {
    if ($users->user) foreach ($users->user as $u) if ((string) $u->name == $_REQUEST['name']) $user = $u;
    if (!$user) {
      header('Location: users.php?error='.urlencode('User '.$_REQUEST['name'].' could not be found. '));
      die;
    }
  }
  if (isset($_REQUEST['delete'])) {
    # delete user
    if ($users->user) foreach ($users->user as $u) {
      if ((string) $u->name == $_REQUEST['name']) {
        $dom=dom_import_simplexml($u);
        $dom->parentNode->removeChild($dom);      
      }
    }
    $success = $users->asXML(ROOTPATH.'data/users.xml') === TRUE;
    if ($success) {
      header('Location: users.php?msg='.urlencode('User '.$_REQUEST['name'].' was successfully deleted. '));
    } else {
      header('Location: users.php?error='.urlencode('Error deleting user '.$_REQUEST['name'].'. '));
    }
    die;
  } else if (isset($_POST['save'])) {
    # add or update user
    if (@$_REQUEST['name']) {
      $username = $_REQUEST['name'];
    } else if (!@$_POST['username']) {
      $errors[] = 'Missing user name!';
      $username = '';
    } else {
      $username = $_POST['username'];
      # check, if already existing
      if ($users->user) foreach ($users->user as $u) {
        if ((string) $u->name == $username) { 
          $errors[] = 'Duplicate user name!'; 
          break; 
        }
      }
    }
    if (!@$_REQUEST['name'] && !@$_POST['password']) {
      $errors[] = 'You need to specify a password';
    } else if (@$_POST['password'] && $_POST['password'] != @$_POST['password2']) {
      $errors[] = 'The passwords do not match!';
    } else if (@$_REQUEST['name'] && !@$_POST['password']) {
      # check, if user has been authorized for additional repositories
      $reps = array();
      if ($user->repository) foreach ($user->repository as $r) $reps[] = (string) $r->name;
      foreach ($repositories as $repository) {
        if (@$_POST['level_'.$repository] && !in_array($repository, $reps)) {
          $errors[] = 'You must (re)specify a password, if you add repositories.';
          break;
        }
      }
    }
    # check, if user is authorized for at least one repository
    $reps = array();
    foreach ($repositories as $repository) {
      if (@$_POST['level_'.$repository]) $reps[] = $repository;
    }
    if (count($reps) <= 0) {
      $errors[] = 'You must enable at least one repository. ';
    }
    if (count($errors) == 0) {
      if (!$user) {
        $user = $users->addChild('user');
        $user->name = $username;
      }
      $user->timezone = (string) @$_POST['timezone'];
      foreach ($repositories as $repository) {
        if (@$_POST['level_'.$repository]) {
          $rep = null;
          if ($user->repository) foreach ($user->repository as $r) {
            if ($r->name == $repository) {
              $rep = $r;
              break;
            }
          }
          if (!$rep) {
            $rep = $user->addChild('repository');
            $rep->name = $repository;
          }
          $rep->level = $_POST['level_'.$repository];
          if (@$_POST['password']) {
            $realm = $settings->realm;
            $rep->a1 = md5($username.':'.$realm.':'.$_POST['password']);  
            $rep->a1r = md5($repository.'\\'.$username.':'.$realm.':'.$_POST['password']);  
          }
        } else {
          if ($user->repository) foreach ($u->repository as $r) {
            if ($r->name == $repository) {
              $dom=dom_import_simplexml($r);
              $dom->parentNode->removeChild($dom);      
            }
          }
        }
      }
      $success = $users->asXML(ROOTPATH.'data/users.xml') === TRUE;
      if ($success) {
        header('Location: users.php?name='.urlencode($username).'&msg='.urlencode('User '.$_REQUEST['name'].' was successfully saved. '));
        die;
      }
      $errors[] = 'Error saving user! ';
    }
    $timezone = @$_POST['timezone'];
    $levels = array();
    foreach ($repositories as $repository) {
      $levels[$repository] = @$_POST['level_'.$repository];
    }
  } else if (isset($_REQUEST['name']) && !empty($_REQUEST['name'])) {
    $username = $_REQUEST['name'];
    $timezone = @$user->timezone;
    $levels = array();
    if ($user->repository) foreach ($user->repository as $r) $levels[(string) $r->name] = (string) $r->level;
  } else if (isset($_REQUEST['name']) && empty($_REQUEST['name'])) {
    $username = '';
    $timezone = TIMEZONE;
    $levels = array();
  }
  template_header();
  if (isset($_REQUEST['name'])) {
    $timezones = timezone_identifiers_list();
?>
  <ul class="actions">
    <li><a href="users.php">Back to users</a></li>
  </ul>
  <?php if (count($errors) > 0) { ?>
    <?php foreach ($errors as $error) { ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php } ?>
  <?php } ?>
  <h2><?php echo @$_REQUEST['name'] ? 'User '.htmlspecialchars($username) : 'Add User'; ?></h2>
  <form method="POST">
    <table class="form">
      <?php if (!@$_REQUEST['name']) { ?>
        <tr>
          <td>User name</td>
          <td><input name="username" value="<?php echo htmlspecialchars($username); ?>"></td>
          <td></td>
        </tr>
      <?php } ?>
      <tr>
        <td>User password</td>
        <td><input type="password" name="password" value=""/></td>
        <td></td>
      </tr>
      <tr>
        <td>User password (repeated)</td>
        <td><input type="password" name="password2" value=""/></td>
        <td></td>
      </tr>
      <tr>
        <td>Time zone</td>
        <td>
          <select name="timezone">
            <?php foreach ($timezones as $tz) echo '<option'.($timezone == $tz ? ' selected="selected"' : '').'>'.$tz."</option>\r\n"; ?>
          </select>
        </td>
        <td></td>
      <tr>
      <tr>
        <td>Repositories</td>
        <td>
          <table>
          <?php foreach ($repositories as $rep) { ?>
            <tr>
              <td><?php echo htmlspecialchars($rep); ?></td>
              <td>
                <select name="level_<?php echo htmlspecialchars($rep); ?>">
                  <option value="">(no access)</option>
                  <option value="<?php echo USERLEVEL_VIEW; ?>" <?php if (@$levels[$rep] == USERLEVEL_VIEW) echo 'selected="selected"'; ?>>read only</option>
                  <option value="<?php echo USERLEVEL_EDIT; ?>" <?php if (@$levels[$rep] == USERLEVEL_EDIT) echo 'selected="selected"'; ?>>full access</option>
                </select>
              </td>
            </tr>
          <?php } ?>
          </table>
        </td>
      </tr>
      <tr>
        <td>
          <input type="hidden" name="save" value="save"/>
          <?php if (@$name) { ?><input type="hidden" name="name" value="<?php echo htmlspecialchars($name); ?>" /><?php } ?>
        </td>
        <td colspan="2"><input type="submit" value="Save"/> or <a href="users.php">Cancel</a></td>
      </tr>
    </table>
  </form>
<?php 
  } else { 
    $allusers = array();
    if ($users->user) foreach ($users->user as $u) $allusers[(string) $u->name] = $u;
    ksort($allusers);
?>
  <ul class="actions">
    <li><a href="users.php?name=">Add user</a></li>
  </ul>
  <h2>Users</h2>
  <table class="list">
    <thead>
      <tr><th>User name</th><th>Time zone</th><th>Repositories</th><th></th></tr>
    </thead>
    <tbody>
      <?php if (count($allusers) <= 0) { ?>
        <tr><td colspan="3"><i>No users found</i></td></tr>
      <?php } else foreach ($allusers as $u) { ?>
        <tr>
          <td><a href="users.php?name=<?php echo htmlspecialchars((string) $u->name); ?>"><?php echo htmlspecialchars((string) $u->name); ?></a></td>
          <td><?php echo htmlspecialchars((string) $u->timezone); ?></td>
          <td>
            <?php
              $reps = array();
              if ($u->repository) foreach ($u->repository as $r) $reps[(string) $r->name] = (string) $r->level;
            ?>
            <?php foreach ($reps as $rname => $rlevel) { ?>
              <p><?php echo htmlspecialchars((string) $rname); ?> (<?php echo $rlevel == (string) USERLEVEL_EDIT ? 'full access' : 'read only'; ?>)</p>
            <?php } ?>
          </td>
          <td class="link delete">
            <a href="<?php echo 'users.php?name='.urlencode((string) $u->name).'&delete='; ?>" 
                title="Delete user: <?php echo htmlspecialchars((string) $u->name); ?>">X</a>
          </td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
<?php
  }
  template_footer();