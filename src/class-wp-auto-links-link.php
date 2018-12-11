<?php

class WP_Auto_Links_Link
{
    /**
     * @var string
     */
    protected $url = '';

    /**
     * @var string[]
     */
    protected $keywords = [];

    /**
     * @var int
     */
    protected $rest;

    public function __construct(string $url, array $keywords, int $rest)
    {
        $this->url = $url;
        $this->keywords = $keywords;
        $this->rest = $rest;
    }

    public function __get(string $name)
    {
        return $this->$name;
    }

    public function increment(): bool
    {
        $this->rest--;
        return $this->rest >= 0;
    }

    public function has_keyword(string $match): bool
    {
        return in_array($match, $this->keywords);
    }
}
