//<?php
//require_once "../config.php";
//class Repository {
//    private $file;
//
//    public function __construct($filename) {
//        $this->file = DATA_PATH . $filename;
//        if (!file_exists($this->file)) {
//            file_put_contents($this->file, json_encode([]));
//        }
//    }
//
//    public function getAll() {
//        return json_decode(file_get_contents($this->file), true) ?: [];
//    }
//
//    public function saveAll($data) {
//        file_put_contents($this->file, json_encode($data, JSON_PRETTY_PRINT));
//    }
//}
