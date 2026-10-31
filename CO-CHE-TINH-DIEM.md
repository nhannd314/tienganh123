# Cơ chế tính điểm

Hệ thống có 4 loại điểm khác nhau. Tài liệu này mô tả từng loại, chỗ code xử lý và các điểm cần lưu ý.

| Loại | Dùng ở đâu | Lưu ở | Code chính |
|---|---|---|---|
| Điểm bài học (ôn tập) | Sidebar bài học, BXH | `lesson_scores` | `App\Services\LessonScoreCalculator`, `Lesson::recordScore()` |
| Bảng xếp hạng (BXH) | Trang chủ / sidebar | cache `leaderboard` | `App\Services\Leaderboard` |
| Điểm bài thi | Trang kết quả đề thi | `exam_attempts` | `App\Services\ExamPaper::grade()` |
| Thời gian chơi game | Bảng thời gian nhanh nhất | `game_scores` | `App\Models\GameScore` |

---

## 1. Điểm bài học (ôn tập)

### 1.1 Luồng dữ liệu

1. Người học hoàn thành một phần ôn tập (1–11). Trình duyệt gửi đáp án tới `LessonReviewController::store()`.
2. Server **tự chấm lại** đáp án, không tin kết quả từ trình duyệt. Với các phần đọc to (5, 6, 10) điểm lấy từ session do Azure chấm (`pronounce()`).
3. Mỗi lần nộp tạo một bản ghi `LessonReview` (`total`, `correct`, `duration_seconds`, `answers`). Khách chưa đăng nhập vẫn có bản ghi nhưng `user_id = null`, và không có điểm.
4. Nếu đã đăng nhập, `Lesson::recordScore()` tính lại điểm từ toàn bộ tiến độ (`Lesson::reviewProgress()`), rồi **chỉ giữ điểm cao nhất** trong `lesson_scores`.

### 1.2 Tiến độ (`reviewProgress`)

- Mỗi phần chỉ tính **lần làm gần nhất** (`latest`), nên làm lại một phần sẽ thay thế kết quả cũ của phần đó.
- Một phần được coi là **hoàn thành** khi có ít nhất một lần làm đạt `LessonReview::PASS_PERCENT = 80%` đáp án đúng. Lần sau làm kém hơn không làm mất trạng thái hoàn thành.
- Các số đầu vào cho công thức:
  - `done` = tổng số câu đã làm (lần gần nhất của mỗi phần)
  - `correct` / `wrong = done - correct`
  - `total` = tổng số câu của **tất cả** các phần của bài (kể cả phần chưa làm)
  - `seconds` = tổng thời gian của các lần gần nhất
  - `pronunciation` = trung bình điểm đọc (0–10) của các phần 5, 6, 10 đã làm
  - `pronunciationWords` = số từ/câu đã đọc ở các phần đó

### 1.3 Công thức (`LessonScoreCalculator::calculate`)

| Thành phần | Công thức | Hằng số |
|---|---|---|
| Điểm đúng | `correct × 10` | `POINTS_PER_CORRECT = 10` |
| Trừ điểm sai | `wrong × 4` | `PENALTY_PER_WRONG = 4` |
| Điểm phát âm | `pronunciationWords × max(0, pronunciation − 7) × 2` | `PRONUNCIATION_FREE_SCORE = 7`, `PRONUNCIATION_POINTS_PER_SCORE = 2` |
| **Tạm tính** | `max(0, đúng − sai + phát âm)` | |
| Thưởng tốc độ | `tạm tính × 0.20 × speedFactor` | `SPEED_BONUS_RATE = 0.20` |
| Thưởng hoàn thành | `tạm tính × completionRate` | xem dưới |
| **Điểm cuối** | `round((tạm tính + tốc độ + hoàn thành) / 10)` | `SCORE_DIVISOR = 10` |

**Hệ số tốc độ (`speedFactor`, từ 0 đến 1)**

- Thời gian chuẩn = `done × 10 giây` (`PAR_SECONDS_PER_QUESTION`).
- `ratio = seconds / thời gian chuẩn`.
- Đầy đủ khi `ratio ≤ 0.5` (`SPEED_FULL_RATIO`), giảm dần về 0 tại `ratio ≥ 1.5` (`SPEED_NONE_RATIO`).
- Bằng 0 nếu `done = 0`, `seconds ≤ 0`, hoặc trả lời đúng dưới 70% số câu đã làm (`SPEED_MIN_ACCURACY`), để chặn việc bấm bừa cho nhanh.

**Thưởng hoàn thành (`COMPLETION_BONUSES`)**

- Đúng ≥ 96% **tổng số câu của bài**: cộng 10% điểm tạm tính.
- Đúng ≥ 90%: cộng 5%. Chỉ áp dụng mức cao nhất.
- Vì mẫu số là `total` của cả bài nên người học phải làm hết các phần mới nhận được thưởng này.

Điểm không bao giờ âm.

### 1.4 Ví dụ

Bài có 50 câu, làm hết, đúng 45, sai 5; đọc 20 từ với điểm trung bình 8.5; mất 300 giây.

