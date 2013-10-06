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
  
  function hsc($value, $default) {
    return htmlspecialchars((string) $value ? $value : $default);
  }
  
  $errors = array();
  $timezones = timezone_identifiers_list();
  if (isset($_POST['save'])) {
    $data = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><settings></settings>');
    $data->realm = @$_POST['realm'] ? $_POST['realm'] : 'phpEasyVCS';
    $data->auth = isset($_POST['auth']) ? $_POST['auth'] : '';
    $data->secret = @$_POST['secret'] ? $_POST['secret'] : uniqid();
    if (@$_POST['timezone'] && !in_array($_POST['timezone'], $timezones)) $errors[] = 'Invalid time zone!';
    $data->timezone = @$_POST['timezone'] ? $_POST['timezone'] : 'UTC';
    $data->dateformat = @$_POST['dateformat'] ? $_POST['dateformat'] : '%Y-%m-%d %H:%M';
    $data->tmpdir = @$_POST['tmpdir'] ? $_POST['tmpdir'] : '/tmp';
    if (@$_POST['forbidpattern']) {
      if (preg_match('/'.$_POST['forbidpattern'].'/','') !== false) { 
        $data->forbidpattern = '/'.$_POST['forbidpattern'].'/';
      } else { 
        $errors[] = 'Invalid forbid pattern'; 
      }
    }
    if (@$_POST['deletepattern']) {
      if (preg_match('/'.$_POST['deletepattern'].'/','') !== false) { 
        $data->deletepattern = '/'.$_POST['deletepattern'].'/';
      } else { 
        $errors[] = 'Invalid delete pattern'; 
      }
    }
    if (@$_POST['debugging']) $data->debugging = 1;
    if (file_exists(ROOTPATH.'data/settings.xml')) {
      $olddata = @simplexml_load_file(ROOTPATH.'data/settings.xml', 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOENT);
    }
    if (!@$_POST['username']) {
      $errors[] = 'Missing user name!';
    } else if (!@$olddata && !@$_POST['password']) {
      $errors[] = 'You need to specify a password';
    } else if (@$olddata && @$_POST['username'] != (string) $olddata->admin->name && !@$_POST['password']) {
      $errors[] = 'You need to specify a password, if you change the administrator\'s user name.';
    } else if (@$olddata && @$_POST['realm'] != (string) $olddata->realm) {
      $errors[] = 'You need to specify a password, if you change the realm.';
    }
    if (@$_POST['password']) {
      if ($_POST['password'] != @$_POST['password2']) {
        $errors[] = 'The passwords do not match!';
      } else {
        $admin = $data->addChild('admin');
        $admin->name = $_POST['username'];
        $admin->a1 = md5($_POST['username'].':'.$data->realm.':'.$_POST['password']);  
        $admin->a1r = md5('default\\'.$_POST['username'].':'.$data->realm.':'.$_POST['password']);  
      }
    } else if (@$olddata){
      $admin = $data->addChild('admin');
      $admin->name = (string) $olddata->admin->name;
      $admin->a1 = (string) $olddata->admin->a1;  
      $admin->a1r = (string) $olddata->admin->a1r;  
    }
    if (count($errors) <= 0) {
      $success = $data->asXML(ROOTPATH.'data/settings.xml') === TRUE;
    }
  } else if (file_exists(ROOTPATH.'data/settings.xml')) {
    $data = @simplexml_load_file(ROOTPATH.'data/settings.xml', 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOENT);
  }
  $timezone = @$data->timezone ? (string) $data->timezone : 'UTC';
  $forbidpattern = @$data->forbidpattern ? substr($data->forbidpattern,1,-1) : '';
  $deletepattern = @$data->deletepattern ? substr($data->deletepattern,1,-1) : '';
  template_header();
