<?php
/** Verify the database state required by Lab 1. */

declare(strict_types=1);

require dirname(__DIR__) . '/wp-load.php';

$errors = [];
$checks = [];

$expectedCategories = [
    'cong-nghe' => 'Công nghệ',
    'du-lich-viet-nam' => 'Du lịch Việt Nam',
    'am-thuc-viet' => 'Ẩm thực Việt',
    'song-xanh' => 'Sống xanh',
    'ky-nang-su-nghiep' => 'Kỹ năng & Sự nghiệp',
];

$categoryCounts = [];
foreach ($expectedCategories as $slug => $name) {
    $term = get_term_by('slug', $slug, 'category');
    if (!$term || is_wp_error($term)) {
        $errors[] = 'Thiếu danh mục: ' . $name;
        continue;
    }
    $categoryCounts[$name] = (int) $term->count;
    if ((int) $term->count !== 2) {
        $errors[] = $name . ' không có đúng 2 bài viết.';
    }
}
$checks['categories'] = $categoryCounts;

$postIds = get_posts([
    'post_type' => 'post',
    'post_status' => 'publish',
    'numberposts' => -1,
    'fields' => 'ids',
    'meta_key' => '_lab1_seed',
    'meta_value' => '1',
]);
if (count($postIds) !== 10) {
    $errors[] = 'Số bài Lab 1 không bằng 10.';
}

$postChecks = [];
foreach ($postIds as $postId) {
    $post = get_post((int) $postId);
    preg_match_all('/<img\b/i', $post->post_content, $matches);
    $imageCount = count($matches[0]);
    $tagCount = count(wp_get_post_tags((int) $postId));
    $categoryCount = count(wp_get_post_categories((int) $postId));
    $excerptLength = mb_strlen(trim($post->post_excerpt), 'UTF-8');
    $hasThumbnail = has_post_thumbnail((int) $postId);

    if ($imageCount < 2) {
        $errors[] = $post->post_title . ': thiếu 2 ảnh trong nội dung.';
    }
    if (!$hasThumbnail) {
        $errors[] = $post->post_title . ': thiếu ảnh đại diện.';
    }
    if ($tagCount < 1) {
        $errors[] = $post->post_title . ': thiếu tag.';
    }
    if ($categoryCount !== 1) {
        $errors[] = $post->post_title . ': phải thuộc đúng 1 danh mục.';
    }
    if ($excerptLength < 80 || $excerptLength > 140) {
        $errors[] = $post->post_title . ': excerpt không gần 100 ký tự (' . $excerptLength . ').';
    }

    $postChecks[] = [
        'title' => $post->post_title,
        'images' => $imageCount,
        'featured_image' => $hasThumbnail,
        'tags' => $tagCount,
        'categories' => $categoryCount,
        'excerpt_characters' => $excerptLength,
    ];
}
$checks['posts'] = $postChecks;

$expectedRoles = [
    'user1' => 'administrator',
    'user2' => 'editor',
    'user3' => 'author',
    'user4' => 'contributor',
    'user5' => 'subscriber',
];
$actualRoles = [];
foreach ($expectedRoles as $login => $role) {
    $user = get_user_by('login', $login);
    $actualRole = $user ? ($user->roles[0] ?? '') : '';
    $actualRoles[$login] = $actualRole;
    if (!$user || $actualRole !== $role) {
        $errors[] = $login . ': vai trò không đúng.';
    }
}
$checks['users'] = $actualRoles;

$actualSettings = [
    'title' => get_option('blogname'),
    'tagline' => get_option('blogdescription'),
    'timezone' => get_option('timezone_string'),
    'date_format' => get_option('date_format'),
    'time_format' => get_option('time_format'),
];
$expectedSettings = [
    'title' => 'Góc Sống Việt',
    'tagline' => 'Tri thức, trải nghiệm và cảm hứng mỗi ngày',
    'timezone' => 'Asia/Ho_Chi_Minh',
    'date_format' => 'd/m/Y',
    'time_format' => 'H:i',
];
if ($actualSettings !== $expectedSettings) {
    $errors[] = 'Cấu hình General chưa khớp yêu cầu.';
}
$checks['settings'] = $actualSettings;

$result = [
    'status' => $errors ? 'FAILED' : 'PASSED',
    'errors' => $errors,
    'checks' => $checks,
];

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
exit($errors ? 1 : 0);

