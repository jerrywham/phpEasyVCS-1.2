<?php
# +--------------------------------------------------------------------+
# | phpEasyVCS                                                         |
# | The file-based version control system                              |
# +--------------------------------------------------------------------+
# | Copyright (c) 2011 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

require_once(dirname(__FILE__).'/filevcs.class.php');

define('USERLEVEL_VIEW', 1);
define('USERLEVEL_EDIT', 2);
define('USERLEVEL_ADMIN', 3);
define('ROOTPATH', getRootPath());

define('SESSION_USERNAME', 'username');
define('SESSION_USERLEVEL', 'userlevel');
define('SESSION_REPOSITORY', 'repository');

// Use workaround for clients uploading files in two or three steps
// (delete file, add empty or one-byte file, upload file):
// remove deleted versions or zero/one-byte file versions not older than x seconds
define('WORKAROUND_SECONDS', 120);
define('WORKAROUND_MAXSIZE', 1);

if (!defined('WEBUI')) define('WEBUI', true);

function strip_slashes_from_param($value) {
  if (is_array($value)) {
    $result = array();
    foreach ($value as $v) $result[] = stripslashes($v);
    return $result;
  } else {
    return stripslashes($value);
  }
}

if (get_magic_quotes_gpc()) {
  foreach ($_GET as $key => $value) $_GET[$key] = strip_slashes_from_param($value);
  foreach ($_POST as $key => $value) $_POST[$key] = strip_slashes_from_param($value);
  foreach ($_REQUEST as $key => $value) $_REQUEST[$key] = strip_slashes_from_param($value);
}

$authenticator = new Authenticator();
$authenticator->authenticate();

class Authenticator {

  private $settings = null;
  private $users = array();

  private $username = null;
  private $userlevel = null;
  private $repository = null;

  private function loadSettings() {
    # load settings 
    if (file_exists(ROOTPATH.'data/settings.xml')) {
      $this->settings = $settings = simplexml_load_file(ROOTPATH.'data/settings.xml', 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOENT);
      
      # realm for authentication
      define('REALM', @$settings->realm ? (string) $settings->realm : 'phpEasyVCS');
      
      # authentication method
      define('AUTH', @$settings->auth ? (string) $settings->auth : false);
      
      # secret for digest authentication
      define('SECRET', @$settings->secret ? (string) $settings->secret : 'phpeasyvcs');
      
      # time zone of all dates/times without zone and the timezone displayed on the web pages
      define('TIMEZONE', @$settings->timezone ? (string) $settings->timezone : 'UTC'); 
      
      # display format for dates on the web pages              
      define('DATE_FORMAT', @$settings->dateformat ? (string) $settings->dateformat : '%Y-%m-%d %H:%M'); 
      
      # change the temporary directory if open_basedir restriction in effect
      define('TMP_DIR', @$settings->tmpdir ? (string) $settings->tmpdir : '/tmp'); 
      
      # define a pattern for files that should not be created or uploaded, e.g. '/^~.*/'
      if (@$settings->forbidpattern) define('FORBID_PATTERN', (string) $settings->forbidpattern);
      
      # define a pattern and flags for files that should be physically deleted on request instead of versioned,
      # e.g. '/^~.*|^[A-Z]{8}$/'
      if (@$settings->deletepattern) define('DELETE_PATTERN', (string) $settings->deletepattern);
      if (@$settings->deleteemptyfiles) define('DELETE_EMPTY_FILES', true);
      
      # settings for error reporting and debugging
      if (@$settings->debugging) { error_reporting(E_ALL); define("DEBUG_SEVERITY",E_USER_NOTICE); }
      
      # admin user
      #  - a1 = md5($name.':'.$realm.':'.$password)
      #  - a1r = md5($repository.'\'.$name.':'.$realm.':'.$password)
      $this->users[] = array('name' => (string) $settings->admin->name, 
                             'repository' => 'default', 'level' => USERLEVEL_ADMIN, 
                             'a1' => (string) $settings->admin->a1, 'a1r' => (string) $settings->admin->a1r); 
    } else if (defined('WEBUI') && !WEBUI) {
      header("HTTP/1.0 500 Server Error");
      exit();
    } else if (basename($_SERVER['PHP_SELF']) != 'settings.php') {
      header("Location: settings.php");
      exit();
    }
  }
  
