<?php

class Filetype {
  
  static private $icons = array(
    /* backup */ 'bak'=>'backup',
    /* c */ 'c'=>'c', 'cc'=>'c',
    /* calc */ 'mrp'=>'calc',
    /* calendar */ 'ics'=>'calendar',
    /* code */ 'java'=>'code', 'js'=>'code', 'php'=>'code', 'psf'=>'code', 'xml'=>'code',
    /* database */ 'pdb'=>'database', 'sql'=>'database',
    /* document */ 'doc'=>'document', 'docx'=>'document', 'kwd'=>'document', 'odt'=>'document',
      'rtf'=>'document', 'sdw'=>'document',
    /* executable */ 'exe'=>'executable', 'jar'=>'executable',
    /* font */ 'afm'=>'font', 'pcf'=>'font', 'ttf'=>'font',
    /* h */ 'h'=>'h',
    /* html */ 'htm'=>'html', 'html'=>'html', 'xhtml'=>'html',
    /* image */ '3ds'=>'image', 'bmp'=>'image', 'gif'=>'image', 'jpeg'=>'image', 'jpg'=>'image', 
      'png'=>'image', 'ppm'=>'image', 'psd'=>'image', 'svg'=>'image', 'tif'=>'image',
      'tiff'=>'image', 'xcf'=>'image', 'xpm'=>'image',
    /* movie */ 'asf'=>'movie', 'avi'=>'movie', 'mov'=>'movie', 'mpeg'=>'movie', 'mpg'=>'movie',
      'wmv'=>'movie',
    /* package */ 'deb'=>'package', 'prc'=>'package', 'rpm'=>'package',
    /* packed */ 'ace'=>'packed', 'arj'=>'packed', 'bz2'=>'packed', 'cab'=>'packed', 'gz'=>'packed', 
      'lha'=>'packed', 'rar'=>'packed', 'tar'=>'packed', 'zip'=>'packed',
    /* pdf */ 'pdf'=>'pdf',
    /* presentation */ 'kpr'=>'presentation', 'odp'=>'presentation', 'ppt'=>'presentation', 
      'pptx'=>'presentation', 'sdd'=>'presentation',
    /* print */ 'eps'=>'print', 'ps'=>'print', 
    /* security */ 'pgp'=>'security',
    /* sound */ 'ac3'=>'sound', 'aiff'=>'sound', 'au'=>'sound', 'mid'=>'sound', 'midi'=>'sound', 
      'mp3'=>'sound', 'ogg'=>'sound', 'ra'=>'sound', 'rm'=>'sound', 'wav'=>'sound',
    /* table */ 'ksp'=>'table', 'ods'=>'table', 'sdc'=>'table', 'xls'=>'table', 'xlsx'=>'table',
      'csv'=>'table',
    /* text */ 'qif'=>'text', 'tex'=>'text', 'txt'=>'text'
  );
  /* css, dot, js */

  static private $mimetypes = array(
    "3ds"=>"application/x-3ds",
    "ac3"=>"audio/ac3",
    "ace"=>"application/x-ace",
    "afm"=>"application/octet-stream",
    "aiff"=>"audio/x-aiff",
    "arj"=>"application/arj",
    "asf"=>"video/x-ms-asf",
    "au"=>"audio/basic",
    "avi"=>"video/avi",
    "bmp"=>"image/bmp",
    "bz2"=>"application/x-bzip2",
    "c"=>"text/plain",
    "cab"=>"vnd.ms-cab-compressed",
    "cc"=>"text/plain",
    "css"=>"text/css",
    "csv"=>"text/csv",
    "deb"=>"application/x-deb",
    "doc"=>"application/msword", 
    "docx"=>"application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    "dot"=>"application/msword",
    "dwg"=>"model/vnd.dwg",
    "dxf"=>"image/vnd.dwg",
    "eps"=>"application/postscript",
    "exe"=>"application/octet-stream",
    "gif"=>"image/gif",
    "gz"=>"application/x-gzip",
    "h"=>"text/plain",
    "htm"=>"text/html",
    "html"=>"text/html",
    "ics"=>"text/calendar",
    "jar"=>"application/java-archive",
    "java"=>"text/plain",
    "jpeg"=>"image/jpeg", 
    "jpg"=>"image/jpeg",
    "kpr"=>"application/vnd.kde.kpresenter",
    "ksp"=>"application/vnd.kde.kspread",
    "kwd"=>"application/vnd.kde.kword",
    "js"=>"text/javascript",
    "lha"=>"application/lha",
    "log"=>"text/plain",
    "mid"=>"audio/midi",
    "midi"=>"audio/midi",
    "mov"=>"video/quicktime",
    "mp3"=>"audio/mpeg3",
    "mpeg"=>"video/mpeg", 
    "mpg"=>"video/mpeg",
    "odp"=>"application/vnd.oasis.opendocument.presentation",
    "ods"=>"application/vnd.oasis.opendocument.spreadsheet",
    "odt"=>"application/vnd.oasis.opendocument.text",
    "ogg"=>"audio/ogg",
    "pcf"=>"application/x-font-pcf",
    "pdb"=>"application/vnd.palm",
    "pdf"=>"application/pdf",
    "php"=>"text/php",
    "png"=>"image/png",
    "ppm"=>"image/x-portable-pixmap",
    "ppt"=>"application/mspowerpoint",
    "pptx"=>"application/vnd.openxmlformats-officedocument.presentationml.presentation",
    "prc"=>"application/vnd.palm",
    "ps"=>"application/postscript",
    "psd"=>"image/vnd.adobe.photoshop",
    "rar"=>"application/x-rar-compressed",
    "rm"=>"audio/x-pn-realaudio",
    "rpm"=>"audio/x-pn-realaudio-plugin",
    "rtf"=>"text/rtf",
    "sdc"=>"application/vnd.stardivision.calc",
    "sdd"=>"application/vnd.stardivision.impress",
    "sdw"=>"application/vnd.stardivision.writer",
    "sql"=>"text/plain",
    "svg"=>"image/svg+xml",
    "tar"=>"application/x-tar",
    "tex"=>"application/x-tex",
    "tif"=>"image/tiff",
    "tiff"=>"image/tiff",
    "ttf"=>"application/octet-stream",
    "txt"=>"text/plain",
    "wav"=>"audio/wav",
    "wmv"=>"video/x-ms-wmv",
    "xcf"=>"image/xcf",
    "xls"=>"application/msexcel",
    "xlsx"=>"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    "xml"=>"text/xml",
    "xpm"=>"image/x-xbitmap",
    "zip"=>"application/zip"
  );

  static function getIcons() {
    return self::$icons;
  }

  static function getIcon($ext) {
    return isset(self::$icons[$ext]) ? self::$icons[$ext] : 'unknown';
  }

  static function getMimetypes() {
    return self::$mimetypes;
  }

  static function getMimetype($ext) {
    return isset(self::$mimetypes[$ext]) ? self::$mimetypes[$ext] : 'application/octet-stream';
  } 
  
}
