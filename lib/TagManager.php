<?php

class TagManager {
    private $dataFile;
    private $tagsData = [];

    public function __construct($dataFile) {
        $this->dataFile = $dataFile;
        $this->load();
    }

    private function load() {
        if (file_exists($this->dataFile)) {
            $json = file_get_contents($this->dataFile);
            $this->tagsData = json_decode($json, true) ?: [];
        } else {
            $this->tagsData = [];
        }
    }

    private function save() {
        // Pretty print for easier debugging/manual editing if needed
        $json = json_encode($this->tagsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->dataFile, $json);
    }

    public function getTags($filename) {
        return $this->tagsData[$filename] ?? [];
    }

    public function setTags($filename, $tags) {
        // Filter and clean tags
        $tags = array_filter(array_map('trim', $tags));
        $tags = array_unique($tags);
        $tags = array_values($tags); // Re-index

        $this->tagsData[$filename] = $tags;
        $this->save();
    }

    public function getAllTags() {
        $allTags = [];
        foreach ($this->tagsData as $filename => $tags) {
            foreach ($tags as $tag) {
                if ($tag) {
                    $allTags[$tag] = ($allTags[$tag] ?? 0) + 1;
                }
            }
        }
        ksort($allTags);
        return $allTags;
    }

    public function renameTag($oldTag, $newTag) {
        $count = 0;
        foreach ($this->tagsData as $filename => $tags) {
            if (in_array($oldTag, $tags)) {
                $newTags = array_map(function($t) use ($oldTag, $newTag) {
                    return $t === $oldTag ? $newTag : $t;
                }, $tags);
                $this->tagsData[$filename] = array_unique($newTags);
                $count++;
            }
        }
        if ($count > 0) {
            $this->save();
        }
        return $count;
    }

    public function mergeTags($sourceTag, $targetTag, $isDelete = false) {
        $count = 0;
        foreach ($this->tagsData as $filename => $tags) {
            if (in_array($sourceTag, $tags)) {
                // Remove source
                $tags = array_diff($tags, [$sourceTag]);
                
                // Add target if not present and not deleting
                if (!$isDelete && !in_array($targetTag, $tags)) {
                    $tags[] = $targetTag;
                }
                
                $this->tagsData[$filename] = array_values($tags);
                $count++;
            }
        }
        if ($count > 0) {
            $this->save();
        }
        return $count;
    }
}
