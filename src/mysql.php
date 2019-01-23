<?php
namespace RestApi;

class MySQL
{
    public $handler;
    public $host;
    public $username;
    public $password;
    public $database;

    public function __construct($mysql_hostname,$mysql_username,$mysql_password,$mysql_database)
    {

        $this->host     = $mysql_hostname;
        $this->username = $mysql_username;
        $this->password = $mysql_password;
        $this->database = $mysql_database;

        $this->handler  = mysqli_connect($this->host, $this->username, $this->password,$this->database);
        if (!$this->handler) {
            die('{"success": false, "message": "' . $this->getErrorMsg() . '"}');
        }

        mysqli_query($this->handler,"set names utf8");
    }

    public function __destruct()
    {
        mysqli_close($this->handler);
    }

    public function rs($fields, $table, $params)
    {
        $start     = isset($params["start"]) ? $params["start"] : 0;
        $limit     = isset($params["limit"]) ? $params["limit"] : 100;
        $sort      = isset($params["sort"]) ? $params["sort"] : NULL;
        $dir       = isset($params["dir"]) ? $params["dir"] : NULL;
        $ordercond = is_null($sort) || is_null($dir) ? null : "order by {$sort} {$dir}";
        $limitcond = is_null($limit) || is_null($start) ? null : "limit {$start}, {$limit}";
        $cond      = '';

        unset($params["start"]);
        unset($params["limit"]);
        unset($params["sort"]);
        unset($params["dir"]);
        unset($params["_dc"]);
        unset($params["page"]);
        unset($params["group"]);
        if ($params) {
            $cond      = [];
            foreach ($params as $key => $value) {
                $value  = mysqli_real_escape_string($this->handler,$value);
                $cond[] = "$key = '{$value}'";
            }
            $cond = implode("AND ", $cond);
            $cond = is_null($cond) ? null : " WHERE " . $cond;
        }

        $sql    = "SELECT count(id) FROM " . $table . $cond;
        $result = mysqli_query($this->handler,$sql);
        if (!$result) {
            die('{"success": false, "message": "' . $this->getErrorMsg() . '"}');
        }
        $row          = mysqli_fetch_row($result);
        $ret["total"] = $row[0];
        $ret["data"]  = array();

        if ($ret["total"] > 0 && $start < $ret["total"]) {
            $sql = "select {$fields} from {$table} {$cond} {$ordercond} {$limitcond}";

            $result = mysqli_query($this->handler,$sql);
            if (!$result) {
                die('{"success": false, "message": "' . $this->getErrorMsg() . '"}');
            }
            $fields_num = mysqli_field_count($this->handler);

            while ($row = mysqli_fetch_row($result)) {
                $record = array();
                for ($i = 0; $i < $fields_num; $i++) {

                    $field = mysqli_fetch_field_direct( $result, $i);
                    $type = $field->type;
                    if ("int" == $type) {
                        $record[$field->name] = intval($row[$i]);
                    } else if ("float" == $type) {
                        $record[$field->name] = floatval($row[$i]);
                    } else if ("double" == $type) {
                        $record[$field->name] = doubleval($row[$i]);
                    } else {
                        $record[$field->name] = $row[$i];
                    }
                }
                array_push($ret["data"], $record);
            }
        }
        return $ret;
    }

    public function insert($table, $fields)
    {
        unset($fields["id"]);
        $handler = $this->handler;
        $values  = array_values($fields);
        array_walk($values, function(&$string) use ($handler) {
            $string = mysqli_real_escape_string($handler, $string);
        });
        $sql    = sprintf('INSERT INTO %s (%s) VALUES ("%s")', $table, implode(',', array_keys($fields)), implode('","', $values));
        $result = mysqli_query($this->handler,$sql);
        if (!$result) {
            die('{"success": false, "message": "' . $this->getErrorMsg() . '"}');
        }
        return mysql_insert_id();
    }

    public function update($table, $id, $fields)
    {
        foreach ($fields as $key => $value) {
            $value     = mysqli_real_escape_string($this->handler,$value);
            $updates[] = "$key = '{$value}'";
        }
        $implode = implode(", ", $updates);
        $sql     = "UPDATE $table SET $implode WHERE id = '$id'";
        $result  = mysqli_query($this->handler,$sql);
        if (!$result) {
            die('{"success": false, "message": "' . $this->getErrorMsg() . '"}');
        }
    }

    public function destroy($table, $id)
    {
        $sql    = "DELETE FROM $table WHERE id = $id";
        $result = mysqli_query($this->handler,$sql);
        if (!$result) {
            die('{"success": false, "message": "' . $this->getErrorMsg() . '"}');
        }
    }

    public function getErrorMsg()
    {
        $errno = mysqli_errno($this->handler);
        switch ($errno) {
            case 1062:
                return "Bu kayıt zaten ekli!";
                break;
            default:
                return mysqli_errno($this->handler) . " " . mysqli_error($this->handler);
        }
    }
}
