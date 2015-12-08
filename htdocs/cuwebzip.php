<?php
/*

Copyright (c) 2015 Jörg Wrase - cusp.de

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

 */

session_start();

$counter = 1;

if (isset($_SESSION['counter'])) {
    $counter = $_SESSION['counter'];
    $counter++;
}
$_SESSION['counter'] = $counter;

define('COUNTER', $counter);

define('DEFAULT_DB_FILENAME_FOR_BACKUP', 'cu_db_backup###counter###.sql');
define('DEFAULT_FS_FILENAME_FOR_BACKUP', 'cu_fs_backup###counter###.zip');

define('DEFAULT_DB_SERVER', 'localhost');
define('DEFAULT_DB_USER', 'root');
define('DEFAULT_DB_PASSWORD', '');
define('DEFAULT_DB_NAMES', '--all-databases');
define('DB_EXEC_TEMPLATE', 'mysqldump -h ###dbServer### -u ###dbUser### ###dbPassword### ###dbNames### > ###dbFileName###');


/* For Debugging */

define('DONT_EXEC', false);

$outputMessages = array();

/**
 * Class ExecInfo
 */

/** @noinspection PhpIllegalPsrClassPathInspection */
class ExecInfo {

    protected static $execStr = '';
    protected static $return = '';
    protected static $output = array();
    protected static $returnValue = '';

    /**
     * @return string
     */
    public static function getExecStr() {
        return self::$execStr;
    }

    /**
     * @param string $execStr
     */
    public static function setExecStr($execStr) {
        self::$execStr = (string)$execStr;
    }

    /**
     * @return string
     */
    public static function getReturn() {
        return self::$return;
    }

    /**
     * @param string $return
     */
    public static function setReturn($return) {
        self::$return = (string)$return;
    }

    /**
     * @return array
     */
    public static function getOutput() {
        return self::$output;
    }

    /**
     * @param array $output
     */
    public static function setOutput(array $output) {
        self::$output = $output;
    }

    /**
     * @return string
     */
    public static function getReturnValue() {
        return self::$returnValue;
    }

    /**
     * @param string $returnValue
     */
    public static function setReturnValue($returnValue) {
        self::$returnValue = (string)$returnValue;
    }

    public static function getVisibleArray() {
        $infoArray['execStr']     = self::$execStr;
        $infoArray['return']      = self::$return;
        $infoArray['returnValue'] = self::$returnValue;
        $infoArray['output']      = implode("\n", self::$output);

        return $infoArray;
    }

}


/**
 * @param string $getParameterName
 *
 * @return null|string
 */
function testValue($getParameterName) {
    $parameter = (isset($_GET[$getParameterName]) && $_GET[$getParameterName] !== '') ?
        (string)$_GET[$getParameterName] : null;

    return $parameter;
}

/**
 * @param $fileName
 *
 * @return string
 */
function fileNameWithCounter($fileName) {
    global $counter;

    $fileName = trim($fileName);
    $fileName = str_replace('###counter###', $counter, $fileName);

    return $fileName;

}

/**
 * @param $execStr
 *
 * @return array
 */
function cuExec($execStr) {

    if (DONT_EXEC !== true) {
        $return = exec($execStr, $output, $returnValue);
    }


    $output = (isset($output) && is_array($output)) ? $output : array();

    $execStr     = isset($execStr) ? $execStr : null;
    $return      = isset($return) ? $return : null;
    $returnValue = isset($returnValue) ? $returnValue : null;

    ExecInfo::setExecStr($execStr);
    ExecInfo::setOutput($output);
    ExecInfo::setReturn($return);
    ExecInfo::setReturnValue($returnValue);

}

/**
 * @param $fileName
 * @param $followSymlinks
 *
 * @return mixed
 */