  public function authenticate() {
    $this->loadSettings();
    if (WEBUI) {
      // user already logged in?
      session_start();
      $this->username = @$_SESSION[SESSION_USERNAME];
      $this->userlevel = @$_SESSION[SESSION_USERLEVEL];
      $this->repository = @$_SESSION[SESSION_REPOSITORY];
    }

    if (!$this->username || !$this->userlevel) {
    
      if ($this->settings) {
    
        # load users
        if (file_exists(ROOTPATH.'data/users.xml')) {
          $data = simplexml_load_file(ROOTPATH.'data/users.xml', 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOENT);
          if ($data->user) foreach ($data->user as $u) {
            if ($u->repository) foreach ($u->repository as $r) {
              $this->users[] = array('name' => (string) $u->name, 'timezone' => (string) $u->timezone,
                                     'repository' => (string) $r->name, 'level' => (string) $r->level, 
                                     'a1' => (string) $r->a1, 'a1r' => (string) $r->a1r);
            }
          }
        }
        
        $user = null;
        if (AUTH == 'digest') {
          $user = $this->authenticateDigest($this->users, REALM, SECRET);
        } else if (AUTH == 'basic') {
          $user = $this->authenticateBasic($this->users, REALM);
        }
    
        if (!AUTH) {
          if (function_exists('apache_getenv')) {
            $this->username = @apache_getenv("REMOTE_USER");
          } else if ($_SERVER['PHP_AUTH_USER']) {
            $this->username = @$_SERVER['PHP_AUTH_USER'];
          }
          $this->userlevel = USERLEVEL_ADMIN;
        } else if (!$user || (defined('USERLEVEL') && USERLEVEL > $user['level'])) {
          if (AUTH == 'digest') {
            $now = time();
            $nonce = $now."H".md5($now.':'.SECRET);
            header('WWW-Authenticate: Digest realm="'.REALM.'",qop="auth",nonce="'.$nonce.'",opaque="'.md5(REALM).'"');
          } else if (!$user) {
            header('WWW-Authenticate: Basic realm="'.REALM.'"');
          }
          header('HTTP/1.0 401 Unauthorized');
          echo 'You are not authorized';
          die();
        } else {
          $this->username = $user['name'];
          $this->userlevel = $user['level'];
          $this->repository = $user['repository'];
          if (WEBUI) {
            $_SESSION[SESSION_USERNAME] = $this->username;
            $_SESSION[SESSION_USERLEVEL] = $this->userlevel;
            $_SESSION[SESSION_REPOSITORY] = $this->repository;
          }
        }
      }
    }
    if (@$user['timezone']) {
      date_default_timezone_set($user['timezone']);
    } else {
      @date_default_timezone_set(TIMEZONE);
    }

    define('DATAPATH', ROOTPATH.'data'.'/'.($this->repository ? $this->repository : 'default').'/');
  }

  function authenticateBasic($users, $realm) {
    if (isset($_SERVER['PHP_AUTH_USER'])) {
      $username = $_SERVER['PHP_AUTH_USER'];
      $password = $_SERVER['PHP_AUTH_PW'];
    } else if (isset($_SERVER['HTTP_AUTHORIZATION']) && substr(strtolower($_SERVER['HTTP_AUTHORIZATION']),0,6) == 'basic ') {
      list($username, $password) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'],6)));    
    } else if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && substr(strtolower($_SERVER['REDIRECT_HTTP_AUTHORIZATION']),0,6) == 'basic ') {
      # if in .htaccess: RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
      list($username, $password) = explode(':', base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'],6)));    
    } else if (function_exists('apache_request_headers')) {
      $headers = apache_request_headers();
      if (isset($headers['Authorization']) && substr(strtolower($headers['Authorization']),0,6) == 'basic ') {
        list($username, $password) = explode(':', base64_decode(substr($headers['Authorization'],6)));    
      } 
    } 
    if (@$username && @$password) {
      foreach ($users as $user) {
        if (($username == $user['name'] || $username == $user['repository'].'\\'.$user['name']) && 
            md5($username.':'.$realm.':'.$password) == $user['a1']) return $user;
      }
    }
    return false;
  }
  
