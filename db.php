<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

function getJsonPath() {
    return __DIR__ . '/news.json';
}

function countWords($text) {
    $text = strip_tags((string) $text);
    $text = preg_replace('/&nbsp;|&#160;/i', ' ', $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = trim($text);

    if ($text === '') {
        return 0;
    }

    preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);
    return count($matches[0]);
}

function slugify($text) {
    $text = trim((string) $text);
    if ($text === '') {
        return 'haber';
    }

    $text = strtr($text, [
        'İ' => 'I',
        'I' => 'i',
        'ı' => 'i',
        'Ğ' => 'G',
        'ğ' => 'g',
        'Ü' => 'U',
        'ü' => 'u',
        'Ş' => 'S',
        'ş' => 's',
        'Ö' => 'O',
        'ö' => 'o',
        'Ç' => 'C',
        'ç' => 'c',
    ]);

    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/\s+/', '-', $text);
    $text = preg_replace('/[^a-z0-9\-]/', '', $text);
    $text = preg_replace('/-+/', '-', $text);

    return trim($text, '-');
}

function ensureUniqueSlug($slug, $title, $excludeId = null) {
    $base = slugify($slug !== '' ? $slug : $title);
    if ($base === '') {
        $base = 'haber';
    }

    $candidate = $base;
    $counter = 2;
    $allNews = readNewsData();

    while (true) {
        $conflict = false;
        foreach ($allNews as $item) {
            if (isset($item['id']) && (int) $item['id'] === (int) $excludeId) {
                continue;
            }
            if (($item['slug'] ?? '') === $candidate) {
                $conflict = true;
                break;
            }
        }

        if (!$conflict) {
            return $candidate;
        }

        $candidate = $base . '-' . $counter++;
    }
}

function normalizeNewsData(array $data) {
    $seen = [];
    foreach ($data as &$item) {
        $item['views'] = isset($item['views']) ? (int) $item['views'] : 0;
        $item['positive_votes'] = isset($item['positive_votes']) ? (int) $item['positive_votes'] : 0;
        $item['negative_votes'] = isset($item['negative_votes']) ? (int) $item['negative_votes'] : 0;
        $item['featured'] = !empty($item['featured']) ? 1 : 0;

        $slug = trim((string) ($item['slug'] ?? ''));
        if ($slug === '') {
            $slug = slugify($item['title'] ?? 'haber');
        }
        $slug = slugify($slug);
        if ($slug === '') {
            $slug = 'haber';
        }

        $candidate = $slug;
        $counter = 2;
        while (in_array($candidate, $seen, true)) {
            $candidate = $slug . '-' . $counter++;
        }

        $seen[] = $candidate;
        $item['slug'] = $candidate;
    }
    unset($item);

    return $data;
}

function readNewsData() {
    $path = getJsonPath();
    if (!file_exists($path)) {
        file_put_contents($path, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    $json = file_get_contents($path);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        $data = [];
    }

    return normalizeNewsData($data);
}

function writeNewsData(array $data) {
    $path = getJsonPath();
    $normalized = normalizeNewsData(array_values($data));
    file_put_contents($path, json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function getNextNewsId(array $data) {
    $maxId = 0;
    foreach ($data as $item) {
        if (isset($item['id']) && is_numeric($item['id'])) {
            $maxId = max($maxId, (int) $item['id']);
        }
    }
    return $maxId + 1;
}

function getAllNews() {
    $data = readNewsData();
    usort($data, function ($a, $b) {
        if ($a['date'] === $b['date']) {
            return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
        }
        return strcmp($b['date'] ?? '', $a['date'] ?? '');
    });
    return $data;
}

function getFeaturedNews() {
    $data = array_filter(readNewsData(), function ($item) {
        return !empty($item['featured']);
    });
    usort($data, function ($a, $b) {
        if ($a['date'] === $b['date']) {
            return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
        }
        return strcmp($b['date'] ?? '', $a['date'] ?? '');
    });
    return $data;
}

function getNewsById($id) {
    $data = readNewsData();
    foreach ($data as $item) {
        if (isset($item['id']) && (int) $item['id'] === (int) $id) {
            return $item;
        }
    }
    return null;
}

function getNewsBySlug($slug) {
    $data = readNewsData();
    foreach ($data as $item) {
        if (($item['slug'] ?? '') === (string) $slug) {
            return $item;
        }
    }
    return null;
}

function incrementNewsViews($id) {
    $data = readNewsData();
    $updated = false;
    foreach ($data as &$item) {
        if (isset($item['id']) && (int) $item['id'] === (int) $id) {
            $item['views'] = (int) ($item['views'] ?? 0) + 1;
            $updated = true;
            break;
        }
    }
    unset($item);

    if ($updated) {
        writeNewsData($data);
    }
    return $updated;
}

function createNews(array $data) {
    $allNews = readNewsData();
    $newId = getNextNewsId($allNews);
    $newsItem = [
        'id' => $newId,
        'title' => $data['title'],
        'summary' => $data['summary'],
        'content' => $data['content'],
        'category' => $data['category'],
        'image' => $data['image'],
        'video' => $data['video'],
        'hero' => $data['hero'],
        'featured' => !empty($data['featured']) ? 1 : 0,
        'views' => 0,
        'positive_votes' => 0,
        'negative_votes' => 0,
        'date' => $data['date'],
        'slug' => ensureUniqueSlug($data['slug'] ?? '', $data['title']),
    ];
    $allNews[] = $newsItem;
    writeNewsData($allNews);
    return $newId;
}

function updateNews($id, array $data) {
    $allNews = readNewsData();
    $updated = false;
    foreach ($allNews as &$item) {
        if (isset($item['id']) && (int) $item['id'] === (int) $id) {
            $item['title'] = $data['title'];
            $item['summary'] = $data['summary'];
            $item['content'] = $data['content'];
            $item['category'] = $data['category'];
            $item['image'] = $data['image'];
            $item['video'] = $data['video'];
            $item['hero'] = $data['hero'];
            $item['featured'] = !empty($data['featured']) ? 1 : 0;
            $item['date'] = $data['date'];
            $item['slug'] = ensureUniqueSlug($data['slug'] ?? '', $data['title'], $id);
            $updated = true;
            break;
        }
    }
    unset($item);

    if ($updated) {
        writeNewsData($allNews);
    }
    return $updated;
}

function deleteNews($id) {
    $allNews = readNewsData();
    $newData = array_filter($allNews, function ($item) use ($id) {
        return !(isset($item['id']) && (int) $item['id'] === (int) $id);
    });
    $deleted = count($newData) !== count($allNews);
    if ($deleted) {
        writeNewsData($newData);
    }
    return $deleted;
}

function castNewsVote($id, $voteValue) {
    $data = readNewsData();
    $field = $voteValue === 'negative' ? 'negative_votes' : 'positive_votes';
    $updated = false;

    foreach ($data as &$item) {
        if (isset($item['id']) && (int) $item['id'] === (int) $id) {
            $item[$field] = (int) ($item[$field] ?? 0) + 1;
            $updated = true;
            break;
        }
    }
    unset($item);

    if ($updated) {
        writeNewsData($data);
    }
    return $updated;
}
