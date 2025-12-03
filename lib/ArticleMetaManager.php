<?php

class ArticleMetaManager {
    private $dataFile;
    private $metaData = [];

    public function __construct($dataFile) {
        $this->dataFile = $dataFile;
        $this->load();
    }

    private function load() {
        if (file_exists($this->dataFile)) {
            $json = file_get_contents($this->dataFile);
            $this->metaData = json_decode($json, true) ?: [];
        } else {
            $this->metaData = [];
        }
    }

    private function save() {
        $json = json_encode($this->metaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->dataFile, $json);
    }

    public function getMeta($filename) {
        return $this->metaData[$filename] ?? [
            'published_at' => null,
            'status' => 'public' // Default to public for backward compatibility or ease of use, or 'private'? 
                                 // User said "Add Public/Private flag". 
                                 // Existing articles are effectively public. So default 'public' is safer.
        ];
    }

    public function setMeta($filename, $data) {
        $current = $this->getMeta($filename);
        // Merge with defaults to ensure keys exist
        $newData = array_merge($current, $data);
        
        $this->metaData[$filename] = $newData;
        $this->save();
    }

    public function getAllMeta() {
        return $this->metaData;
    }
}
