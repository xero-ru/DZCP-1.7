<?php
/**
 * DZCP - deV!L`z ClanPortal 1.7.0
 * http://www.dzcp.de
 * Database Connect & Functions
 */

//#######################################
//OLD Code Adapter
//#######################################

$mysqli = null;
if($db['host'] != '' && $db['user'] != '' && $db['pass'] != '' && $db['db'] != '' && !$thumbgen) {
    $db_host = $db['host'];
    $mysqli = new mysqli($db_host,$db['user'],$db['pass'],$db['db']);
    if ($mysqli->connect_error) { die("<b>Fehler beim Zugriff auf die Datenbank!"); }
}

//MySQLi-Funktionen
function _rows($rows) {
    global $mysqli;
    if ($mysqli instanceof mysqli)
        return array_key_exists('_stmt_rows_', $rows) ? $rows['_stmt_rows_'] : $rows->num_rows;

    return false;
}

function _fetch($fetch) {
    global $mysqli;
    if ($mysqli instanceof mysqli)
        return array_key_exists('_stmt_rows_', $fetch) ? $fetch[0] : $fetch->fetch_assoc();

    return false;
}

function _real_escape_string($string='') {
    global $mysqli;
    if ($mysqli instanceof mysqli)
        return !empty($string) ? $mysqli->real_escape_string($string) : $string;

    return false;
}

function _insert_id() {
    global $mysqli;
    if ($mysqli instanceof mysqli)
        return $mysqli->insert_id;

    return false;
}

function db($query='',$rows=false,$fetch=false) {
    global $mysqli,$clanname,$updater;
    if ($mysqli instanceof mysqli) {
        if(debug_all_sql_querys) DebugConsole::wire_log('debug', 9, 'SQL_Query', $query);
        if($updater) { $qry = $mysqli->query($query); } else {
            if(!$qry = $mysqli->query($query)) {
                $message = DebugConsole::sql_error_Exception($query,$query);
                include_once(basePath."/inc/lang/languages/english.php");
                $message = 'SQL-Debug:<p>'.$message;
                die(show('<b>Upps...</b><br /><br />Entschuldige bitte! Das h&auml;tte nicht passieren d&uuml;rfen. Wir k&uuml;mmern uns so schnell wie m&ouml;glich darum.<br><br>'.$clanname.'<br><br>'.(view_error_reporting ? nl2br($message).'<br><br>' : '').'[lang_back]'));
            }
        }

        if ($rows && !$fetch)
            return _rows($qry);
        else if($fetch && $rows)
            return $qry->fetch_array(MYSQLI_NUM);
        else if($fetch && !$rows)
            return _fetch($qry);

        return $qry;
    }

    return false;
}

/**
 *  i     corresponding variable has type integer
 *  d     corresponding variable has type double
 *  s     corresponding variable has type string
 *  b     corresponding variable is a blob and will be sent in packets
 */
function db_stmt($query,$params=array('si', 'hallo', '4'),$rows=false,$fetch=false) {
    global $prefix,$mysqli;
    if ($mysqli instanceof mysqli) {
        if(!$statement = $mysqli->prepare($query)) die('<b>MySQL-Query failed:</b><br /><br /><ul>'.
                '<li><b>ErrorNo</b> = '.!empty($prefix) ? str_replace($prefix,'',$mysqli->connect_errno) : $mysqli->connect_errno.
                '<li><b>Error</b>   = '.!empty($prefix) ? str_replace($prefix,'',$mysqli->connect_error) : $mysqli->connect_error.
                '<li><b>Query</b>   = '.!empty($prefix) ? str_replace($prefix,'',$query).'</ul>' : $query);

        call_user_func_array(array($statement, 'bind_param'), refValues($params));
        if(!$statement->execute()) die('<b>MySQL-Query failed:</b><br /><br /><ul>'.
                '<li><b>ErrorNo</b> = '.!empty($prefix) ? str_replace($prefix,'',$mysqli->connect_errno) : $mysqli->connect_errno.
                '<li><b>Error</b>   = '.!empty($prefix) ? str_replace($prefix,'',$mysqli->connect_error) : $mysqli->connect_error.
                '<li><b>Query</b>   = '.!empty($prefix) ? str_replace($prefix,'',$query).'</ul>' : $query);

        $meta = mysqli_stmt_result_metadata($statement);
        if(!$meta || empty($meta)) { mysqli_stmt_close($statement); return; }
        $row = array(); $parameters = array(); $results = array();
        while ( $field = mysqli_fetch_field($meta) ) {
            $parameters[] = &$row[$field->name];
        }

        mysqli_stmt_store_result($statement);
        $results['_stmt_rows_'] = mysqli_stmt_num_rows($statement);
        call_user_func_array(array($statement, 'bind_result'), refValues($parameters));

        while ( mysqli_stmt_fetch($statement) ) {
            $x = array();
            foreach( $row as $key => $val ) {
                $x[$key] = $val;
            }

            $results[] = $x;
        }

        if ($rows && !$fetch)
            return _rows($results);
        else if($fetch && !$rows)
            return _fetch($results);

        return $results;
    }
}

