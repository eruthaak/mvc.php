<?php

  require 'interface.Query.php';
  require 'enum.FETCH.php'; 

  /**
   * 
   */
  class Database extends \PDO implements Query {
    private PDO $db;
    private PDOStatement $statement;
    private string $query;

    public function __construct(string $credential, string $username, string $password = NULL, bool $isAppDebug) {
      $this->db = new parent($credential, $username, $password);
      if( !$isAppDebug ) $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
      return $this;
    }

    /**
     * Select row(s) from database table.
     * 
     * @param int $fetchMode : You must specify first one row or more than first one row.
     * @param string $table : You must specify database table.
     * @param string ...$columns : You should specify which columns will be chosen when it's run, you must give columns as an array, if you give any word as key to given array, your key becomes as an alias on SQL.
     * 
     * @return array : Results
     * @author Beresyus
     */
    public function select(string $table, int $fetchMode = FETCH::GET, string ...$columns) : array {
      if( empty($columns) ) {
        $this->setQuery("SELECT * FROM `$table`");
        $this->statement = $this->db->prepare($this->query);
        $this->statement->execute();
      } else {
        $query = "SELECT ";
        foreach($columns as $alias => $column_name) {
          if( array_key_last($columns) === $alias ) {
            if( is_nan($alias) ) {
              $query .= "`$column_name` AS `$alias`";
            } else {
              $query .= "`$column_name`";
            }
          } else {
            if( is_nan($alias) ) {
              $query .= "`$column_name` AS `$alias`, ";
            } else {
              $query .= "`$column_name`, ";
            }
          }
        }
        $query .= " FROM `$table`";
        $this->setQuery($query);
        $this->statement = $this->db->prepare($this->query);
        $this->statement->execute();
      }

      if ( $fetchMode === 0 )  {
        $datas = $this->statement->fetch(PDO::FETCH_ASSOC);
      } else {
        $datas = $this->statement->fetchAll(PDO::FETCH_ASSOC);
      }

      return $datas;
    }

    /**
     * Insert data(s) to database table.
     * 
     * @param string $table : You must specify the table which you want to insert data.
     * @param array $datas : You must specify datas as value and columns as key of an array.
     * 
     * @return int|array : Last insert id or error.
     * @author Beresyus
     */
    public function insert(string $table, array $datas) : int|array {
      $query = "INSERT INTO `$table` (";

      $columns = array_keys($datas);
      $lastColumn = array_key_last($datas);

      foreach($columns as $column) {
        if( $column == $lastColumn ) $query .= "`$column`) VALUES (";
        else $query .= "`$column`, ";
      }

      $lastData = $datas[$lastColumn];
      foreach($datas as $data) {
        switch( gettype($data) ) {
          case 'string':
              if( $data == $lastData ) $query .= "'$data')";
              else $query .= "'$data', ";
            break;
          case 'NULL':
            if($data == $lastData) $query .= "NULL)";
            else $query .= "NULL, ";
            break;
          default:
            if( $data == $lastData ) $query .= "$data)";
            else $query .= "$data, ";
            break;
        }
      }

      $this->setQuery($query);
      // echo $this->query; exit;
      $this->statement = $this->db->prepare($this->query);
      $result = $this->statement->execute();


      if( $result === false ) {
        $dbErrorInfo = $this->statement->errorInfo();
        $dbErrorCode = $dbErrorInfo[0];

        if( $dbErrorCode == "23000" ) {
          $dbErrorMessage = $dbErrorInfo[2];
          $dbErrorMessage = explode(' ', $dbErrorMessage);
          $dbErrorMessage = array_filter($dbErrorMessage);
          $input = substr($dbErrorMessage[5], 1, -1);
          $dbErrorMessage = implode('-', [$dbErrorMessage[0], $dbErrorMessage[1], $input]);
          $dbErrorMessage = strtolower($dbErrorMessage);
          return [$input => $dbErrorMessage];
        } 
        return 0;
      }


      return $this->db->lastInsertId();
    }

    /**
     * Insert data(s) to database table.
     * 
     * @param string $table : You must specify the table which you want to update data.
     * @param array $datas : You must specify datas as value and columns as key of an array.
     * @param int ...$ids : You can specfify which data(s) will updated.
     * 
     * @return int|array : Affected ids or error.
     * @author Beresyus
     */
    public function update(string $table, array $datas, int ...$ids) : int {
      $query = "UPDATE `$table` SET";

      $lastData = end($datas);

      foreach($datas as $column => $data) {
        switch( gettype($data) ) {
          case 'string':
            if($data == $lastData) $query .= " `$column` = '$data' ";
            else $query .= " `$column` = '$data', ";
            break;
          case 'NULL':
            if($data == $lastData) $query .= " `$column` = NULL ";
            else $query .= " `$column` = NULL, ";
            break;
          default:
            if($data == $lastData) $query .= " `$column` = $data ";
            else $query .= " `$column` = $data, ";
            break;
        }
      }

      $query .= "WHERE";

      $lastData = end($ids);
      if( isset($ids) && !empty($ids) ) {
        foreach($ids as $id) {
          if($id == $lastData) {
            $query .= " `id` = $id";
          } else {
            $query .= " `id` = $id AND";
          }
        }
      }

      $this->setQuery($query);
      $this->statement = $this->db->prepare($this->query);
      $this->statement->execute();
      return $this->statement->rowCount();
    }

    /**
     * @return int
     */
    public function delete(string $table, int ...$ids) : int {
      $query = "DELETE FROM `$table`";
      if( !empty($ids) ) {
        $query .= " WHERE ";
        foreach($ids as $k => $id) {
          if($k == array_key_last($ids)) {
            $query .= " `id` = $id";
          } else {
            $query .= " `id` = $id,";
          }
        }
      }

      $this->setQuery($query);
      $this->statement = $this->db->prepare($this->query);
      $this->statement->execute();
      return $this->statement->rowCount();
    }

    /**
     * @return int|array
     */
    public function raw(string $query) : int | array {
      $this->setQuery($query);
      $this->statement = $this->db->prepare($this->query);
      $this->statement->execute();
      if( strpos($query, "SELECT") !== false ) {
        $datas = $this->statement->fetchAll(PDO::FETCH_ASSOC);
        return $datas;
      } else if( strpos($query, "INSERT") !== false ) {
        $lastInsertId = $this->db->lastInsertId();
        return $lastInsertId;
      } else if( strpos($query, "UPDATE") !== false || strpos($query, "DELETE") !== false ) {
        $affectedRows = $this->statement->rowCount();
        return $affectedRows;
      }
    }

    /**
     * You can set query as row.
     * @param string $query : Query
     * @return void
     * @author Beresyus
     */
    public function setQuery(string $query) : void {
      $this->query = $query;
    }

    /**
     * You can get query as row.
     * @return string
     * @author Beresyus 
     */
    public function getQuery() : string {
      return $this->query;
    }

    /**
     * You can get PDOStatement if u want intervene to Database.
     * @return PDOStatement
     * @author Beresyus
     */
    public function getStatement() : PDOStatement {
      return $this->statement;
    }

    public function __destruct() {
      unset($this->db);
      unset($this->statement);
    }
  }