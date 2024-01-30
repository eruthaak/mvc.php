<?php

require 'interface.BasicQueries.php';

/**
 * 
 */
class Database extends \PDO implements BasicQueries {
  private PDO $db;
  private PDOStatement $statement;
  private string $query;

  public function __construct(string $credential, string $username, string $password = NULL) {
    $this->db = new parent($credential, $username, $password);
    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
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
  public function select(string $table, array $columns = [], array $options = NULL) : array {
    if( empty($columns) ) {
      $query = "SELECT * FROM `$table`";
    } else {
      $aliases = array_keys($columns);
      $columns = array_map(function($alias) use ($columns) { return is_numeric($alias) ? '`' . $columns[$alias] . '`' : '`' . $columns[$alias] . '` AS \'' . $alias . '\''; }, $aliases);
      $columns = implode(', ', $columns);
      $query = "SELECT $columns FROM `$table`";
    }

    if( isset($options) ) {
      $query .= " WHERE ";
      $and = @$options['AND'];
      $or = @$options['OR'];
      $raw = @$options['RAW'];

      if(isset($and)) {
        $where = array_map(function($conditions, $expression) {
          $sentence = array_map(function($value, $column) use ($expression) {
            if(gettype($value) === 'NULL') return "`$column` $expression NULL"; else if(gettype($value) === 'string') return "`$column` $expression '$value'"; else return "`$column` $expression $value";
            // return is_numeric($value) ? "`$column` $expression $value" : "`$column` $expression '$value'";
          }, $conditions, array_keys($conditions));
          return implode(' AND ', $sentence);
        }, $and, array_keys($and));
        $where = implode(' AND ', $where);
        $query .= $where;
      }

      if(isset($or)) {
        if(isset($and)) $query .= " OR ";
        $where = array_map(function($conditions, $expression) {
          $sentence = array_map(function($value, $column) use ($expression) {
            if(gettype($value) === 'NULL') return "`$column` $expression NULL"; else if(gettype($value) === 'string') return "`$column` $expression '$value'"; else return "`$column` $expression $value";
          }, $conditions, array_keys($conditions));
          return implode(' OR ', $sentence);
        }, $or, array_keys($or));
        $where = implode(' OR ', $where);
        $query .= $where;
      }

      if(isset($raw)) $query .= $raw;
    }

    $this->setQuery($query);
    echo $this->query; exit;
    $this->statement = $this->db->prepare($this->query);
    $this->statement->execute();
    $datas = $this->statement->fetchAll(PDO::FETCH_ASSOC);
    if(count($datas) === 1) return $datas[0];
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
    $columns = array_keys($datas);
    $columns = array_map(function($column) { return '`' . $column . '`'; }, $columns);
    $columns = implode(', ', $columns);
    $values = array_values($datas);
    $values = array_map(function($value) { if(gettype($value) === 'NULL') return 'NULL'; else if(gettype($value) === 'string') return '\'' . $value . '\''; else return $value; }, $values);
    $values = implode(', ', $values);
    $query = "INSERT INTO `$table` ($columns) VALUES ($values)";

    $this->setQuery($query);
    // echo $this->query; exit;
    $this->statement = $this->db->prepare($this->query);
    $result = $this->statement->execute();

    if( $result === false ) return $this->statement->errorInfo();
    else return $this->db->lastInsertId();
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
  public function update(string $table, array $columns = [], array $options = NULL) : int {
    $query = "UPDATE `$table` SET ";
    $set = array_map(function($value, $column) {
      if(gettype($value) === 'NULL') return "`$column` = NULL";
      else if(gettype($value) === 'string') return "`$column` = '$value'";
      else return "`$column` = $value";
    }, array_values($columns), array_keys($columns));
    $set = implode(', ', $set);
    $query .= $set;
    
    if( !empty($options) ) {
      $query .= " WHERE ";
      $and = @$options['AND'];
      $or = @$options['OR'];
      $raw = @$options['RAW'];

      if(isset($and)) {
        $where = array_map(function($conditions, $expression) {
          $sentence = array_map(function($value, $column) use ($expression) {
            if(gettype($value) === 'NULL') return "`$column` $expression NULL"; else if(gettype($value) === 'string') return "`$column` $expression '$value'"; else return "`$column` $expression $value";
          }, $conditions, array_keys($conditions));
          return implode(' AND ', $sentence);
        }, $and, array_keys($and));
        $where = implode(' AND ', $where);
        $query .= $where;
      }

      if(isset($or)) {
        if(isset($and)) $query .= " OR ";
        $where = array_map(function($conditions, $expression) {
          $sentence = array_map(function($value, $column) use ($expression) {
            if(gettype($value) === 'NULL') return "`$column` $expression NULL"; else if(gettype($value) === 'string') return "`$column` $expression '$value'"; else return "`$column` $expression $value";
          }, $conditions, array_keys($conditions));
          return implode(' OR ', $sentence);
        }, $or, array_keys($or));
        $where = implode(' OR ', $where);
        $query .= $where;
      }

      if(isset($raw)) $query .= $raw;
    }

    $this->setQuery($query);
    $this->statement = $this->db->prepare($this->query);
    $this->statement->execute();
    return $this->statement->rowCount();
  }

  /**
   * @return int
   */
  public function delete(string $table, array $options = NULL) : int {
    $query = "DELETE FROM `$table`";

    if( !empty($options) ) {
      $query .= " WHERE ";
      $and = @$options['AND'];
      $or = @$options['OR'];
      $raw = @$options['RAW'];

      if(isset($and)) {
        $where = array_map(function($conditions, $expression) {
          $sentence = array_map(function($value, $column) use ($expression) {
            if(gettype($value) === 'NULL') return "`$column` $expression NULL"; else if(gettype($value) === 'string') return "`$column` $expression '$value'"; else return "`$column` $expression $value";
          }, $conditions, array_keys($conditions));
          return implode(' AND ', $sentence);
        }, $and, array_keys($and));
        $where = implode(' AND ', $where);
        $query .= $where;
      }

      if(isset($or)) {
        if(isset($and)) $query .= " OR ";
        $where = array_map(function($conditions, $expression) {
          $sentence = array_map(function($value, $column) use ($expression) {
            if(gettype($value) === 'NULL') return "`$column` $expression NULL"; else if(gettype($value) === 'string') return "`$column` $expression '$value'"; else return "`$column` $expression $value";
          }, $conditions, array_keys($conditions));
          return implode(' OR ', $sentence);
        }, $or, array_keys($or));
        $where = implode(' OR ', $where);
        $query .= $where;
      }

      if(isset($raw)) $query .= $raw;
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