- Đúng: 45 × 10 = 450. Sai: 5 × 4 = 20. Phát âm: 20 × (8.5 − 7) × 2 = 60.
- Tạm tính = 450 − 20 + 60 = **490**.
- Tốc độ: `ratio = 300 / 500 = 0.6`, `speedFactor = (1.5 − 0.6) / 1 = 0.9`, thưởng = 490 × 0.2 × 0.9 ≈ **88**.
- Hoàn thành: 45/50 = 90%, mức 5%, thưởng = 490 × 0.05 ≈ **25**.
- Điểm cuối = (490 + 88 + 25) / 10 ≈ **60**.

### 1.5 Chấm phát âm (phần 5, 6, 10)

- Azure chấm mỗi lần đọc trên thang 10 (`AzurePronunciationAssessor`).
- Mỗi từ được đọc tối đa `PRONUNCIATION_ATTEMPTS` lần, **điểm cao nhất** được giữ trong session cho đến khi nộp phần đó.
- Từ được tính là đúng khi điểm tốt nhất ≥ `PRONUNCIATION_PASS_SCORE = 7`.
- Điểm đọc được lưu vào cột `selected` của câu trả lời.

---

## 2. Bảng xếp hạng (BXH)

- Điểm của người học = **tổng điểm tốt nhất** (`lesson_scores.score`) của họ trên tất cả bài học đã xuất bản thuộc khóa học đã xuất bản.
- Lấy top 10, sắp theo điểm giảm dần; bằng điểm thì user id nhỏ hơn đứng trước.
- Cache 300 giây. Khi một người vượt điểm tốt nhất của họ (`isNewBest`), BXH được tính lại ngay (`refreshAfterScoreOf`). Nếu họ lên hạng hoặc mới vào top, hệ thống phát thông báo thành tích `LearnerAchieved::leaderboardRank`.
- Hạng trong từng bài (`Lesson::standingOf`) = số người có điểm cao hơn + 1.

---

## 3. Điểm bài thi (`ExamPaper`)

- Tổng **100 điểm** chia cho tất cả câu hỏi theo **trọng số của nhóm** (`weight`). Nhóm trọng số 2 có mỗi câu giá trị gấp đôi nhóm trọng số 1.
- Câu thường: đúng thì được trọn điểm của câu, sai thì 0. Đáp án so khớp với danh sách chấp nhận (`isAccepted`).
- Câu đọc to (`speak`): được điểm theo tỷ lệ `điểm câu × best / 10`, và vẫn được tính là "đúng" khi `best ≥ 7`.
- Mỗi từ đọc tối đa 3 lần (`ExamPaper::PRONUNCIATION_ATTEMPTS`), lấy điểm cao nhất, lưu trong `ExamAttempt::pronunciation_scores`.
- Điểm cuối làm tròn 2 chữ số thập phân và chặn tối đa 100.
- Câu nghe có giới hạn 3 lần nghe, do trình duyệt đếm (`LISTEN_LIMIT`), nên không có kiểm soát phía server.

---

## 4. Thời gian chơi game (`GameScore`)

- Với mỗi bài và mỗi game, mỗi người chỉ giữ **thời gian thắng nhanh nhất** (milliseconds).
- Chỉ ghi đè khi thời gian mới nhỏ hơn.
- Bảng xếp hạng game: top 10, thời gian tăng dần, bằng nhau thì người ghi trước đứng trước.
- Điểm này độc lập với điểm bài học và không vào BXH chính.

---

## 5. Các điểm cần lưu ý khi rà soát

1. **Comment lệch với code.** Docblock của `LessonScoreCalculator` ghi "words of parts 5 and 6", nhưng `reviewProgress` tính cả phần 10 (đọc câu) vào phát âm.
2. **Điểm phát âm dùng trung bình gộp.** `pronunciation` là trung bình của trung bình từng phần rồi nhân với tổng số từ, nên phần có ít từ có trọng số bằng phần có nhiều từ trong trung bình. Kết quả có thể lệch nhẹ so với trung bình theo từng từ.
3. **Làm lại có thể làm điểm hiện tại thấp đi, nhưng điểm lưu thì không.** Do chỉ lấy lần gần nhất của mỗi phần, và `recordScore` chỉ ghi khi cao hơn. `lesson_scores.percent` cũng chỉ cập nhật cùng lúc với điểm.
4. **Thưởng tốc độ dựa vào `seconds` do trình duyệt gửi** (`duration`). Server không kiểm chứng, nên người dùng cố ý có thể gửi thời gian nhỏ để nhận tối đa 20%.
5. **Thưởng hoàn thành tính trên `total` cả bài.** Làm chưa đủ các phần thì không bao giờ nhận được thưởng, dù đúng 100% những phần đã làm.
6. **Bản ghi điểm 0.** `recordScore` vẫn tạo bản ghi `lesson_scores` khi điểm bằng 0, nên người đó được đếm vào `learners` của bài dù chưa có điểm. Thông báo thành tích chỉ phát khi điểm > 0.
7. **BXH có độ trễ tối đa 5 phút** với các thay đổi không đến từ việc vượt điểm tốt nhất, ví dụ ẩn/hiện bài học hoặc khóa học.
