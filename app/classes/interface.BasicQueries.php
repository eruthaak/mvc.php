<?php

  interface BasicQueries {
    // Four Basic SQL Query
    public function select(string $table, array $columns = [], array $options = []) : array;
    public function insert(string $table, array $datas) : int|array;
    public function update(string $table, array $columns = [], array $options = []) : int;
    public function delete(string $table, array $options = []) : int;
  }