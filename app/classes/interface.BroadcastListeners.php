<?php
  interface MustListenTheseActions {
    public function onConnect();
    public function onMessage();
    public function onClose();
    public function onError();
  }