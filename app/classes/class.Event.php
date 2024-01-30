<?php
  class Event implements MustListenTheseActions {
    public function __construct() { }

    public function onConnect() { }
    public function onMessage() { }
    public function onClose() { }
    public function onError() { }
  }