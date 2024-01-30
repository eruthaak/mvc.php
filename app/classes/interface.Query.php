<?php

  interface Query {
    // Four Basic SQL Query
    public function select(string $table, int $fetchMode = FETCH::GET, string ...$columns) : array;
    public function insert(string $table, array $datas) : int|array;
    public function update(string $table, array $datas, int ...$ids) : int;
    public function delete(string $table, int ...$ids) : int;
  }