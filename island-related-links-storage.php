<?php
/**
 * 5島「関連サイト」リンクの読み書き（JSON）
 */

function island_related_links_data_path(): string
{
    return __DIR__ . '/data/island_related_links.json';
}

function island_related_links_island_keys(): array
{
    return ['amami', 'kikai', 'tokunoshima', 'okinoerabu', 'yoron'];
}

function island_related_links_island_labels_ja(): array
{
    return [
        'amami' => '奄美大島',
        'kikai' => '喜界島',
        'tokunoshima' => '徳之島',
        'okinoerabu' => '沖永良部島',
        'yoron' => '与論島',
    ];
}

/** 管理画面プルダウン用：ポップアップ見出しの定型候補 */
function island_related_links_title_presets(string $islandKey): array
{
    $labels = island_related_links_island_labels_ja();
    $name = $labels[$islandKey] ?? $islandKey;
    return [
        $name . 'の旅行情報',
        $name . 'の観光案内',
        $name . 'の観光情報',
        $name . ' 関連サイト',
        $name . 'リンク一覧',
    ];
}

function island_related_links_default(): array
{
    return [
        'titles' => [
            'amami' => '奄美大島の旅行情報',
            'kikai' => '喜界島の旅行情報',
            'tokunoshima' => '徳之島の旅行情報',
            'okinoerabu' => '沖永良部島の旅行情報',
            'yoron' => '与論島の旅行情報',
        ],
        'links' => [
            'amami' => [
                ['label' => '概要', 'url' => 'https://www.amami-tourism.org/about/'],
                ['label' => '自然', 'url' => 'https://www.amami-tourism.org/about/nature/'],
                ['label' => '歴史', 'url' => 'https://www.amami-tourism.org/about/history/'],
                ['label' => '文化', 'url' => 'https://www.amami-tourism.org/about/culture/'],
                ['label' => '天候', 'url' => 'https://www.amami-tourism.org/about/weather/'],
                ['label' => '特産物', 'url' => 'https://www.amami-tourism.org/about/specialty/'],
                ['label' => '奄美へのアクセス', 'url' => 'https://www.amami-tourism.org/access/#access'],
                ['label' => '島内こうつう手段', 'url' => 'https://www.amami-tourism.org/access/#traffic'],
                ['label' => '見どころ', 'url' => 'https://www.amami-tourism.org/scenic-spots/'],
                ['label' => '泊まる', 'url' => 'https://www.amami-tourism.org/lodging/'],
                ['label' => '食べる', 'url' => 'https://www.amami-tourism.org/gourmet/'],
                ['label' => '体験する', 'url' => 'https://www.amami-tourism.org/experience/'],
                ['label' => 'お土産', 'url' => 'https://www.amami-tourism.org/souvenir/'],
                ['label' => '島食', 'url' => 'https://www.amami-tourism.org/magazine/magazine-category/gourmet/'],
            ],
            'kikai' => [
                ['label' => '概要', 'url' => 'https://kikaijimanavi.com/tourism/'],
                ['label' => '食事', 'url' => 'https://kikaijimanavi.com/meal/'],
                ['label' => '宿泊', 'url' => 'https://kikaijimanavi.com/lodging/'],
                ['label' => 'ショップ', 'url' => 'https://kikaijimanavi.com/shopping/'],
                ['label' => '特産品', 'url' => 'https://kikaijimanavi.com/specialtygoods/'],
                ['label' => '喜界観光・体験', 'url' => 'https://kikaijimanavi.com/tourism-and-experience/'],
            ],
            'tokunoshima' => [
                ['label' => '概要', 'url' => 'https://www.tokunoshima-kanko.com/about/'],
                ['label' => '遊ぶ', 'url' => 'https://www.tokunoshima-kanko.com/play/'],
                ['label' => '食べる', 'url' => 'https://www.tokunoshima-kanko.com/food/'],
                ['label' => '泊まる', 'url' => 'https://www.tokunoshima-kanko.com/?post_type=shop&s=&shop_cate=stay&area='],
                ['label' => '特産品', 'url' => 'https://www.tokunoshima-kanko.com/item/'],
            ],
            'okinoerabu' => [
                ['label' => '組織・会員募集', 'url' => 'https://okinoerabujima.info/organization/member-recruitment'],
                ['label' => '概要', 'url' => 'https://okinoerabujima.info/course'],
                ['label' => '観光', 'url' => 'https://okinoerabujima.info/spot'],
                ['label' => '体験', 'url' => 'https://okinoerabujima.info/activity'],
                ['label' => 'グルメ・お土産', 'url' => 'https://okinoerabujima.info/pickup/gourmet'],
                ['label' => '宿泊', 'url' => 'https://okinoerabujima.info/stay'],
                ['label' => '交通手段', 'url' => 'https://okinoerabujima.info/pickup/access'],
                ['label' => 'エコラブ', 'url' => 'https://okinoerabujima.info/pickup/erabucoco'],
                ['label' => 'サービス施設', 'url' => 'https://okinoerabujima.info/service'],
                ['label' => '島図鑑', 'url' => 'https://okinoerabujima.info/pickup/erabushima_zukan'],
                ['label' => '基礎知識', 'url' => 'https://okinoerabujima.info/pickup/beginner'],
            ],
            'yoron' => [
                ['label' => '島内交通', 'url' => 'https://www.yorontou.info/on-island.html'],
                ['label' => 'お役立ち情報', 'url' => 'https://www.yorontou.info/safe-travel/'],
                ['label' => '宿泊', 'url' => 'https://www.yorontou.info/spot/genre/stay'],
                ['label' => '観光スポット', 'url' => 'https://www.yorontou.info/sightseeing/'],
                ['label' => '体験', 'url' => 'https://www.yorontou.info/spot/genre/experience-facility'],
                ['label' => 'お土産', 'url' => 'https://www.yorontou.info/spot/genre/souvenir-shop'],
                ['label' => 'マリンアクティビティ', 'url' => 'https://www.yorontou.info/spot/genre/marine-activity'],
                ['label' => 'ダイビングスポット', 'url' => 'https://www.yorontou.info/spot/genre/diving-spot'],
                ['label' => '史跡・名所・景勝地', 'url' => 'https://www.yorontou.info/spot/genre/historic'],
                ['label' => 'ビーチ', 'url' => 'https://www.yorontou.info/spot/genre/beach'],
                ['label' => 'グルメ', 'url' => 'https://www.yorontou.info/gourmet/'],
            ],
        ],
    ];
}

