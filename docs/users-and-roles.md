# Tài khoản và kiểm tra phân quyền

Năm tài khoản Lab 1 đã được tạo và đăng nhập thử qua API xác thực WordPress:

| Tài khoản | Vai trò | Đăng nhập |
|---|---|---|
| user1 | Administrator | Thành công |
| user2 | Editor | Thành công |
| user3 | Author | Thành công |
| user4 | Contributor | Thành công |
| user5 | Subscriber | Thành công |

## So sánh quyền thực tế

Ký hiệu: ✓ được phép, — không được phép.

| Chức năng | Administrator | Editor | Author | Contributor | Subscriber |
|---|:---:|:---:|:---:|:---:|:---:|
| Cấu hình website | ✓ | — | — | — | — |
| Sửa bài của người khác | ✓ | ✓ | — | — | — |
| Đăng bài | ✓ | ✓ | ✓ | — | — |
| Viết/sửa bài của mình | ✓ | ✓ | ✓ | ✓ | — |
| Xóa bài đã đăng của mình | ✓ | ✓ | ✓ | — | — |
| Tải media | ✓ | ✓ | ✓ | — | — |
| Kiểm duyệt bình luận | ✓ | ✓ | — | — | — |
| Đọc trang quản trị | ✓ | ✓ | ✓ | ✓ | ✓ |

Kết quả được lấy bằng `wp_signon()` cho từng tài khoản và kiểm tra `current_user_can()` trên phiên người dùng tương ứng.

Mật khẩu không được lưu trong tài liệu hoặc lịch sử Git. Có thể đặt lại đồng loạt bằng biến môi trường `LAB1_SEED_PASSWORD` khi chạy `tools/lab1-seed.php`.

