<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Seed the fixed pages linked in the footer. A page that already exists (same slug) is left as it is, so the text
     * edited in the admin is never overwritten; delete the page to get the original back.
     */
    public function run(): void
    {
        foreach ($this->pages() as $order => $page) {
            Page::firstOrCreate(
                ['slug' => $page['slug']],
                ['title' => $page['title'], 'content' => $page['content'], 'sort_order' => $order + 1, 'is_published' => true],
            );
        }
    }

    /**
     * @return list<array{slug: string, title: string, content: string}>
     */
    private function pages(): array
    {
        $contact = config('site.contact');
        $phone = $contact['phone'];
        $phoneLabel = substr($phone, 0, 4).' '.substr($phone, 4, 3).' '.substr($phone, 7);

        $contactList = '<ul>'
            .'<li>Email: <a href="mailto:'.$contact['email'].'">'.$contact['email'].'</a></li>'
            .'<li>Điện thoại: <a href="tel:'.$phone.'">'.$phoneLabel.'</a></li>'
            .'<li>Facebook: <a href="https://facebook.com/'.$contact['facebook'].'">facebook.com/'.$contact['facebook'].'</a></li>'
            .'</ul>';

        return [
            [
                'slug' => 'huong-dan-hoc',
                'title' => 'Hướng dẫn học',
                'content' => <<<HTML
<p>Chào bạn! Đây là những bước đơn giản giúp bạn học tiếng Anh trên FunEnglish thật vui và nhớ lâu.</p>

<h2>Bước 1: Chọn khóa học</h2>
<p>Vào mục <strong>Khóa học</strong> và chọn khóa phù hợp với trình độ của bạn. Mỗi khóa học được chia thành nhiều unit, mỗi unit có nhiều bài học nhỏ. Bạn chỉ cần học từng bài một, từ trên xuống dưới.</p>

<h2>Bước 2: Học từ vựng trong bài</h2>
<p>Ở mỗi bài học, bạn sẽ gặp danh sách từ vựng kèm nghĩa tiếng Việt, phiên âm và ví dụ. Hãy làm theo cách sau:</p>
<ul>
<li>Nghe phát âm của từ (giọng Mỹ hoặc giọng Anh) và đọc theo thật to.</li>
<li>Đọc câu ví dụ để hiểu cách dùng từ trong câu.</li>
<li>Lật flashcard để tự kiểm tra xem bạn đã nhớ nghĩa của từ chưa.</li>
</ul>

<h2>Bước 3: Ôn tập ngay sau khi học</h2>
<p>Sau khi học xong, bấm <strong>Ôn tập ngay</strong> để làm các bài tập nhỏ về từ vừa học. Ôn tập ngay khi từ còn mới là cách tốt nhất để ghi nhớ lâu. Nếu làm sai, bạn đừng lo, hãy làm lại cho đến khi nhớ nhé!</p>

<h2>Bước 4: Chơi game để nhớ từ</h2>
<p>Mỗi bài học có các trò chơi như <strong>Soldier and Monster</strong> và <strong>Galaxy Defender</strong>. Bạn gõ đúng từ tiếng Anh theo nghĩa tiếng Việt để hạ quái vật hoặc bắn hạ thiên thạch. Chơi càng nhanh, bạn càng lên cao trong bảng xếp hạng của bài học.</p>

<h2>Học thêm với Từ vựng và Flashcard</h2>
<ul>
<li><strong>Từ vựng:</strong> xem các bộ từ theo chủ đề và tra nhanh nghĩa, phiên âm, cách đọc của từng từ.</li>
<li><strong>Flashcard:</strong> luyện nhớ từ bất cứ lúc nào, không cần mở bài học.</li>
<li><strong>Blog tiếng Anh:</strong> đọc các mẹo học tập và kinh nghiệm hay.</li>
</ul>

<h2>Mẹo để học hiệu quả</h2>
<ul>
<li>Học đều mỗi ngày khoảng 15 đến 20 phút tốt hơn học dồn một buổi dài.</li>
<li>Đọc to từng từ thay vì chỉ nhìn bằng mắt.</li>
<li>Sau mỗi bài, chơi một game để ôn lại từ vừa học.</li>
<li>Khi chơi game gõ từ, hãy tắt bộ gõ tiếng Việt để gõ chính xác hơn.</li>
</ul>

<h2>Cần giúp đỡ?</h2>
<p>Nếu bạn gặp khó khăn khi học, hãy liên hệ với chúng mình:</p>
$contactList
HTML,
            ],
            [
                'slug' => 'cau-hoi-thuong-gap',
                'title' => 'Câu hỏi thường gặp',
                'content' => <<<HTML
<p>Dưới đây là những câu hỏi bạn thường hỏi khi bắt đầu học trên FunEnglish. Nếu chưa tìm thấy câu trả lời, bạn cứ liên hệ với chúng mình nhé.</p>

<h2>FunEnglish dành cho ai?</h2>
<p>FunEnglish là website học tiếng Anh dành cho học sinh. Bạn học từ vựng qua bài học, flashcard và các trò chơi vui, theo từng bước dễ nhớ.</p>

<h2>Tôi có cần tài khoản để học không?</h2>
<p>Bạn có thể xem các bộ từ vựng và đọc blog mà không cần đăng nhập. Để học các khóa học, lưu tiến độ và có tên trên bảng xếp hạng, bạn cần đăng nhập vào tài khoản của mình.</p>

<h2>Khóa học có mất phí không?</h2>
<p>FunEnglish có cả khóa học miễn phí và khóa học có phí. Giá của từng khóa học được ghi rõ ở trang khóa học đó. Muốn học khóa có phí, bạn bấm <strong>Đăng ký khóa học</strong> ở trang khóa học.</p>

<h2>Tôi có thể học trên điện thoại không?</h2>
<p>Có. Website hiển thị tốt trên điện thoại, máy tính bảng và máy tính. Tuy nhiên các trò chơi gõ từ sẽ thoải mái hơn khi bạn dùng bàn phím máy tính.</p>

<h2>Vì sao tôi gõ từ trong game mà không nhận?</h2>
<p>Hãy kiểm tra xem bạn đã bấm vào khung game để bắt đầu gõ chưa. Bạn cũng nên tắt bộ gõ tiếng Việt (Telex, VNI) khi chơi để các chữ cái được nhận chính xác.</p>

<h2>Tôi không nghe thấy âm thanh thì làm sao?</h2>
<p>Bạn hãy kiểm tra loa hoặc tai nghe và âm lượng của thiết bị. Với các trò chơi, bạn cũng xem nút loa ở góc màn hình có đang tắt tiếng không. Trình duyệt chỉ phát âm thanh sau khi bạn đã bấm vào trang, nên hãy thử bấm nút bắt đầu rồi nghe lại.</p>

<h2>Thời gian chơi game được tính như thế nào?</h2>
<p>Thời gian của bạn được tính từ lúc bấm bắt đầu cho đến khi bạn hoàn thành tất cả các từ. Thời gian nhanh nhất của bạn sẽ được lưu vào bảng xếp hạng của bài học, nếu bạn đã đăng nhập.</p>

<h2>Tôi cần hỗ trợ thêm thì liên hệ ở đâu?</h2>
<p>Bạn có thể liên hệ với chúng mình qua:</p>
$contactList
HTML,
            ],
            [
                'slug' => 'danh-cho-phu-huynh',
                'title' => 'Dành cho phụ huynh',
                'content' => <<<HTML
<p>Kính chào quý phụ huynh! FunEnglish là website giúp các em học sinh học tiếng Anh theo cách nhẹ nhàng, vui vẻ và đều đặn mỗi ngày. Trang này giới thiệu cách các em học và cách quý phụ huynh có thể đồng hành cùng con.</p>

<h2>Con học như thế nào trên FunEnglish?</h2>
<ul>
<li><strong>Học theo khóa:</strong> mỗi khóa học chia thành các unit và bài học nhỏ, các em học lần lượt từng bài.</li>
<li><strong>Từ vựng có phát âm:</strong> mỗi từ có nghĩa tiếng Việt, phiên âm, ví dụ và âm thanh đọc mẫu.</li>
<li><strong>Ôn tập ngay sau bài học:</strong> giúp các em nhớ từ vừa học thay vì chỉ đọc qua.</li>
<li><strong>Flashcard và trò chơi:</strong> biến việc ghi nhớ từ vựng thành những lần chơi ngắn, tạo hứng thú cho các em.</li>
</ul>

<h2>Theo dõi quá trình học của con</h2>
<p>Trong mục <strong>Hồ sơ</strong> của tài khoản, phần <strong>Thành tích học tập</strong> cho biết số bài học đã hoàn thành, số từ vựng đã học, tổng điểm và tiến độ của từng khóa học. Quý phụ huynh có thể cùng con xem lại phần này mỗi tuần để khen ngợi và động viên các em.</p>
<p>Ở phần <strong>Thông tin cá nhân</strong>, quý phụ huynh cũng có thể điền email và số điện thoại của phụ huynh để chúng mình tiện liên lạc khi cần.</p>

<h2>Lời khuyên để đồng hành cùng con</h2>
<ul>
<li>Cùng con đặt lịch học cố định, khoảng 15 đến 20 phút mỗi ngày.</li>
<li>Khuyến khích con đọc to từ vựng và nói lại các câu ví dụ.</li>
<li>Hỏi con hôm nay học được từ mới nào, và để con tự giải thích nghĩa cho bố mẹ.</li>
<li>Khen ngợi sự cố gắng của con hơn là điểm số hay thứ hạng.</li>
<li>Giới hạn thời gian chơi game, ưu tiên học bài và ôn tập trước.</li>
</ul>

<h2>Khóa học và chi phí</h2>
<p>FunEnglish có cả khóa học miễn phí và khóa học có phí. Giá được ghi rõ ở từng trang khóa học. Quý phụ huynh nên cho con học thử các khóa học miễn phí trước để xem con có hứng thú không.</p>

<h2>Liên hệ với chúng mình</h2>
<p>Nếu quý phụ huynh có thắc mắc hoặc góp ý, xin vui lòng liên hệ:</p>
$contactList
HTML,
            ],
        ];
    }
}
