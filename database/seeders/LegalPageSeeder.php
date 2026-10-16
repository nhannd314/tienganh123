<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class LegalPageSeeder extends Seeder
{
    /**
     * Seed the privacy policy and terms of service, each in Vietnamese then English. They are the URLs given to Google
     * on the OAuth consent screen, and are listed after the other fixed pages in the footer. A page that already exists
     * (same slug) is left as it is, so the text edited in the admin is never overwritten.
     */
    public function run(): void
    {
        foreach ($this->pages() as $order => $page) {
            Page::firstOrCreate(
                ['slug' => $page['slug']],
                ['title' => $page['title'], 'content' => $page['content'], 'sort_order' => $order + 10, 'is_published' => true],
            );
        }
    }

    /**
     * @return list<array{slug: string, title: string, content: string}>
     */
    private function pages(): array
    {
        $email = config('site.contact.email');
        $mailLink = '<a href="mailto:'.$email.'">'.$email.'</a>';
        $siteLink = '<a href="'.config('app.url').'">hoctienganh.pro.vn</a>';
        $effectiveDate = '16/10/2026';

        return [
            [
                'slug' => 'chinh-sach-bao-mat',
                'title' => 'Chính sách bảo mật',
                'content' => <<<HTML
<p><em>Ngày hiệu lực / Effective date: {$effectiveDate}</em></p>
<p><em>English version below.</em></p>

<h2>Chính sách bảo mật</h2>
<p>Chính sách này giải thích HọcTiếngAnh ({$siteLink}) thu thập những thông tin nào khi bạn sử dụng website, dùng chúng vào việc gì và bạn có những quyền gì đối với thông tin của mình.</p>

<h3>1. Thông tin chúng mình thu thập</h3>
<ul>
<li><strong>Thông tin tài khoản:</strong> họ tên, email và mật khẩu (được mã hóa, chúng mình không đọc được mật khẩu của bạn).</li>
<li><strong>Thông tin hồ sơ bạn tự điền thêm:</strong> số điện thoại, ảnh đại diện, ngày sinh, lớp, trường, email và số điện thoại của phụ huynh.</li>
<li><strong>Khi đăng nhập bằng Google:</strong> chúng mình chỉ nhận họ tên, địa chỉ email và mã định danh tài khoản Google của bạn. Chúng mình không nhận mật khẩu Google và không truy cập Gmail, Drive hay bất kỳ dữ liệu nào khác trong tài khoản Google của bạn.</li>
<li><strong>Kết quả học tập:</strong> các bài đã học, điểm ôn tập, điểm và thứ hạng trong các trò chơi.</li>
<li><strong>Giọng đọc khi luyện phát âm:</strong> đoạn ghi âm được gửi tới dịch vụ chấm phát âm để tính điểm rồi bỏ đi, chúng mình không lưu lại file ghi âm.</li>
<li><strong>Thông tin thanh toán:</strong> số tiền, mã giao dịch và thời gian thanh toán. Bạn thanh toán bằng chuyển khoản ngân hàng nên chúng mình không thu thập số thẻ hay thông tin đăng nhập ngân hàng của bạn.</li>
<li><strong>Cookie:</strong> chúng mình chỉ dùng cookie cần thiết để giữ bạn ở trạng thái đăng nhập và bảo vệ website, không dùng cookie quảng cáo.</li>
</ul>

<h3>2. Chúng mình dùng thông tin vào việc gì</h3>
<ul>
<li>Tạo và duy trì tài khoản, cho bạn đăng nhập.</li>
<li>Lưu tiến độ học, chấm điểm và hiển thị bảng xếp hạng.</li>
<li>Kích hoạt khóa học sau khi bạn thanh toán.</li>
<li>Liên hệ với bạn hoặc phụ huynh khi cần hỗ trợ.</li>
<li>Cải thiện nội dung và bảo vệ website khỏi việc sử dụng sai mục đích.</li>
</ul>
<p>Chúng mình <strong>không bán</strong> và <strong>không cho thuê</strong> thông tin cá nhân của bạn cho bất kỳ ai.</p>

<h3>3. Dịch vụ bên thứ ba</h3>
<p>Để vận hành website, chúng mình dùng một số dịch vụ bên ngoài. Mỗi dịch vụ chỉ nhận phần thông tin cần thiết cho việc của nó:</p>
<ul>
<li><strong>Google:</strong> đăng nhập bằng tài khoản Google.</li>
<li><strong>Microsoft Azure:</strong> chấm điểm phát âm và tạo giọng đọc.</li>
<li><strong>SePay:</strong> xác nhận giao dịch chuyển khoản ngân hàng.</li>
<li><strong>Nhà cung cấp máy chủ:</strong> nơi lưu trữ website và cơ sở dữ liệu.</li>
</ul>
<p>Chúng mình cũng có thể cung cấp thông tin khi cơ quan nhà nước có thẩm quyền yêu cầu theo quy định của pháp luật.</p>

<h3>4. Trẻ em</h3>
<p>Website dành cho các bạn nhỏ học tiếng Anh. Chúng mình khuyến khích phụ huynh tạo tài khoản, đồng ý thay cho con và theo dõi việc học của con. Nếu phụ huynh muốn xem, sửa hoặc xóa thông tin của con, xin vui lòng liên hệ với chúng mình.</p>

<h3>5. Lưu giữ và bảo vệ thông tin</h3>
<p>Thông tin được lưu trong thời gian tài khoản còn hoạt động. Thông tin thanh toán có thể được giữ lâu hơn theo quy định về kế toán. Chúng mình dùng kết nối mã hóa (HTTPS), mã hóa mật khẩu và giới hạn quyền truy cập dữ liệu để bảo vệ thông tin của bạn.</p>

<h3>6. Quyền của bạn</h3>
<p>Bạn có thể xem và sửa thông tin của mình trong trang hồ sơ. Bạn cũng có quyền yêu cầu chúng mình cung cấp bản sao, chỉnh sửa hoặc xóa thông tin cá nhân và xóa tài khoản bằng cách gửi email tới {$mailLink}. Chúng mình sẽ phản hồi trong vòng 7 ngày làm việc.</p>

<h3>7. Thay đổi chính sách</h3>
<p>Khi chính sách này thay đổi, chúng mình sẽ cập nhật trên trang này và ghi lại ngày hiệu lực mới. Nếu thay đổi quan trọng, chúng mình sẽ thông báo qua email hoặc trên website.</p>

<h3>8. Liên hệ</h3>
<p>Mọi câu hỏi về chính sách bảo mật, xin gửi email tới {$mailLink}.</p>

<hr>

<h2>Privacy Policy</h2>
<p>This policy explains what information HọcTiếngAnh ({$siteLink}) collects when you use the website, how we use it, and the rights you have over it.</p>

<h3>1. Information we collect</h3>
<ul>
<li><strong>Account information:</strong> your name, email address and password (stored hashed; we cannot read it).</li>
<li><strong>Profile details you choose to add:</strong> phone number, profile picture, date of birth, grade, school, and a parent's email address and phone number.</li>
<li><strong>When you sign in with Google:</strong> we only receive your name, email address and Google account ID. We never receive your Google password and we do not access your Gmail, Drive or any other data in your Google account.</li>
<li><strong>Learning results:</strong> lessons completed, review scores, and game scores and rankings.</li>
<li><strong>Voice recordings for pronunciation practice:</strong> the recording is sent to a speech scoring service to calculate a score and then discarded; we do not keep audio files.</li>
<li><strong>Payment information:</strong> the amount, transaction reference and time of payment. You pay by bank transfer, so we never collect card numbers or your online banking credentials.</li>
<li><strong>Cookies:</strong> we only use the cookies needed to keep you signed in and to protect the website. We do not use advertising cookies.</li>
</ul>

<h3>2. How we use your information</h3>
<ul>
<li>To create and maintain your account and let you sign in.</li>
<li>To save your learning progress, score your exercises and show leaderboards.</li>
<li>To activate courses after you pay.</li>
<li>To contact you or your parent when support is needed.</li>
<li>To improve our content and protect the website from misuse.</li>
</ul>
<p>We do <strong>not sell</strong> or <strong>rent</strong> your personal information to anyone.</p>

<h3>3. Third-party services</h3>
<p>We rely on a few outside services to run the website. Each one receives only the information it needs to do its job:</p>
<ul>
<li><strong>Google:</strong> sign-in with a Google account.</li>
<li><strong>Microsoft Azure:</strong> pronunciation scoring and text-to-speech.</li>
<li><strong>SePay:</strong> confirming bank transfer payments.</li>
<li><strong>Our hosting provider:</strong> where the website and its database are stored.</li>
</ul>
<p>We may also disclose information when required to by a competent authority under applicable law.</p>

<h3>4. Children</h3>
<p>The website is built for children learning English. We encourage parents to create the account, give consent on their child's behalf and follow their child's learning. Parents who want to review, correct or delete their child's information can contact us at any time.</p>

<h3>5. Retention and security</h3>
<p>We keep your information for as long as your account is active. Payment records may be kept longer where accounting rules require it. We protect your information with encrypted connections (HTTPS), hashed passwords and restricted access to data.</p>

<h3>6. Your rights</h3>
<p>You can view and update your information on your profile page. You may also ask us for a copy of your personal information, ask us to correct or delete it, or ask us to delete your account by emailing {$mailLink}. We will reply within 7 business days.</p>

<h3>7. Changes to this policy</h3>
<p>When this policy changes, we will update this page and its effective date. For significant changes we will also let you know by email or on the website.</p>

<h3>8. Contact</h3>
<p>For any question about this privacy policy, please email {$mailLink}.</p>
HTML,
            ],
            [
                'slug' => 'dieu-khoan-su-dung',
                'title' => 'Điều khoản sử dụng',
                'content' => <<<HTML
<p><em>Ngày hiệu lực / Effective date: {$effectiveDate}</em></p>
<p><em>English version below.</em></p>

<h2>Điều khoản sử dụng</h2>
<p>Chào mừng bạn đến với HọcTiếngAnh ({$siteLink}). Khi tạo tài khoản hoặc sử dụng website, bạn đồng ý với các điều khoản dưới đây. Nếu người dùng là trẻ em, phụ huynh hoặc người giám hộ là người đồng ý thay cho con.</p>

<h3>1. Dịch vụ</h3>
<p>HọcTiếngAnh cung cấp các khóa học tiếng Anh trực tuyến gồm bài học từ vựng, phát âm, ôn tập, flashcard và trò chơi. Một số khóa học miễn phí, một số khóa học cần mua gói học để sử dụng.</p>

<h3>2. Tài khoản</h3>
<ul>
<li>Bạn cần cung cấp thông tin chính xác khi đăng ký, bao gồm cả khi đăng nhập bằng Google.</li>
<li>Bạn có trách nhiệm giữ bí mật mật khẩu và chịu trách nhiệm về các hoạt động trên tài khoản của mình.</li>
<li>Mỗi tài khoản dành cho một người học. Không chia sẻ hoặc bán lại tài khoản cho người khác.</li>
<li>Hãy báo ngay cho chúng mình nếu bạn nghĩ tài khoản của mình bị người khác sử dụng.</li>
</ul>

<h3>3. Gói học và thanh toán</h3>
<ul>
<li>Giá và thời hạn của từng gói học được ghi rõ trước khi bạn thanh toán.</li>
<li>Bạn thanh toán bằng chuyển khoản ngân hàng theo đúng số tiền và nội dung chuyển khoản được hiển thị. Gói học được kích hoạt sau khi giao dịch được xác nhận.</li>
<li>Nếu bạn đã chuyển khoản mà gói học chưa được kích hoạt, hoặc chuyển nhầm số tiền, hãy liên hệ với chúng mình kèm thông tin giao dịch để được hỗ trợ.</li>
<li>Gói học đã kích hoạt thường không được hoàn tiền. Trường hợp website gặp lỗi khiến bạn không thể học, chúng mình sẽ xem xét gia hạn hoặc hoàn tiền.</li>
</ul>

<h3>4. Quy định sử dụng</h3>
<p>Khi sử dụng website, bạn đồng ý không:</p>
<ul>
<li>Sao chép, tải xuống hàng loạt hoặc phát tán lại nội dung bài học, hình ảnh, âm thanh của website.</li>
<li>Dùng phần mềm, công cụ tự động hoặc gian lận để thay đổi điểm số và bảng xếp hạng.</li>
<li>Tìm cách truy cập trái phép, làm gián đoạn hoặc gây hại cho website và người dùng khác.</li>
<li>Sử dụng website vào mục đích vi phạm pháp luật Việt Nam.</li>
</ul>
<p>Chúng mình có quyền tạm khóa hoặc xóa tài khoản vi phạm các quy định này.</p>

<h3>5. Quyền sở hữu nội dung</h3>
<p>Bài học, hình ảnh, âm thanh, trò chơi, logo và các nội dung khác trên website thuộc quyền sở hữu của HọcTiếngAnh hoặc được sử dụng có phép. Bạn được sử dụng nội dung cho việc học cá nhân, không dùng cho mục đích thương mại.</p>

<h3>6. Giới hạn trách nhiệm</h3>
<p>Chúng mình luôn cố gắng giữ website hoạt động ổn định và nội dung chính xác, nhưng không cam kết website không bao giờ gián đoạn hay không có lỗi. Điểm phát âm và điểm trò chơi do hệ thống tự động chấm, chỉ mang tính tham khảo. Trong phạm vi pháp luật cho phép, chúng mình không chịu trách nhiệm cho các thiệt hại gián tiếp phát sinh từ việc sử dụng website.</p>

<h3>7. Quyền riêng tư</h3>
<p>Cách chúng mình thu thập và sử dụng thông tin của bạn được trình bày trong <a href="/trang/chinh-sach-bao-mat">Chính sách bảo mật</a>.</p>

<h3>8. Thay đổi điều khoản</h3>
<p>Chúng mình có thể cập nhật các điều khoản này. Phiên bản mới có hiệu lực từ khi được đăng trên trang này. Việc bạn tiếp tục sử dụng website sau đó đồng nghĩa với việc bạn đồng ý với điều khoản mới.</p>

<h3>9. Luật áp dụng và liên hệ</h3>
<p>Các điều khoản này được điều chỉnh theo pháp luật Việt Nam. Mọi thắc mắc, xin gửi email tới {$mailLink}.</p>

<hr>

<h2>Terms of Service</h2>
<p>Welcome to HọcTiếngAnh ({$siteLink}). By creating an account or using the website, you agree to the terms below. If the user is a child, their parent or guardian agrees on the child's behalf.</p>

<h3>1. The service</h3>
<p>HọcTiếngAnh offers online English courses with vocabulary lessons, pronunciation practice, reviews, flashcards and games. Some courses are free; others require purchasing a learning plan.</p>

<h3>2. Accounts</h3>
<ul>
<li>You must provide accurate information when you register, including when you sign in with Google.</li>
<li>You are responsible for keeping your password secret and for all activity on your account.</li>
<li>Each account is for one learner. Do not share or resell your account.</li>
<li>Tell us immediately if you think someone else is using your account.</li>
</ul>

<h3>3. Plans and payment</h3>
<ul>
<li>The price and duration of each plan are shown before you pay.</li>
<li>You pay by bank transfer using the exact amount and transfer description shown. Your plan is activated once the transaction is confirmed.</li>
<li>If you have paid but your plan has not been activated, or you transferred the wrong amount, contact us with the transaction details and we will help.</li>
<li>Activated plans are generally non-refundable. If a fault on our side prevents you from learning, we will consider extending your plan or refunding you.</li>
</ul>

<h3>4. Acceptable use</h3>
<p>When using the website, you agree not to:</p>
<ul>
<li>Copy, bulk-download or redistribute the lessons, images or audio on the website.</li>
<li>Use software, bots or cheats to alter scores or leaderboards.</li>
<li>Attempt to gain unauthorised access to, disrupt or harm the website or other users.</li>
<li>Use the website for any purpose that breaks Vietnamese law.</li>
</ul>
<p>We may suspend or delete accounts that break these rules.</p>

<h3>5. Content ownership</h3>
<p>The lessons, images, audio, games, logo and other content on the website belong to HọcTiếngAnh or are used with permission. You may use the content for your own learning, not for commercial purposes.</p>

<h3>6. Limitation of liability</h3>
<p>We work to keep the website running smoothly and its content accurate, but we do not promise that it will always be available or error-free. Pronunciation and game scores are calculated automatically and are for guidance only. To the extent permitted by law, we are not liable for indirect damages arising from your use of the website.</p>

<h3>7. Privacy</h3>
<p>How we collect and use your information is described in our <a href="/trang/chinh-sach-bao-mat">Privacy Policy</a>.</p>

<h3>8. Changes to these terms</h3>
<p>We may update these terms. A new version takes effect once it is posted on this page. If you keep using the website after that, you accept the updated terms.</p>

<h3>9. Governing law and contact</h3>
<p>These terms are governed by the laws of Vietnam. For any question, please email {$mailLink}.</p>
HTML,
            ],
        ];
    }
}
