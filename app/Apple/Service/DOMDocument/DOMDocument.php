<?php

namespace App\Apple\Service\DOMDocument;

use Symfony\Component\DomCrawler\Crawler;

class DOMDocument
{
    protected Crawler $crawler;

    public function __construct(protected ?string $html = null)
    {
        $this->crawler = new Crawler($html);
    }

    public function setHtml(string $html): self
    {
        $this->html = $html;
        $this->crawler = new Crawler($html);
        return $this;
    }

    public function getCrawler(): Crawler
    {
        return $this->crawler;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function getHtmlFragment(): Crawler
    {
        return $this->crawler->filter('script[type="application/json"].boot_args')->first();
    }

    public function getJson(): ?array
    {
        $script = $this->getHtmlFragment();
        if (!$script->count()) {
            return null;
        }

        // 获取 script 标签的内容
        $jsonString = $script->text();

        // 解码 JSON 数据
        $data = json_decode($jsonString, true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON解析错误: '.json_last_error_msg());
        }

        return $data;
    }
}
