<?php
/*
 * Copyright by Jörg Wrase - www.Computer-Und-Sound.de
 * Date: 04.12.2015
 * Time: 21:52
 * 
 * Created by IntelliJ IDEA
 *
 */

define('DEFAULT_DB_FILENAME_FOR_BACKUP', 'cu_db_backup.sql');
define('DEFAULT_FS_FILENAME_FOR_BACKUP', 'cu_fs_backup.sql');

define('DEFAULT_DB_SERVER', 'localhost');
define('DEFAULT_DB_USER', 'root');
define('DEFAULT_DB_PASSWORD', '');

$outputMessages = array();

/**
 * Class ExecInfo
 */
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
 * @param $execStr
 *
 * @return array
 */
function cuExec($execStr) {


//    $execStr = escapeshellarg($execStr);

    $return = exec($execStr, $output, $returnValue);

    $output = is_array($output) ? $output : array();

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

}


/**
 * @param $dbServer
 * @param $dbUser
 * @param $dbPassword
 */
function makeDBBackup($dbServer, $dbUser, $dbPassword, $dbFileName) {
    global $outputMessages;

    $execStr = "mysqldump -h $dbServer -u $dbUser  --all-databases > $dbFileName";

    if ($dbPassword) {
        $execStr = "mysqldump.exe -h $dbServer -u $dbUser -p$dbPassword --all-databases > $dbFileName";
    }

    define('DB_FILENAME_FOR_BACKUP', $dbFileName);

    cuExec($execStr);

    $outputMessages['DB'] = ExecInfo::getVisibleArray();

}

$dbServername = testValue('inputDBServername');
$dbUsername   = testValue('inputDBUsername');
$dbPassword   = testValue('inputDBPassword');

$dbDoIt       = testValue('DBDoIt');
$fsDoIt       = testValue('FSDoIt');

if ($dbServername && $dbUsername && $dbDoIt) {

    $dbFileNameForBackup = testValue('inputDBfileNameForBackup') ?: DEFAULT_DB_FILENAME_FOR_BACKUP;

    makeDBBackup($dbServername, $dbUsername, $dbPassword, $dbFileNameForBackup);

}

if ($fsDoIt) {

    $fsFileNameForBackup = testValue('inputFSfileNameForBackup') ?: DEFAULT_FS_FILENAME_FOR_BACKUP;

    $followSymlinks = testValue('inputFSfollowSymlinks');

    makeZip($fsFileNameForBackup, $followSymlinks);
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hostfile editieren</title>

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


    </style>

    <script type="text/javascript">

    </script>

</head>
<body>

<form class="form-horizontal" enctype="application/x-www-form-urlencoded">

    <div class="container">

        <div class="row">

            <div class="col-sm-14 col-lg-12">

                <h1>Backup your Webpage</h1>

                <?php if (empty($outputMessages) === false): ?>
                    <div class="jumbotron text-info">

                        <h3>Log-Data</h3>

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
                            <input type="text" class="form-control" id="inputDBServername" name="inputDBServername"
                                   placeholder="DB Servername" value="<?php echo DEFAULT_DB_SERVER; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputDBUsername" class="col-sm-4 control-label">DB Username</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="inputDBUsername" name="inputDBUsername"
                                   placeholder="DB Username" value="<?php echo DEFAULT_DB_USER; ?>">
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
                        <label for="inputDBfileNameForBackup" class="col-sm-4 control-label">Filename for
                            Database-Backup</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="inputDBfileNameForBackup"
                                   value="curBackupServer.sql">
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
                            <input type="text" class="form-control" id="inputFSfileNameForBackup"
                                   value="curBackupServer.zip">
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