?>
<?php if (count($errors) > 0) { ?>
  <?php foreach ($errors as $error) { ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php } ?>
<?php } else if (isset($success) && $success) { ?>
  <div class="msg">The settings have been successfully saved.</div>
<?php } else if (isset($success) && !$success) { ?>
  <div class="error">The settings could not be saved.</div>
<?php } ?>  
  <h2>Settings</h2>
  <form method="post">
    <table class="form">
      <tr>
        <td>Authentication method</td>
        <td>
          <select name="auth">
            <option value="basic">Basic</option>
            <option value="digest" <?php if ((string) @$data->auth == 'digest') echo 'selected="selected"'; ?> >Digest</option>
            <option value="" <?php if ((string) @$data->auth === '') echo 'selected="selected"'; ?> >None</option>
          </select>
        </td>
        <td>With Basic authentication the password is transferred in plain text, if you don't have
            an SSL connection. Digest authentication only transfers digests of the user credentials
            and is thus a bit more secure.</td>
      </tr>
      <tr>
        <td>Authentication realm</td>
        <td>
          <?php if (!@$data) { ?>
            <input type="text" name="realm" value="phpEasyVCS"/></td>
          <?php } else { ?>
            <?php echo htmlspecialchars($data->realm); ?>
            <input type="hidden" name="realm" value="<?php echo htmlspecialchars($data->realm); ?>" />
          <?php } ?>
        </td>
        <td>The authentication realm displayed to the client.</td>
      </tr>
      <tr>
        <td>Secret</td>
        <td><input type="text" name="secret" value="<?php echo hsc(@$data->secret,uniqid()); ?>"/></td>
        <td>A secret string is needed to validate the response with Digest authentication.</td>
      </tr>
      <tr>
        <td>Time zone</td>
        <td>
          <select name="timezone">
            <?php foreach ($timezones as $tz) echo '<option'.($timezone == $tz ? ' selected="selected"' : '').'>'.$tz."</option>\r\n"; ?>
          </select>
        </td>
        <td>The time zone used by phpEasyVCS.</td>
      <tr>
      <tr>
        <td>Date format</td>
        <td><input type="text" name="dateformat" value="<?php echo hsc(@$data->dateformat,'%Y-%m-%d %H:%M'); ?>"/></td>
        <td>The date format used to display dates in the web interface.</td>
      </tr>
      <tr>
        <td>Temporary directory</td>
        <td><input type="text" name="tmpdir" value="<?php echo hsc(@$data->tmpdir,'/tmp'); ?>"/></td>
        <td>The temporary directory to use for file uploads. Depending on the server setup phpEasyVCS
            might not be allowed to copy files from /tmp. In this case you should set this to the
            directory specified by your hosting provider, e.g. /users/myusername/temp</td>
      </tr>
      <tr>
        <td>Forbid pattern</td>
        <td><input type="text" name="forbidpattern" value="<?php echo hsc($forbidpattern,''); ?>"/></td>
        <td>A regular expression matching file names that should not be stored on the server.</td>
      </tr>
      <tr>
        <td>Delete pattern</td>
        <td><input type="text" name="deletepattern" value="<?php echo hsc($deletepattern,''); ?>"/></td>
        <td>A regular expression matching file names that should not be versioned, but deleted on a 
            delete request.</td>
      </tr>
      <tr>
        <td>Admin user name</td>
        <td><input type="text" name="username" value="<?php echo hsc(@$data->admin->name,'admin'); ?>"/></td>
        <td>The user name of the admistrator.</td>
      </tr>
      <tr>
        <td>Admin password</td>
        <td><input type="password" name="password" value=""/></td>
        <td>The password of the administrator.</td>
      </tr>
      <tr>
        <td>Admin password (repeated)</td>
        <td><input type="password" name="password2" value=""/></td>
        <td>The password of the administrator.</td>
      </tr>
      <tr>
        <td><input type="hidden" name="save" value="save"/></td>
        <td colspan="2"><input type="submit" value="Save"/> or <a href="browse.php">Cancel</a></td>
      </tr>
    </table>
  </form>
  <?php template_footer();