function makeZip($fileName, $followSymlinks = false) {
    global $outputMessages;

    $execStr = "zip --symlinks -r $fileName .";

    if ($followSymlinks) {
        $execStr = "zip -r $fileName .";
    }

    cuExec($execStr);

    $outputMessages['FS'] = ExecInfo::getVisibleArray();

    define('FS_FILENAME_FOR_BACKUP', $fileName);

}


/**
 * @param $dbServer
 * @param $dbUser
 * @param $dbPassword
 */
function makeDBBackup($dbServer, $dbUser, $dbPassword, $dbNames, $dbFileName) {
    global $outputMessages;

    // mysqldump -h ###dbServer### -u ###dbPassword### ###dbUser### ###dbNames### > ###dbFileName###
    $execStr = DB_EXEC_TEMPLATE;

    $dbPassword = trim($dbPassword);

    if ($dbPassword !== '--all-databases' && $dbPassword !== '') {
        $dbPassword = '-p' . $dbPassword;
    }

    $replacer = array(
        '###dbServer###'   => $dbServer,
        '###dbPassword###' => $dbPassword,
        '###dbUser###'     => $dbUser,
        '###dbNames###'    => $dbNames,
        '###dbFileName###' => $dbFileName,
    );

    $execStr = str_replace(array_keys($replacer), array_values($replacer), $execStr);

    define('DB_FILENAME_FOR_BACKUP', $dbFileName);

    cuExec($execStr);

    $outputMessages['DB'] = ExecInfo::getVisibleArray();

}

$action = testValue('action');

if ($action === 'phpinfo') {
    phpinfo();
    exit;
}

$dbServername = testValue('inputDBServername');
$dbUsername   = testValue('inputDBUsername');
$dbPassword   = testValue('inputDBPassword');
$dbNames      = testValue('inputDBNames');
$dbFile       = testValue('inputDBfileNameForBackup');
$fsFile       = testValue('inputFSfileNameForBackup');

$dbDoIt = testValue('DBDoIt');
$fsDoIt = testValue('FSDoIt');

if ($fsDoIt) {

    $fsFileNameForBackup = $fsFile ?: DEFAULT_FS_FILENAME_FOR_BACKUP;

    $fsFileNameForBackup = fileNameWithCounter($fsFileNameForBackup);

    $followSymlinks = testValue('inputFSfollowSymlinks');

    makeZip($fsFileNameForBackup, $followSymlinks);
}

if ($dbServername && $dbUsername && $dbDoIt) {

    $dbFile = $dbFile ?: DEFAULT_DB_FILENAME_FOR_BACKUP;

    $dbFile = fileNameWithCounter($dbFile);

    makeDBBackup($dbServername, $dbUsername, $dbPassword, $dbNames, $dbFile);

}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>cuWebPageBackup</title>

    <!-- Bootstrap -->
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css"
          crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css"
          crossorigin="anonymous">

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" crossorigin="anonymous"></script>

    <!-- Bootbox -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/4.4.0/bootbox.min.js"></script>

    <style>
        body {
            padding-top : 50px;
        }
    </style>

    <script type="text/javascript">

    </script>

</head>
<body>

<nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar"
                    aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="<?php echo $_SERVER['SCRIPT_NAME']; ?>">Backup your Webpage</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav navbar-right">
                <li><a href="<?php echo $_SERVER['SCRIPT_NAME']; ?>?action=phpinfo" target="_blank">Show PHP Info</a>
                </li>
                <li><a href="http://www.cusp.de">Service by cusp.de - Jörg Wrase</a></li>
            </ul>
        </div>
    </div>
</nav>

