<?php

  # This enum class is for select queries.
  # You must specify first one or more than first one row from table.
  enum FETCH {
    const GET = 1;
    const FIRST = 0;
  }