<?php
/**
 * Seed the local WordPress installation for Lab 1.
 *
 * Run from the project root with LAB1_SEED_PASSWORD set in the environment.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$labPassword = getenv('LAB1_SEED_PASSWORD');
if (!$labPassword) {
    fwrite(STDERR, "Missing LAB1_SEED_PASSWORD.\n");
    exit(1);
}

function lab1_fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function lab1_term_id(string $name, string $slug): int
{
    $existing = term_exists($slug, 'category');
    if ($existing) {
        return (int) (is_array($existing) ? $existing['term_id'] : $existing);
    }

    $created = wp_insert_term($name, 'category', ['slug' => $slug]);
    if (is_wp_error($created)) {
        lab1_fail('Cannot create category ' . $name . ': ' . $created->get_error_message());
    }

    return (int) $created['term_id'];
}

function lab1_draw_wrapped_text($image, string $text, string $font, int $size, int $x, int $y, int $maxWidth, int $color): void
{
    $words = preg_split('/\s+/u', trim($text));
    $lines = [];
    $line = '';

    foreach ($words as $word) {
        $candidate = $line === '' ? $word : $line . ' ' . $word;
        $box = imagettfbbox($size, 0, $font, $candidate);
        if (($box[2] - $box[0]) > $maxWidth && $line !== '') {
            $lines[] = $line;
            $line = $word;
        } else {
            $line = $candidate;
        }
    }
    if ($line !== '') {
        $lines[] = $line;
    }

    foreach (array_slice($lines, 0, 3) as $lineText) {
        imagettftext($image, $size, 0, $x, $y, $color, $font, $lineText);
        $y += $size + 16;
    }
}

function lab1_create_image(string $title, string $category, int $number, int $postId, array $palette): array
{
    $upload = wp_upload_dir();
    if (!empty($upload['error'])) {
        lab1_fail('Upload directory error: ' . $upload['error']);
    }

    wp_mkdir_p($upload['path']);
    $filename = 'lab1-' . sanitize_title($title) . '-' . $number . '.png';
    $path = trailingslashit($upload['path']) . $filename;

    $width = 1200;
    $height = 675;
    $image = imagecreatetruecolor($width, $height);
    imageantialias($image, true);

    [$r1, $g1, $b1] = $palette[0];
    [$r2, $g2, $b2] = $palette[1];
    for ($y = 0; $y < $height; $y++) {
        $ratio = $y / $height;
        $color = imagecolorallocate(
            $image,
            (int) ($r1 + (($r2 - $r1) * $ratio)),
            (int) ($g1 + (($g2 - $g1) * $ratio)),
            (int) ($b1 + (($b2 - $b1) * $ratio))
        );
        imageline($image, 0, $y, $width, $y, $color);
    }

    $white = imagecolorallocate($image, 255, 255, 255);
    $soft = imagecolorallocatealpha($image, 255, 255, 255, 90);
    $dark = imagecolorallocate($image, 24, 35, 52);
    imagefilledellipse($image, 1030, 105, 310, 310, $soft);
    imagefilledellipse($image, 105, 590, 360, 360, $soft);
    imagefilledrectangle($image, 74, 72, 250, 119, $white);
    imagefilledrectangle($image, 74, 548, 1126, 552, $white);

    $regularFont = 'C:/Windows/Fonts/arial.ttf';
    $boldFont = 'C:/Windows/Fonts/arialbd.ttf';
    if (!is_file($regularFont) || !is_file($boldFont)) {
        lab1_fail('Arial fonts were not found for image generation.');
    }

    imagettftext($image, 20, 0, 92, 105, $dark, $boldFont, mb_strtoupper($category, 'UTF-8'));
    lab1_draw_wrapped_text($image, $title, $boldFont, 44, 74, 250, 960, $white);
    imagettftext($image, 22, 0, 76, 610, $white, $regularFont, 'GÓC SỐNG VIỆT  •  MINH HỌA ' . $number);

    if (!imagepng($image, $path, 8)) {
        imagedestroy($image);
        lab1_fail('Cannot write image: ' . $path);
    }
    imagedestroy($image);

    $attachmentId = wp_insert_attachment([
        'post_mime_type' => 'image/png',
        'post_title' => $title . ' - Minh họa ' . $number,
        'post_content' => '',
        'post_excerpt' => 'Ảnh minh họa cho bài “' . $title . '”.',
        'post_status' => 'inherit',
        'post_parent' => $postId,
    ], $path, $postId, true);

    if (is_wp_error($attachmentId)) {
        lab1_fail('Cannot register attachment: ' . $attachmentId->get_error_message());
    }

    $metadata = wp_generate_attachment_metadata((int) $attachmentId, $path);
    wp_update_attachment_metadata((int) $attachmentId, $metadata);
    update_post_meta((int) $attachmentId, '_lab1_asset', '1');
    update_post_meta((int) $attachmentId, '_wp_attachment_image_alt', $title . ' - ảnh minh họa ' . $number);

    return [
        'id' => (int) $attachmentId,
        'url' => wp_get_attachment_url((int) $attachmentId),
    ];
}

// Remove only artifacts created by an earlier run of this seeder.
$oldPostIds = get_posts([
    'post_type' => 'post',
    'post_status' => 'any',
    'numberposts' => -1,
    'fields' => 'ids',
    'meta_key' => '_lab1_seed',
    'meta_value' => '1',
]);
foreach ($oldPostIds as $oldPostId) {
    wp_delete_post((int) $oldPostId, true);
}

$oldAttachmentIds = get_posts([
    'post_type' => 'attachment',
    'post_status' => 'any',
    'numberposts' => -1,
    'fields' => 'ids',
    'meta_key' => '_lab1_asset',
    'meta_value' => '1',
]);
foreach ($oldAttachmentIds as $oldAttachmentId) {
    wp_delete_attachment((int) $oldAttachmentId, true);
}

// Remove the stock sample post from a fresh WordPress installation.
$hello = get_page_by_path('hello-world', OBJECT, 'post');
if ($hello && $hello->post_title === 'Hello world!') {
    wp_delete_post((int) $hello->ID, true);
}

$categorySpecs = [
    'cong-nghe' => ['name' => 'Công nghệ', 'palette' => [[24, 74, 153], [80, 159, 224]]],
    'du-lich-viet-nam' => ['name' => 'Du lịch Việt Nam', 'palette' => [[0, 121, 107], [76, 175, 80]]],
    'am-thuc-viet' => ['name' => 'Ẩm thực Việt', 'palette' => [[183, 68, 35], [255, 167, 38]]],
    'song-xanh' => ['name' => 'Sống xanh', 'palette' => [[46, 125, 50], [139, 195, 74]]],
    'ky-nang-su-nghiep' => ['name' => 'Kỹ năng & Sự nghiệp', 'palette' => [[94, 53, 177], [171, 71, 188]]],
];

$categoryIds = [];
foreach ($categorySpecs as $slug => $spec) {
    $categoryIds[$slug] = lab1_term_id($spec['name'], $slug);
}
update_option('default_category', $categoryIds['cong-nghe']);

$uncategorized = get_term_by('slug', 'uncategorized', 'category');
if ($uncategorized && !in_array((int) $uncategorized->term_id, $categoryIds, true)) {
    wp_delete_term((int) $uncategorized->term_id, 'category');
}

$users = [
    'user1' => ['role' => 'administrator', 'email' => 'user1@lab1.local', 'name' => 'Quản trị viên Lab 1'],
    'user2' => ['role' => 'editor', 'email' => 'user2@lab1.local', 'name' => 'Biên tập viên Lab 1'],
    'user3' => ['role' => 'author', 'email' => 'user3@lab1.local', 'name' => 'Tác giả Lab 1'],
    'user4' => ['role' => 'contributor', 'email' => 'user4@lab1.local', 'name' => 'Cộng tác viên Lab 1'],
    'user5' => ['role' => 'subscriber', 'email' => 'user5@lab1.local', 'name' => 'Thành viên Lab 1'],
];

$userIds = [];
foreach ($users as $login => $spec) {
    $existingId = username_exists($login);
    if ($existingId) {
        $userId = (int) $existingId;
        wp_set_password($labPassword, $userId);
        wp_update_user([
            'ID' => $userId,
            'user_email' => $spec['email'],
            'display_name' => $spec['name'],
            'role' => $spec['role'],
        ]);
    } else {
        $userId = wp_create_user($login, $labPassword, $spec['email']);
        if (is_wp_error($userId)) {
            lab1_fail('Cannot create ' . $login . ': ' . $userId->get_error_message());
        }
        wp_update_user([
            'ID' => (int) $userId,
            'display_name' => $spec['name'],
            'role' => $spec['role'],
        ]);
    }
    $userIds[$login] = (int) $userId;
}

$posts = [
    [
        'category' => 'cong-nghe',
        'title' => '5 thói quen bảo mật số ai cũng nên biết',
        'excerpt' => 'Năm thói quen đơn giản giúp bảo vệ tài khoản, dữ liệu cá nhân và thiết bị an toàn hơn mỗi ngày.',
        'tags' => ['bảo mật', 'công nghệ', 'an toàn số'],
        'author' => 'user3',
        'intro' => 'An toàn số không bắt đầu từ những công cụ phức tạp mà từ các thói quen nhỏ được duy trì đều đặn. Mỗi tài khoản cá nhân đều có thể trở thành cửa ngõ dẫn đến email, hình ảnh, tài liệu và thông tin thanh toán, vì vậy một lớp bảo vệ chủ động luôn đáng giá.',
        'detail' => 'Hãy dùng mật khẩu riêng cho từng dịch vụ, ưu tiên trình quản lý mật khẩu và bật xác thực hai lớp ở nơi hỗ trợ. Khi nhận đường dẫn lạ, cần kiểm tra kỹ tên miền, người gửi và mục đích trước khi đăng nhập hoặc tải tệp. Cập nhật hệ điều hành, trình duyệt và ứng dụng định kỳ cũng giúp vá những lỗ hổng đã được phát hiện.',
        'closing' => 'Cuối cùng, nên sao lưu dữ liệu quan trọng theo nguyên tắc có nhiều bản trên các thiết bị hoặc dịch vụ khác nhau. Dành vài phút mỗi tháng để rà soát phiên đăng nhập và quyền của ứng dụng sẽ giúp phát hiện sớm hoạt động bất thường.',
    ],
    [
        'category' => 'cong-nghe',
        'title' => 'Dùng AI như một trợ lý học tập hiệu quả',
        'excerpt' => 'Cách đặt câu hỏi, kiểm chứng và biến phản hồi của AI thành kế hoạch học tập chủ động, có trách nhiệm.',
        'tags' => ['trí tuệ nhân tạo', 'học tập', 'năng suất'],
        'author' => 'user3',
        'intro' => 'AI phát huy giá trị tốt nhất khi đóng vai trò người gợi mở thay vì làm thay toàn bộ bài tập. Người học nên bắt đầu bằng mục tiêu rõ ràng, nêu trình độ hiện tại và yêu cầu giải thích theo từng bước để nhận được phản hồi phù hợp.',
        'detail' => 'Một câu hỏi tốt thường có bối cảnh, nhiệm vụ và tiêu chí đầu ra. Sau khi nhận câu trả lời, hãy tự diễn đạt lại, đối chiếu với giáo trình hoặc nguồn đáng tin cậy và đánh dấu những điểm còn nghi ngờ. Có thể yêu cầu AI tạo câu hỏi luyện tập, phản biện lập luận hoặc mô phỏng một buổi phỏng vấn để tăng tính chủ động.',
        'closing' => 'Không nên đưa dữ liệu cá nhân, tài liệu mật hay nội dung chưa được phép chia sẻ vào công cụ. Kết quả cuối cùng vẫn cần thể hiện tư duy và trách nhiệm của người học; AI chỉ là chiếc bản đồ, còn hành trình hiểu bài phải do chính bạn thực hiện.',
    ],
    [
        'category' => 'du-lich-viet-nam',
        'title' => '48 giờ khám phá phố cổ Hội An',
        'excerpt' => 'Lịch trình hai ngày cân bằng giữa phố cổ, ẩm thực địa phương và những khoảng lặng bên dòng Thu Bồn.',
        'tags' => ['Hội An', 'Quảng Nam', 'du lịch'],
        'author' => 'user2',
        'intro' => 'Hội An đẹp nhất khi được khám phá bằng nhịp điệu chậm. Buổi sáng đầu tiên có thể bắt đầu với một vòng đi bộ qua những mái nhà vàng, hội quán và chợ địa phương, khi phố còn dịu nắng và chưa quá đông người.',
        'detail' => 'Buổi chiều, hãy dành thời gian ghé một xưởng thủ công, thưởng thức cao lầu hoặc cơm gà rồi ngồi bên sông Thu Bồn. Khi đèn lồng được thắp sáng, phố cổ chuyển sang một sắc thái ấm áp khác. Ngày thứ hai phù hợp cho chuyến đạp xe qua làng rau Trà Quế hoặc thăm vùng ven để cảm nhận đời sống bình dị.',
        'closing' => 'Nên mang theo bình nước cá nhân, đi giày thoải mái và tôn trọng không gian sinh hoạt của cư dân. Một lịch trình vừa phải sẽ giúp bạn quan sát nhiều chi tiết hơn, đồng thời giữ đủ năng lượng để tận hưởng ẩm thực và cảnh sắc.',
    ],
    [
        'category' => 'du-lich-viet-nam',
        'title' => 'Kinh nghiệm săn mây Đà Lạt an toàn',
        'excerpt' => 'Chuẩn bị thời tiết, phương tiện và lịch trình hợp lý để ngắm biển mây Đà Lạt trọn vẹn, an toàn.',
        'tags' => ['Đà Lạt', 'săn mây', 'kinh nghiệm du lịch'],
        'author' => 'user2',
        'intro' => 'Săn mây hấp dẫn nhờ khoảnh khắc bình minh mở ra trên thung lũng, nhưng trải nghiệm đẹp cần sự chuẩn bị cẩn thận. Trước chuyến đi, nên theo dõi dự báo mưa, độ ẩm và nhiệt độ, đồng thời hỏi thông tin đường đi từ người địa phương.',
        'detail' => 'Khởi hành sớm nhưng không nên chạy nhanh trên đường tối, trơn hoặc có sương dày. Áo ấm, giày có độ bám, đèn pin, nước uống và một ít đồ ăn nhẹ là những vật dụng thiết thực. Nếu thuê xe, cần kiểm tra phanh, lốp và nhiên liệu trước khi rời khu trung tâm.',
        'closing' => 'Tại điểm ngắm mây, hãy đứng ở khu vực an toàn, tránh mép dốc và không xả rác. Mây có thể thay đổi rất nhanh nên đừng đặt áp lực phải có được bức ảnh hoàn hảo; sự an toàn và trải nghiệm thực tế luôn quan trọng hơn.',
    ],
    [
        'category' => 'am-thuc-viet',
        'title' => 'Bí quyết nấu phở gà thanh vị tại nhà',
        'excerpt' => 'Nồi phở gà trong, thơm và cân bằng nhờ chọn nguyên liệu tươi, giữ lửa vừa và nêm nếm đúng lúc.',
        'tags' => ['phở gà', 'món Việt', 'nấu ăn'],
        'author' => 'user3',
        'intro' => 'Một tô phở gà ngon cần nước dùng trong, vị ngọt tự nhiên và hương thơm vừa đủ. Gà ta, hành tây, gừng, hành tím cùng một lượng nhỏ quế và hồi là nền tảng quen thuộc, nhưng cách xử lý nguyên liệu mới quyết định độ thanh của món ăn.',
        'detail' => 'Hãy nướng thơm hành và gừng, rửa sạch phần cháy rồi cho vào nồi. Gà nên được luộc ở lửa vừa, thường xuyên hớt bọt và vớt ra đúng lúc để thịt không khô. Sau đó tiếp tục ninh xương, nhưng tránh để nước sôi quá mạnh vì chất đạm sẽ làm nước dùng đục.',
        'closing' => 'Chỉ nêm nước mắm gần cuối để giữ mùi thơm. Khi dùng, trụng bánh phở nhanh, xếp thịt gà, hành lá và rau thơm rồi chan nước thật nóng. Một lát chanh và chút tiêu có thể làm hương vị sáng hơn mà không lấn át vị ngọt tự nhiên.',
    ],
    [
        'category' => 'am-thuc-viet',
        'title' => 'Gợi ý mâm cơm gia đình cho ngày hè',
        'excerpt' => 'Mâm cơm mùa hè nhẹ nhàng với món canh, rau, đạm vừa đủ và cách chuẩn bị tiết kiệm thời gian.',
        'tags' => ['mâm cơm', 'gia đình', 'món hè'],
        'author' => 'user2',
        'intro' => 'Ngày nóng phù hợp với mâm cơm có hương vị nhẹ, nhiều rau và cách nấu gọn gàng. Một bữa cân bằng có thể gồm canh chua hoặc canh rau, món đạm hấp hay áp chảo, đĩa rau luộc và phần trái cây theo mùa.',
        'detail' => 'Để tiết kiệm thời gian, nên sơ chế rau và ướp nguyên liệu từ trước, nhưng vẫn bảo quản đúng nhiệt độ. Ưu tiên hấp, luộc và kho nhạt giúp gian bếp bớt nóng, đồng thời giữ hương vị tự nhiên. Nước chấm nên pha vừa phải để mọi thành viên có thể tự điều chỉnh.',
        'closing' => 'Mâm cơm không cần quá nhiều món; điều quan trọng là nguyên liệu tươi, màu sắc hài hòa và khẩu phần phù hợp. Có thể thay đổi loại rau, cá hoặc thịt theo chợ mỗi ngày để bữa ăn phong phú mà không gây lãng phí.',
    ],
    [
        'category' => 'song-xanh',
        'title' => 'Bắt đầu lối sống ít rác từ căn bếp',
        'excerpt' => 'Những thay đổi nhỏ trong mua sắm, bảo quản và tận dụng thực phẩm giúp căn bếp giảm rác rõ rệt.',
        'tags' => ['sống xanh', 'giảm rác', 'căn bếp'],
        'author' => 'user3',
        'intro' => 'Căn bếp là nơi dễ nhìn thấy lượng rác phát sinh mỗi ngày, vì vậy cũng là điểm bắt đầu thuận lợi cho lối sống ít rác. Trước khi mua sắm, hãy kiểm tra tủ lạnh và lên thực đơn ngắn để tránh mua trùng hoặc để thực phẩm quá hạn.',
        'detail' => 'Mang theo túi, hộp và chai dùng nhiều lần khi phù hợp; ưu tiên sản phẩm có bao bì tối giản và mua lượng vừa đủ. Rau củ nên được bảo quản theo từng nhóm, ghi ngày mở hộp và đặt món cần dùng sớm ở vị trí dễ thấy. Phần thân, lá hoặc vỏ ăn được có thể tận dụng cho nước dùng và món xào.',
        'closing' => 'Với rác hữu cơ còn lại, có thể ủ phân nếu điều kiện gia đình cho phép. Đừng cố thay đổi mọi thứ trong một ngày; theo dõi một tuần, chọn nguồn rác lớn nhất rồi cải thiện từng bước sẽ tạo thói quen bền vững hơn.',
    ],
    [
        'category' => 'song-xanh',
        'title' => 'Thiết kế ban công xanh cho nhà phố',
        'excerpt' => 'Chọn cây, chậu và cách tưới phù hợp để tạo một góc ban công xanh mát, gọn gàng và dễ chăm sóc.',
        'tags' => ['ban công', 'cây xanh', 'nhà phố'],
        'author' => 'user2',
        'intro' => 'Một ban công nhỏ vẫn có thể trở thành khoảng xanh dễ chịu nếu thiết kế theo điều kiện thực tế. Bước đầu tiên là quan sát số giờ nắng, hướng gió, khả năng thoát nước và tải trọng trước khi chọn cây hoặc bố trí chậu.',
        'detail' => 'Cây ưa nắng nên đặt ở mép ngoài, cây chịu bóng ở phía trong; kệ đứng và chậu treo giúp tận dụng chiều cao. Chọn ít giống cây nhưng lặp lại màu sắc sẽ tạo cảm giác gọn gàng. Mỗi chậu cần lỗ thoát nước, đĩa hứng phù hợp và khoảng trống để dễ vệ sinh.',
        'closing' => 'Nên tưới vào sáng sớm, kiểm tra độ ẩm đất thay vì tưới theo cảm tính và cắt lá hỏng thường xuyên. Một chiếc ghế nhỏ cùng ánh sáng ấm có thể hoàn thiện góc thư giãn, miễn là lối đi và lan can luôn được giữ an toàn.',
    ],
    [
        'category' => 'ky-nang-su-nghiep',
        'title' => 'Lập kế hoạch tuần hiệu quả trong 30 phút',
        'excerpt' => 'Một quy trình ngắn giúp xác định ưu tiên, chia lịch hợp lý và giữ khoảng trống cho việc phát sinh.',
        'tags' => ['lập kế hoạch', 'quản lý thời gian', 'năng suất'],
        'author' => 'user3',
        'intro' => 'Kế hoạch tuần tốt không phải là danh sách kín đặc mà là bản đồ cho những việc quan trọng. Hãy bắt đầu bằng việc xem lại tuần cũ, ghi nhận nhiệm vụ dang dở, lịch hẹn cố định và ba kết quả có ý nghĩa nhất cần đạt trong tuần mới.',
        'detail' => 'Chia mỗi mục tiêu thành hành động có thể hoàn thành trong một phiên làm việc, sau đó đặt chúng vào những khung giờ phù hợp với năng lượng. Gom các việc nhỏ cùng loại để giảm chuyển đổi chú ý. Chỉ nên lấp khoảng bảy mươi phần trăm lịch, giữ phần còn lại cho nghỉ ngơi và tình huống phát sinh.',
        'closing' => 'Mỗi ngày dành năm phút để rà soát và điều chỉnh thay vì cố bám một kế hoạch đã lỗi thời. Cuối tuần, tự hỏi điều gì tạo ra kết quả, điều gì nên bỏ bớt và một thay đổi nhỏ nào sẽ giúp tuần sau nhẹ nhàng hơn.',
    ],
    [
        'category' => 'ky-nang-su-nghiep',
        'title' => 'Kỹ năng thuyết trình tự tin trước đám đông',
        'excerpt' => 'Chuẩn bị thông điệp, luyện tập và tương tác đúng cách để bài thuyết trình rõ ràng, thuyết phục hơn.',
        'tags' => ['thuyết trình', 'giao tiếp', 'sự nghiệp'],
        'author' => 'user2',
        'intro' => 'Sự tự tin khi thuyết trình thường đến từ chuẩn bị tốt hơn là năng khiếu bẩm sinh. Trước tiên, hãy xác định một thông điệp chính mà khán giả cần nhớ, rồi sắp xếp nội dung thành mở đầu, các luận điểm có dẫn chứng và phần kết rõ ràng.',
        'detail' => 'Slide nên hỗ trợ lời nói bằng hình ảnh hoặc từ khóa, không biến thành trang tài liệu dày chữ. Luyện tập thành tiếng, bấm giờ và ghi hình giúp nhận ra tốc độ nói, từ đệm và cử chỉ chưa tự nhiên. Khi trình bày, hãy nhìn từng khu vực trong phòng và dành khoảng dừng ngắn sau ý quan trọng.',
        'closing' => 'Nếu hồi hộp, hãy hít thở chậm và tập trung vào giá trị muốn chia sẻ thay vì cố đạt sự hoàn hảo. Chuẩn bị trước vài câu hỏi thường gặp cũng giúp phần trao đổi chủ động hơn. Mỗi lần trình bày là một vòng luyện tập để lần sau tiến bộ.',
    ],
];

$createdPosts = [];
foreach ($posts as $index => $spec) {
    $postId = wp_insert_post([
        'post_title' => $spec['title'],
        'post_excerpt' => $spec['excerpt'],
        'post_status' => 'draft',
        'post_type' => 'post',
        'post_author' => $userIds[$spec['author']],
        'post_category' => [$categoryIds[$spec['category']]],
    ], true);
    if (is_wp_error($postId)) {
        lab1_fail('Cannot create post ' . $spec['title'] . ': ' . $postId->get_error_message());
    }

    $category = $categorySpecs[$spec['category']];
    $imageOne = lab1_create_image($spec['title'], $category['name'], 1, (int) $postId, $category['palette']);
    $imageTwo = lab1_create_image($spec['title'], $category['name'], 2, (int) $postId, array_reverse($category['palette']));

    $content = '<p>' . esc_html($spec['intro']) . '</p>'
        . '<figure class="wp-block-image size-large"><img src="' . esc_url($imageOne['url']) . '" alt="' . esc_attr($spec['title'] . ' - ảnh minh họa 1') . '"/><figcaption>Góc nhìn chính của chủ đề ' . esc_html($spec['title']) . '.</figcaption></figure>'
        . '<h2>Những điểm nên áp dụng</h2>'
        . '<p>' . esc_html($spec['detail']) . '</p>'
        . '<figure class="wp-block-image size-large"><img src="' . esc_url($imageTwo['url']) . '" alt="' . esc_attr($spec['title'] . ' - ảnh minh họa 2') . '"/><figcaption>Gợi ý thực hành dành cho người đọc.</figcaption></figure>'
        . '<h2>Bắt đầu từ bước nhỏ</h2>'
        . '<p>' . esc_html($spec['closing']) . '</p>';

    $updated = wp_update_post([
        'ID' => (int) $postId,
        'post_content' => $content,
        'post_status' => 'publish',
    ], true);
    if (is_wp_error($updated)) {
        lab1_fail('Cannot publish post ' . $spec['title'] . ': ' . $updated->get_error_message());
    }

    wp_set_post_tags((int) $postId, $spec['tags'], false);
    set_post_thumbnail((int) $postId, $imageOne['id']);
    update_post_meta((int) $postId, '_lab1_seed', '1');
    $createdPosts[] = ['id' => (int) $postId, 'title' => $spec['title']];
}

update_option('blogname', 'Góc Sống Việt');
update_option('blogdescription', 'Tri thức, trải nghiệm và cảm hứng mỗi ngày');
update_option('timezone_string', 'Asia/Ho_Chi_Minh');
update_option('gmt_offset', 7);
update_option('date_format', 'd/m/Y');
update_option('time_format', 'H:i');
update_option('WPLANG', 'vi');

$capabilities = [
    'manage_options' => 'Cấu hình website',
    'edit_others_posts' => 'Sửa bài của người khác',
    'publish_posts' => 'Đăng bài',
    'edit_posts' => 'Viết/sửa bài của mình',
    'delete_published_posts' => 'Xóa bài đã đăng của mình',
    'upload_files' => 'Tải media',
    'moderate_comments' => 'Kiểm duyệt bình luận',
    'read' => 'Đọc trang quản trị',
];

$roleChecks = [];
foreach ($users as $login => $spec) {
    wp_set_current_user(0);
    $signedIn = wp_signon([
        'user_login' => $login,
        'user_password' => $labPassword,
        'remember' => false,
    ], false);
    if (is_wp_error($signedIn)) {
        lab1_fail('Authentication failed for ' . $login . ': ' . $signedIn->get_error_message());
    }

    wp_set_current_user((int) $signedIn->ID);
    $checks = [];
    foreach ($capabilities as $capability => $label) {
        $checks[$capability] = current_user_can($capability);
    }
    $roleChecks[$login] = [
        'authenticated' => true,
        'role' => $spec['role'],
        'capabilities' => $checks,
    ];
}
wp_set_current_user(0);

$result = [
    'categories_created' => count($categoryIds),
    'posts_created' => count($createdPosts),
    'users_created_or_updated' => count($userIds),
    'attachments_created' => count($createdPosts) * 2,
    'settings' => [
        'title' => get_option('blogname'),
        'tagline' => get_option('blogdescription'),
        'timezone' => get_option('timezone_string'),
        'date_format' => get_option('date_format'),
        'time_format' => get_option('time_format'),
    ],
    'authentication_and_capabilities' => $roleChecks,
];

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

