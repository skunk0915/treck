<?php

class Renderer
{
    private $parsedown;
    private $baseUrl;

    public function __construct($baseUrl)
    {
        $this->parsedown = new Parsedown();
        $this->baseUrl = $baseUrl;
    }

    public function render($articleContent)
    {
        // 1. Remove metadata header
        $contentBody = preg_replace('/^#\s+.*\n/', '', $articleContent);
        
        // 2. Parse Dialogue
        $contentBody = $this->parseDialogue($contentBody);
        
        // 3. Markdown to HTML
        $htmlContent = $this->parsedown->text($contentBody);
        
        // 4. Fix Image Paths
        $htmlContent = str_replace('src="/img/', 'src="' . $this->baseUrl . '/img/', $htmlContent);
        
        // 5. Generate and Insert TOC
        $tocHtml = $this->generateTOC($htmlContent);
        if ($tocHtml) {
            $pos = strpos($htmlContent, '<h2');
            if ($pos !== false) {
                // If there's an h2, insert TOC before it
                $htmlContent = substr_replace($htmlContent, $tocHtml, $pos, 0);
            } else {
                // Otherwise prepend
                $htmlContent = $tocHtml . $htmlContent;
            }
        }

        return $htmlContent;
    }

    private function parseDialogue($content)
    {
        $lines = explode("\n", $content);
        $processedLines = [];
        
        $currentSpeaker = null;
        $currentMessageLines = [];
        $currentType = null;
        $currentIconHtml = null;

        foreach ($lines as $line) {
            if (preg_match('/^\s*\*\*(.+?)\*\*:\s*(.*)/', $line, $matches)) {
                // Found a new speaker line
                
                // Close previous speaker if exists
                if ($currentSpeaker) {
                    $this->flushSpeaker($processedLines, $currentType, $currentIconHtml, $currentMessageLines);
                }

                // Setup new speaker
                $currentSpeaker = $matches[1];
                $currentMessageLines = [$matches[2]]; // Start with the message part
                
                $currentType = 'other';
                $currentIconHtml = mb_substr($currentSpeaker, 0, 1);
                
                if (strpos($currentSpeaker, '先生') !== false) {
                    $currentType = 'teacher';
                    $currentIconHtml = '<img src="' . $this->baseUrl . '/img/teacher.png" alt="先生" loading="lazy">';
                } elseif (strpos($currentSpeaker, 'JK') !== false || strpos($currentSpeaker, '生徒') !== false) {
                    $currentType = 'student';
                    $currentIconHtml = '<img src="' . $this->baseUrl . '/img/jk.png" alt="JK" loading="lazy">';
                }
            } elseif ($currentSpeaker) {
                // Inside a dialogue
                if (trim($line) === '') {
                    // Blank line ends the dialogue
                    $this->flushSpeaker($processedLines, $currentType, $currentIconHtml, $currentMessageLines);
                    
                    $currentSpeaker = null;
                    $currentMessageLines = [];
                    $processedLines[] = $line; // Keep the blank line
                } else {
                    $currentMessageLines[] = $line;
                }
            } else {
                // Normal text
                $processedLines[] = $line;
            }
        }

        // Flush last speaker if exists
        if ($currentSpeaker) {
            $this->flushSpeaker($processedLines, $currentType, $currentIconHtml, $currentMessageLines);
        }

        return implode("\n", $processedLines);
    }

    private function flushSpeaker(&$processedLines, $currentType, $currentIconHtml, $currentMessageLines)
    {
        $fullMessage = implode("\n", $currentMessageLines);
        $renderedMessage = $this->parsedown->line($fullMessage);

        $html = "
<div class=\"chat-row $currentType\">
    <div class=\"icon $currentType\">$currentIconHtml</div>
    <div class=\"bubble\">
        <div class=\"message\">$renderedMessage</div>
    </div>
</div>";
        $processedLines[] = $html;
    }

    private function generateTOC(&$content)
    {
        $toc = '';
        $matches = [];
        if (preg_match_all('/<h([2-3])>(.*?)<\/h[2-3]>/', $content, $matches, PREG_SET_ORDER)) {
            $toc .= '<div class="toc-container">';
            $toc .= '<p class="toc-title">目次 <button class="toc-toggle">[-]</button></p>';
            $toc .= '<ul class="toc-list">';
            
            $currentLevel = 2;
            $counter = 0;
            $openLi = false;

            foreach ($matches as $match) {
                $level = (int)$match[1];
                $text = strip_tags($match[2]);
                $id = 'section-' . ++$counter;

                // Add ID to the original header in content
                $content = str_replace($match[0], "<h$level id=\"$id\">" . $match[2] . "</h$level>", $content);

                if ($level > $currentLevel) {
                    $toc .= '<ul>';
                    $openLi = false; 
                } elseif ($level < $currentLevel) {
                    if ($openLi) {
                        $toc .= '</li>'; 
                    }
                    $toc .= '</ul>';
                    $toc .= '</li>'; 
                    $openLi = true; 
                } elseif ($openLi) {
                     $toc .= '</li>'; 
                }
                
                $toc .= "<li><a href=\"#$id\">$text</a>";
                $openLi = true;
                $currentLevel = $level;
            }

            if ($openLi) {
                $toc .= '</li>';
            }
            while ($currentLevel > 2) {
                $toc .= '</ul></li>';
                $currentLevel--;
            }

            $toc .= '</ul>';
            $toc .= '</div>';
        }
        return $toc;
    }
}