<form class="form-horizontal" enctype="application/x-www-form-urlencoded"
      action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
    <div class="container-fluid">

        <div class="row">

            <div class="col-sm-14 col-lg-12">
                <?php if ($dbDoIt || $fsDoIt): ?>
                    <div class="alert alert-success" role="alert">
                        <?php if ($dbDoIt): ?>
                            <a href="<?php echo DB_FILENAME_FOR_BACKUP; ?>">Created BackupFile from
                                DB: <?php echo DB_FILENAME_FOR_BACKUP; ?></a><br>
                        <?php endif; ?>
                        <?php if ($fsDoIt): ?>
                            <a href="<?php echo FS_FILENAME_FOR_BACKUP; ?>">Created BackupFile from
                                FS: <?php echo FS_FILENAME_FOR_BACKUP; ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (count($outputMessages) > 0): ?>
                    <div class="jumbotron text-info">

                        <h3>Log-Data <span class="small">(<?php echo $counter; ?>)</span></h3>

                        <pre>
                            <?php print_r($outputMessages); ?>
                        </pre>
                    </div>
                <?php endif; ?>

                <div class="jumbotron">

                    <h2>Database Data</h2>

                    <div class="form-group">
                        <div class="col-sm-offset-4 col-sm-8">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="DBDoIt" checked="checked"> Make Database Backup
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputDBServername" class="col-sm-4 control-label">DB Servername</label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <div class="input-group-addon"><span
                                        class="text-info glyphicon glyphicon-exclamation-sign"></span></div>
                                <input type="text" class="form-control" id="inputDBServername" name="inputDBServername"
                                       placeholder="DB Servername" value="<?php echo DEFAULT_DB_SERVER; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputDBUsername" class="col-sm-4 control-label">DB Username</label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <div class="input-group-addon"><span
                                        class="text-info glyphicon glyphicon-exclamation-sign"></span></div>

                                <input type="text" class="form-control" id="inputDBUsername" name="inputDBUsername"
                                       placeholder="DB Username" value="<?php echo DEFAULT_DB_USER; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputDBPassword" class="col-sm-4 control-label">DB Password</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="inputDBPassword" name="inputDBPassword"
                                   placeholder="DB Password">
                        </div>
                    </div>


                    <div class="form-group">
                        <label for="inputDBNames" class="col-sm-4 control-label">DB Names</label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <div class="input-group-addon"><span
                                        class="text-info glyphicon glyphicon-exclamation-sign"></span></div>
                                <!--                            <div class="small pull-right"><span class="glyphicon glyphicon-question-sign"></span></div>-->
                                <input type="text" class="form-control" id="inputDBNames" name="inputDBNames"
                                       placeholder="DB Names" value="<?php echo DEFAULT_DB_NAMES; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputDBfileNameForBackup" class="col-sm-4 control-label">Filename for
                            Database-Backup</label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <div class="input-group-addon"><span
                                        class="text-info glyphicon glyphicon-exclamation-sign"></span></div>

                                <input type="text" class="form-control" id="inputDBfileNameForBackup"
                                       name="inputDBfileNameForBackup"
                                       value="<?php echo DEFAULT_DB_FILENAME_FOR_BACKUP; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputDBaddOptions" class="col-sm-4 control-label">Add Options</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="inputDBaddOptions" name="inputDBaddOptions">
                        </div>
                    </div>


                </div>

                <div class="jumbotron">

                    <h2>Filesystem</h2>

                    <div class="form-group">
                        <div class="col-sm-offset-4 col-sm-8">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="FSDoIt" checked="checked"> Make Filesystem Backup
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-4 col-sm-8">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="inputFSfollowSymlinks"> Follow Symlinks
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputFSfileNameForBackup" class="col-sm-4 control-label">Filename for
                            Filesystem-Backup</label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <div class="input-group-addon"><span
                                        class="text-info glyphicon glyphicon-exclamation-sign"></span></div>

                                <input type="text" class="form-control" id="inputFSfileNameForBackup"
                                       value="<?php echo DEFAULT_FS_FILENAME_FOR_BACKUP; ?>">
                            </div>
                        </div>
                    </div>

                </div>


                <div class="form-group">
                    <div class="col-sm-offset-4 col-sm-8">
                        <button type="submit" class="btn btn-default">Try to DoIt</button>
                    </div>
                </div>

            </div>

        </div>

    </div>

</form>

</body>
</html>