<?php
/**
 * Gemini Lite AI Content Rewriter
 *
 * @package News Scrapper
 */

if (!defined('ABSPATH')) {
    exit;
}

class News_Scraper_Gemini {

    protected $api_key;
    protected $model;

    public function __construct() {
        $this->api_key = get_option('news_scrapper_gemini_api_key', defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '');
        $this->model   = get_option('news_scrapper_gemini_model', 'gemini-2.0-flash-lite');
    }

    /**
     * Check if Gemini API is configured
     */
    public function is_configured() {
        return !empty($this->api_key);
    }

    /**
     * Step 3: Rewrite article strictly using scraped data without adding outside information
     *
     * @param string $raw_headline
     * @param string $markdown_content
     * @param array  $datapoints Optional structured datapoints
     * @return array
     */
    public function rewrite_article($raw_headline, $markdown_content, $datapoints = array()) {
        if (!$this->is_configured()) {
            // Fallback: Clean formatting without AI if key is missing
            return $this->fallback_clean_content($raw_headline, $markdown_content, $datapoints);
        }

        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . ':generateContent?key=' . $this->api_key;

        $prompt  = "You are a senior financial news editor and investigative journalist.\n";
        $prompt .= "Your task is to REWRITE the following news article into a comprehensive, highly engaging, and clear news report.\n\n";
        $prompt .= "CRITICAL MANDATORY RULES:\n";
        $prompt .= "1. STRICT FACTUAL FIDELITY: DO NOT invent, assume, extrapolate, or add ANY new facts, figures, percentages, dates, names, or quotes.\n";
        $prompt .= "2. STRUCTURE:\n";
        $prompt .= "   - Include an executive summary callout box at the start: <div class=\"news-key-takeaways\"><h4>Key Takeaways</h4><ul><li>...</li></ul></div> with 3 to 5 core bullet points.\n";
        $prompt .= "   - Break down the article body into logical thematic sections using <h2> and <h3> subheadings (e.g. Market Reactions, Economic Impact, Expert Outlook).\n";
        $prompt .= "   - Use well-crafted, fluid paragraphs (<p>) preserving ALL exact dates, statistics, percentages, and dollar amounts.\n";
        $prompt .= "   - Use <blockquote> for direct quotes from officials, executives, or analysts.\n";
        $prompt .= "3. HEADLINE: Generate an authoritative, compelling news headline based directly on the story.\n";
        $prompt .= "4. SEO TAGS: Extract 4 to 8 high-relevance topic tags directly related to the entities, sectors, and issues discussed.\n";
        $prompt .= "5. Return ONLY a valid JSON object strictly matching this schema with NO markdown code blocks or wrapper text:\n";
        $prompt .= "{\n  \"headline\": \"Engaging News Headline\",\n  \"content_html\": \"<div class=\\\"news-key-takeaways\\\"><h4>Key Takeaways</h4><ul><li>...</li></ul></div><h2>Section Heading</h2><p>Article narrative...</p>\",\n  \"tags\": [\"Tag 1\", \"Tag 2\", \"Tag 3\"]\n}\n\n";

        if (!empty($datapoints['highlights'])) {
            $prompt .= "SOURCE KEY HIGHLIGHTS:\n- " . implode("\n- ", $datapoints['highlights']) . "\n\n";
        }
        if (!empty($datapoints['author'])) {
            $prompt .= "REPORTED BY: " . $datapoints['author'] . "\n\n";
        }
        $prompt .= "SOURCE ARTICLE CONTENT:\n" . substr($markdown_content, 0, 10000);

        $payload = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => $prompt),
                    ),
                ),
            ),
            'generationConfig' => array(
                'temperature'     => 0.2, // Low temperature for high factual accuracy
                'responseMimeType'=> 'application/json',
            ),
        );

        $response = wp_remote_post($endpoint, array(
            'timeout' => 60,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body'    => wp_json_encode($payload),
        ));

        if (is_wp_error($response)) {
            return $this->fallback_clean_content($raw_headline, $markdown_content, $datapoints);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $candidate_text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (!empty($candidate_text)) {
            $parsed = json_decode($candidate_text, true);
            if (is_array($parsed) && !empty($parsed['content_html'])) {
                return array(
                    'success'      => true,
                    'headline'     => !empty($parsed['headline']) ? sanitize_text_field($parsed['headline']) : $raw_headline,
                    'content_html' => wp_kses_post($parsed['content_html']),
                    'tags'         => !empty($parsed['tags']) && is_array($parsed['tags']) ? array_map('sanitize_text_field', $parsed['tags']) : array(),
                    'model_used'   => $this->model,
                );
            }
        }

        return $this->fallback_clean_content($raw_headline, $markdown_content, $datapoints);
    }

    /**
     * Fallback parser when AI API is unavailable or returns an error
     */
    public static function fallback_clean_content($raw_headline, $markdown_content, $datapoints = array()) {
        $lines = explode("\n", $markdown_content);
        $html = '';
        $in_list = false;

        // If structured highlights are available, add styled Key Takeaways box
        if (!empty($datapoints['highlights']) && is_array($datapoints['highlights'])) {
            $html .= "<div class=\"news-key-takeaways\">\n  <h4>Key Takeaways</h4>\n  <ul>\n";
            foreach ($datapoints['highlights'] as $hl) {
                $html .= '    <li>' . esc_html($hl) . "</li>\n";
            }
            $html .= "  </ul>\n</div>\n\n";
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (empty($trimmed)) {
                if ($in_list) {
                    $html .= "</ul>\n";
                    $in_list = false;
                }
                continue;
            }

            // Exclude noise lines
            if (preg_match('/^\[(?:Skip|Livestream|Watchlist|Sign In|Create free|Menu|Watch Now|Listen|Zoom In)/i', $trimmed)) {
                continue;
            }

            // Headings
            if (preg_match('/^#\s+(.+)$/', $trimmed, $m)) {
                if ($in_list) { $html .= "</ul>\n"; $in_list = false; }
                // Skip duplicating main headline as H2
                if (stripos($trimmed, substr($raw_headline, 0, 20)) === false) {
                    $html .= '<h2>' . esc_html($m[1]) . "</h2>\n";
                }
                continue;
            }
            if (preg_match('/^#{2,4}\s+(.+)$/', $trimmed, $m)) {
                if ($in_list) { $html .= "</ul>\n"; $in_list = false; }
                $html .= '<h3>' . esc_html($m[1]) . "</h3>\n";
                continue;
            }

            // Bullet Lists
            if (preg_match('/^[\*\-]\s+(.+)$/', $trimmed, $m)) {
                if (!$in_list) {
                    $html .= "<ul>\n";
                    $in_list = true;
                }
                $item_text = self::format_inline_markdown($m[1]);
                $html .= '  <li>' . $item_text . "</li>\n";
                continue;
            }

            if ($in_list) {
                $html .= "</ul>\n";
                $in_list = false;
            }

            // Inline Image
            if (preg_match('/^!\[(.*?)\]\((https?:\/\/[^\s\)]+)\)$/', $trimmed, $m)) {
                $alt = esc_attr($m[1]);
                $src = esc_url($m[2]);
                $html .= '<p class="news-scraped-inline-image"><img src="' . $src . '" alt="' . $alt . '" class="img-fluid rounded" /></p>' . "\n";
                continue;
            }

            // Blockquote
            if (preg_match('/^>\s+(.+)$/', $trimmed, $m)) {
                $html .= '<blockquote>' . self::format_inline_markdown($m[1]) . "</blockquote>\n";
                continue;
            }

            // Standard Paragraph
            $para = self::format_inline_markdown($trimmed);
            $html .= '<p>' . $para . "</p>\n";
        }

        if ($in_list) {
            $html .= "</ul>\n";
        }

        // Generate tags from headline and text
        $words = array_filter(explode(' ', strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $raw_headline))), function($w) {
            return strlen($w) > 4 && !in_array($w, array('about', 'their', 'which', 'would', 'there', 'financial', 'market', 'stock', 'global', 'today', 'after'));
        });
        $tags = array_slice(array_unique(array_values($words)), 0, 5);

        return array(
            'success'      => true,
            'headline'     => !empty($raw_headline) ? $raw_headline : 'Financial News Intelligence',
            'content_html' => $html,
            'tags'         => $tags,
            'model_used'   => 'rule-based-fallback',
        );
    }

    /**
     * Format inline markdown: bold, italic, links
     */
    public static function format_inline_markdown($text) {
        // [Link Text](URL)
        $text = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/', function($m) {
            return '<a href="' . esc_url($m[2]) . '" target="_blank" rel="noopener nofollow">' . esc_html($m[1]) . '</a>';
        }, $text);

        // Bold **text**
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);

        // Italic *text*
        $text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $text);

        return wp_kses_post($text);
    }
}