  function authenticateDigest($users, $realm, $secret) {
    if (isset($_SERVER['PHP_AUTH_DIGEST'])) {
      $digest = $_SERVER['PHP_AUTH_DIGEST'];
    } else if (isset($_SERVER['HTTP_AUTHORIZATION']) && substr(strtolower($_SERVER['HTTP_AUTHORIZATION']),0,7) == 'digest ') {
      $digest = substr($_SERVER['HTTP_AUTHORIZATION'],7);
    } else if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && substr(strtolower($_SERVER['REDIRECT_HTTP_AUTHORIZATION']),0,7) == 'digest ') {
      # if in .htaccess: RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
      $digest = substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'],7);
    } else if (function_exists('apache_request_headers')) {
      $headers = apache_request_headers();
      if (isset($headers['Authorization'])) {
        if (substr(strtolower($headers['Authorization']),0,7) == 'digest ') {
          $digest = substr($headers['Authorization'],7);
        }
      } 
    }
    if (!@$digest) return false;
    preg_match_all('@(username|nonce|uri|nc|cnonce|qop|response)=(?:\'([^\']+)\'|"([^"]+)"|([^\s,]+))@', $digest, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
      $data[$match[1]] = $match[2] ? $match[2] : ($match[3] ? $match[3] : $match[4]);
    }
    if (count($data) < 7) return false;
    # check uri
    $requestURI = $_SERVER['REQUEST_URI'];
    if (strpos($data['uri'],'?') === false && strpos($requestURI,'?') !== false) {
      $requestURI = substr($requestURI, 0, strpos($requestURI,'?'));
    }
    if ($data['uri'] != $requestURI) return false;
    # check nonce, which is $time."H".md5($time.':'.SECRET)
    if (!preg_match('@^(\d+)H(.*)$@', $data['nonce'], $match)) return false;
    if ((int) $match[1] + 24*3600 < time()) return false;
    if ($match[2] != md5($match[1].':'.$secret)) return false;
    # check response
    $username = $data['username'];
    $username = str_replace("\\\\","\\",$username); // workaround?
    foreach ($users as $user) {
      if ($username == $user['name']) {
        $A1 = $user['a1'];
      } else if ($username == $user['repository'].'\\'.$user['name']) {
        $A1 = $user['a1r'];
      } else continue;
      $A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
      $validResponse = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);
      if ($data['response'] != $validResponse) return false;
      return $user;
    }
    return false;
  }
  
  public function getUserName() {
    return $this->username;
  }
  
  public function getUserLevel() {
    return $this->userlevel;
  }

}

function getRootPath() {
  $pos = strrpos(dirname(__FILE__), DIRECTORY_SEPARATOR.'inc');
  return str_replace(DIRECTORY_SEPARATOR, '/', substr(dirname(__FILE__), 0, $pos+1));
}

function timestamp2string($ts) {
  return strftime(DATE_FORMAT, $ts);
}

function size2string($size) {
  $units = array('B', 'KB', 'MB', 'GB', 'TB');
  for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
  return round($size, 2).' '.$units[$i];
}

function sanitizeDir($dir) {
  return FileVCS::sanitizeDir($dir);
}

function sanitizeName($name) {
  return FileVCS::sanitizeName($name);
}

function getUserName() {
  global $authenticator;
  return @$authenticator->getUserName();
}

function checkUserLevel($level) {
  global $authenticator;
  $userlevel = @$authenticator->getUserLevel();
  if ($userlevel && $userlevel < $level) {
    header('HTTP/1.0 401 Unauthorized');
    echo 'You are not authorized';
    die();
  }
}

function isUserLevel($level) {
  global $authenticator;
  $userlevel = @$authenticator->getUserLevel();
  return $userlevel && $userlevel >= $level;
}

function isReadOnly() {
  global $authenticator;
  $userlevel = @$authenticator->getUserLevel();
  return $userlevel <= USERLEVEL_VIEW;
}
