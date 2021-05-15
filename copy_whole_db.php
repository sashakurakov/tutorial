<?php
$DBIUser = 'someuser';
$DBIPass = 'thepassword';

$NewUser = 'someloser';
$NewPass = 'thepassword';

$oldServer = 'my crappy old mysql server domain';
$newServer = 'localhost';    

if ($argv[0] > " ")
{
    $dbname = $argv[1];
    echo "Starting copy of the $argv[1] database.\n";
    $dbpre = mysql_connect($oldServer, $DBIUser, $DBIPass);
    mysql_select_db($dbname, $dbpre);
    $sql = "SHOW TABLES FROM $dbname";
    echo $sql."\n";
    $result = mysql_query($sql);

    if (!$result)
    {
        echo "DB Error, could not list tables\n";
        echo 'MySQL Error: ' . mysql_error();
        exit;
    }

    $dbtbl = mysql_connect($oldServer, $DBIUser, $DBIPass);
    mysql_select_db($dbname, $dbpre);
    $dbnew = mysql_connect($newServer, $NewUser, $NewPass);
    mysql_select_db("mysql", $dbnew);

    $res2 = mysql_query("CREATE DATABASE IF NOT EXISTS ".$dbname,$dbnew);
    if (!$res2)
    {
            echo "DB Error, could not create database\n";
            echo 'MySQL Error: ' . mysql_error();
            exit;
    }
    mysql_select_db($dbname, $dbnew);


    if($result === FALSE)
    {
        die(mysql_error());
    }

    $f = fopen($dbname.'.log', 'w');
    fwrite($f, "Copy all tables in database $dbname on server $oldServer to new database on server $newServer.\n\n");
    while ($row = mysql_fetch_row($result))
    {
        echo "Table: {$row[0]}\n";
        fwrite($f, "Table ".$row[0]."\n");
        $tableinfo = mysql_fetch_array(mysql_query("SHOW CREATE TABLE $row[0]  ",$dbtbl));
        $createsyntax = "CREATE TABLE IF NOT EXISTS ";
        $createsyntax .= substr($tableinfo[1], 13);

        mysql_query(" $createsyntax ",$dbnew);

        $res = mysql_query("SELECT * FROM $row[0]  ",$dbpre); // select all rows
        $oldcnt = mysql_num_rows($res);
        echo "Count: ".$oldcnt." - ";

        $errors = 0;
        while ($roz = mysql_fetch_array($res, MYSQL_ASSOC) )
        {
          $query =  "INSERT INTO $dbname.$row[0] (".implode(", ",array_keys($roz)).") VALUES (";
          $cnt = 0;
          foreach (array_values($roz) as $value)
          {
            if ($cnt == 0)
            {
              $cnt++;
            } else
            {
              $query .= ",";
            }
            $query .= "'";
            $query .= mysql_real_escape_string($value);
            $query .= "'";

          }
          $query .= ")";

          $look = mysql_query($query,$dbnew);
          if ($look === false)
          {
            // write insert to log on error
            $errors = $errors + 1;
            fwrite($f, mysql_error()." - ".$query."\n");
          }

        }
        $sql = "select count(*) as cnt from $dbname.$row[0] ";
        $res = mysql_query($sql, $dbnew);
        $roz = mysql_fetch_array($res);
        echo $roz['cnt']." - Errors: ".$errors."\n";
        fwrite($f, "Old Record Count: ".$oldcnt." - New Record Count: ".$roz['cnt']." - Errors: ".$errors."\n");
        fwrite($f,"End table copy for table $row[0].\n\n");

    }
    fclose($f);
}
else
{
    var_dump($argv);
}
?>