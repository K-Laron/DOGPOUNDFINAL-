<?php

class TestController {
    public function __construct($db, $user) {}

    public function index() {
        echo json_encode(['action' => 'index']);
    }

    public function show($id) {
        echo json_encode(['action' => 'show', 'id' => $id]);
    }
}