function refValues($arr) {
    if (strnatcmp(phpversion(),'5.3') >= 0) {
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = &$arr[$key];

        return $refs;
    }

    return $arr;
}

//Auto Update Detect
if(file_exists(basePath."/_installer/index.php") && !view_error_reporting &&
file_exists(basePath."/inc/mysql.php") && !$installation && !$thumbgen) {
    $sqlqry = db('SHOW TABLE STATUS'); $table_data = array();
    while($table = _fetch($sqlqry))
    { $table_data[$table['Name']] = true; }

    if(!array_key_exists($db['autologin'],$table_data) && !$installer)
        $global_index ? header('Location: _installer/update.php') :
        header('Location: ../_installer/update.php');
    unset($user_check);
}

function sql_backup() {
    global $mysqli,$db;
    if ($mysqli instanceof mysqli) {
        $backup_table_data = array();

        //Table Drop
        $sqlqry = db('SHOW TABLE STATUS');
        while($table = _fetch($sqlqry))
        { $backup_table_data[$table['Name']]['drop'] = 'DROP TABLE IF EXISTS `'.$table['Name'].'`;'; }
        unset($table);

        //Table Create
        foreach($backup_table_data as $table => $null) {
            unset($null);
            $sqlqry = db('SHOW CREATE TABLE '.$table.';');
            while($table = _fetch($sqlqry))
            { $backup_table_data[$table['Table']]['create'] = $table['Create Table'].';'; }
        }
        unset($table);

        //Insert Create
        foreach($backup_table_data as $table => $null) {
            unset($null); $backup = '';
            $sqlqry = db('SELECT * FROM '.$table.' ;');
            while($dt = _fetch($sqlqry)) {
                if(!empty($dt)) {
                    $backup_data = '';
                    foreach ($dt as $key => $var)
                    { $backup_data .= "`".$key."` = '".((string)(str_replace("'", "`", $var)))."',"; }

                    $backup .= "INSERT INTO `".$table."` SET ".substr($backup_data, 0, -1).";\r\n";
                    unset($backup_data);
                }
            }

            $backup_table_data[$table]['insert'] = $backup;
            unset($backup);
        }
        unset($table);

        $sql_backup =  "-- -------------------------------------------------------------------\r\n";
        $sql_backup .= "-- Datenbank Backup von deV!L`z Clanportal v."._version."\r\n";
        $sql_backup .= "-- Build: "._release." * "._build."\r\n";
        $sql_backup .= "-- Host: ".$db['host']."\r\n";
        $sql_backup .= "-- Erstellt am: ".date("d.m.Y")." um ".date("H:i")."\r\n";
        $sql_backup .= "-- MySQL-Version: ".mysqli_get_server_info($mysql)."\r\n";
        $sql_backup .= "-- PHP Version: ".phpversion()."\r\n";
        $sql_backup .= "-- -------------------------------------------------------------------\r\n\r\n";
        $sql_backup .= "--\r\n-- Datenbank: `".$db['db']."`\r\n--\n\n";
        $sql_backup .= "-- -------------------------------------------------------------------\r\n";
        foreach($backup_table_data as $table => $data) {
            $sql_backup .= "\r\n--\r\n-- Tabellenstruktur: `".$table."`\r\n--\r\n\r\n";
            $sql_backup .= $data['drop']."\r\n";
            $sql_backup .= $data['create']."\r\n";

            if(!empty($data['insert'])) {
                $sql_backup .= "\r\n--\r\n-- Datenstruktur: `".$table."`\r\n--\r\n\r\n";
                $sql_backup .= $data['insert']."\r\n";
            }
        }

        unset($data);
        return $sql_backup;
    }

    return false;
}