function island_related_links_load(): array
{
    $path = island_related_links_data_path();
    if (!file_exists($path)) {
        $default = island_related_links_default();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents(
            $path,
            json_encode($default, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
        return $default;
    }
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['links']) || !is_array($data['links'])) {
        return island_related_links_default();
    }
    if (!isset($data['titles']) || !is_array($data['titles'])) {
        $data['titles'] = island_related_links_default()['titles'];
    }
    return $data;
}

function island_related_links_validate_url(string $url): bool
{
    if (strlen($url) > 2048) {
        return false;
    }
    return (bool) preg_match('#\Ahttps?://[^\s]+\z#i', $url);
}

/**
 * POST された配列から保存用データを組み立て。失敗時は [ 'error' => 'message' ]
 * @param array<string,mixed> $post $_POST
 * @return array{error?:string,data?:array}
 */
function island_related_links_build_from_post(array $post): array
{
    $allowed = island_related_links_island_keys();
    $labels = island_related_links_island_labels_ja();
    $titlesIn = isset($post['titles']) && is_array($post['titles']) ? $post['titles'] : [];
    $linksIn = isset($post['links']) && is_array($post['links']) ? $post['links'] : [];

    $out = [
        'titles' => [],
        'links' => [],
    ];

    foreach ($allowed as $key) {
        $title = isset($titlesIn[$key]) ? trim((string) $titlesIn[$key]) : '';
        if ($title === '') {
            $def = island_related_links_default();
            $title = $def['titles'][$key] ?? $key;
        }
        if (mb_strlen($title) > 120) {
            return ['error' => "ポップアップ見出しが長すぎます: {$key}"];
        }
        $out['titles'][$key] = $title;
    }

    foreach ($allowed as $key) {
        $rows = isset($linksIn[$key]) && is_array($linksIn[$key]) ? $linksIn[$key] : [];
        $clean = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = isset($row['label']) ? trim((string) $row['label']) : '';
            $url = isset($row['url']) ? trim((string) $row['url']) : '';
            if ($label === '' && $url === '') {
                continue;
            }
            if ($label === '' || $url === '') {
                return ['error' => "「" . ($labels[$key] ?? $key) . "」の行に、表示名またはURLの未入力があります。"];
            }
            if (mb_strlen($label) > 200) {
                return ['error' => '表示名が長すぎる行があります。'];
            }
            if (!island_related_links_validate_url($url)) {
                return ['error' => 'URL は http:// または https:// で始まる必要があります: ' . mb_substr($label, 0, 40)];
            }
            $clean[] = ['label' => $label, 'url' => $url];
        }
        $out['links'][$key] = $clean;
    }

    return ['data' => $out];
}

function island_related_links_save(array $data): bool
{
    $path = island_related_links_data_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) {
            return false;
        }
    }
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) {
        return false;
    }
    return @file_put_contents($path, $json) !== false;
}
