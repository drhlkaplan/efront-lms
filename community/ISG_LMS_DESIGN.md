# Online İSG Eğitim Platformu — Sistem Tasarımı (TR)

Bu doküman, Türkçe dil desteği olan, ölçeklenebilir, modüler ve güvenli bir **ONLINE İŞ SAĞLIĞI VE GÜVENLİĞİ (İSG) EĞİTİM PLATFORMU** için bütünsel tasarım özetidir.

---

## 1) Amaç ve Kapsam

Az Tehlikeli, Tehlikeli ve Çok Tehlikeli iş kollarına göre **SCORM tabanlı** çevrim içi eğitim sunan, sınavlı ve sertifikalı bir LMS oluşturmak.

---

## 2) Genel Sistem Yapısı

- **Web tabanlı**, responsive ve mobil uyumlu
- **Türkçe arayüz**
- **Rol tabanlı kullanıcı sistemi**
- **SCORM 1.2 ve 2004 uyumlu**
- **KVKK uyumlu veri yapısı**
- **Çoklu firma / çoklu kullanıcı desteği**

---

## 3) Kullanıcı Rollerı

1. **Süper Admin**
2. **Admin (Eğitim yöneticisi)**
3. **Firma Yetkilisi**
4. **Öğrenci / Kursiyer**

---

## 4) Ana Modüller

### 4.1 AUTH / Kullanıcı Yönetimi
- Kayıt / giriş / şifre sıfırlama
- Rol bazlı yetkilendirme
- Firma bazlı kullanıcı gruplama
- Manuel ve toplu (Excel/CSV) kullanıcı ekleme

### 4.2 Eğitim & Kurs Yönetimi
- Tehlike sınıfına göre kurslar:
  - Az Tehlikeli
  - Tehlikeli
  - Çok Tehlikeli
- SCORM paket yükleme
- Eğitim süresi tanımı (saat/dakika)
- Eğitimi tamamlama zorunluluğu
- Eğitim ilerleme takibi (%)

### 4.3 Sınav Sistemi
- İlk sınav (ön test)
- Son sınav
- Soru tipleri:
  - Çoktan seçmeli
  - Doğru / yanlış
- Rastgele soru seçimi
- Süre sınırlaması
- Başarı barajı (%)
- Sınav tekrar kuralı
- Otomatik değerlendirme

### 4.4 Sertifika Modülü
- Eğitimi ve sınavı geçenlere otomatik sertifika
- Sertifika içeriği:
  - Ad Soyad
  - TC Kimlik No
  - Eğitim adı
  - Tehlike sınıfı
  - Eğitim süresi
  - Sertifika numarası
  - Düzenlenme tarihi
- PDF formatında çıktı
- QR kodlu doğrulama
- Sertifika doğrulama sayfası (public)

### 4.5 Admin Kontrol Paneli
- Dashboard:
  - Toplam kullanıcı
  - Aktif kurslar
  - Tamamlanan eğitimler
  - Sertifika sayısı
- Kullanıcı / firma bazlı raporlar
- Eğitim – sınav – sertifika yönetimi
- SCORM izleme verileri
- Loglama (kim, ne zaman, ne yaptı)

### 4.6 Öğrenci Paneli
- Kayıtlı eğitimler
- Eğitim ilerleme durumu
- Sınav girişleri
- Sertifika görüntüleme & indirme
- Eğitim geçmişi

---

## 5) Veritabanı Şeması (Detaylı)

> Tüm tablolarda ortak alanlar: `id`, `created_at`, `updated_at`.

### 5.1 Tablolar ve Alanlar

#### `roles`
- `name`
- `description`
- `is_system` (boolean)

#### `firms`
- `name`
- `tax_number`
- `contact_name`
- `contact_email`
- `contact_phone`
- `status`
- `address`
- `kvkk_consent_text` (text)

#### `users`
- `firm_id` (FK → firms)
- `role_id` (FK → roles)
- `first_name`
- `last_name`
- `email` (unique)
- `phone`
- `tc_identity_no`
- `password_hash`
- `status`
- `last_login_at`
- `locale` (tr-TR)
- `is_mfa_enabled` (boolean)
- `mfa_secret` (nullable)

#### `course_categories` (Tehlike sınıfları)
- `name` (Az Tehlikeli / Tehlikeli / Çok Tehlikeli)
- `description`
- `code` (AZ/TE/CT)

#### `courses`
- `category_id` (FK → course_categories)
- `title`
- `description`
- `duration_minutes`
- `completion_required` (boolean)
- `status`
- `thumbnail_path`
- `created_by` (FK → users)

#### `scorm_packages`
- `course_id` (FK → courses)
- `scorm_version` (1.2 / 2004)
- `package_path`
- `manifest_data` (json)
- `launch_path`
- `sco_count`

#### `enrollments`
- `user_id` (FK → users)
- `course_id` (FK → courses)
- `status` (enrolled / in_progress / completed)
- `progress_percent`
- `completed_at`
- `started_at`
- `assigned_by` (FK → users)

#### `lessons_progress`
- `enrollment_id` (FK → enrollments)
- `scorm_sco_id`
- `progress_percent`
- `last_accessed_at`
- `time_spent_seconds`
- `completion_status`

#### `exams`
- `course_id` (FK → courses)
- `type` (pre_test / final)
- `duration_minutes`
- `pass_score_percent`
- `attempt_limit`
- `randomize_questions` (boolean)
- `question_count`

#### `questions`
- `exam_id` (FK → exams)
- `type` (multiple_choice / true_false)
- `question_text`
- `choices` (json)
- `correct_answer`
- `difficulty` (easy/medium/hard)

#### `exam_results`
- `exam_id` (FK → exams)
- `user_id` (FK → users)
- `score_percent`
- `passed` (boolean)
- `attempt_number`
- `completed_at`
- `duration_seconds`

#### `certificates`
- `user_id` (FK → users)
- `course_id` (FK → courses)
- `certificate_no` (unique)
- `issued_at`
- `qr_code_path`
- `pdf_path`
- `verified_at` (nullable)

#### `logs`
- `user_id` (FK → users)
- `action`
- `entity_type`
- `entity_id`
- `ip_address`
- `user_agent`
- `metadata` (json)

### 5.2 İlişkiler ve Kısıtlar (Özet)
- `firms` → `users` (1:N)
- `roles` → `users` (1:N)
- `course_categories` → `courses` (1:N)
- `courses` → `scorm_packages` (1:N)
- `courses` → `exams` (1:N)
- `courses` → `enrollments` (1:N)
- `users` → `enrollments` (1:N)
- `enrollments` → `lessons_progress` (1:N)
- `exams` → `questions` (1:N)
- `exams` → `exam_results` (1:N)
- `users` → `exam_results` (1:N)
- `users` → `certificates` (1:N)
- `courses` → `certificates` (1:N)
- `users` → `logs` (1:N)

---

## 6) Sayfa – Modül İlişkileri

| Sayfa | Modül | Açıklama |
| --- | --- | --- |
| Giriş / Kayıt / Şifre Sıfırlama | Auth | Kimlik doğrulama akışları |
| Firma Yönetimi | Kullanıcı Yönetimi | Firma ve yetkili yönetimi |
| Kullanıcı Yönetimi | Kullanıcı Yönetimi | Rol atama, toplu import |
| Kurs Yönetimi | Eğitim & Kurs | Kurs oluşturma, SCORM yükleme |
| Sınav Yönetimi | Sınav | Soru bankası, sınav kuralları |
| Sertifika Yönetimi | Sertifika | Şablon ve doğrulama |
| Raporlar | Admin | Firma/kullanıcı bazlı rapor |
| Öğrenci Eğitimlerim | Öğrenci Paneli | Kayıtlı eğitimler |
| Öğrenci Sınavlarım | Öğrenci Paneli | Ön test / final |
| Öğrenci Sertifikalarım | Öğrenci Paneli | PDF ve doğrulama |

---

## 6.1 Tüm Sayfalar (Detaylı Envanter)

### Ortak / Public
- **Anasayfa** (public landing)
- **Giriş** (email/telefon + şifre)
- **Kayıt** (firma yetkilisi tarafından yönlendirilen kayıt akışı)
- **Şifre Sıfırlama**
- **KVKK Aydınlatma Metni**
- **Gizlilik Politikası**
- **Kullanım Şartları**
- **Sertifika Doğrulama** (public, QR ile erişim)

### Süper Admin
- **Süper Admin Dashboard**
- **Firma Yönetimi**
  - Firma Liste / Detay / Durum
  - Firma Yetkilileri
- **Global Kullanıcı Yönetimi**
  - Kullanıcı Liste / Detay / Rol Atama
  - Toplu İçe Aktarım (CSV/Excel)
- **Global Kurs Yönetimi**
  - Kurs Liste / Yeni Kurs / Düzenle
  - SCORM Paket Yönetimi
- **Global Sınav Yönetimi**
  - Ön Test / Final Ayarları
  - Soru Bankası
- **Global Sertifika Yönetimi**
  - Sertifika Liste / İptal
  - Şablon & QR Doğrulama
- **Global Raporlar**
  - Firma bazlı raporlar
  - Tehlike sınıfı bazlı raporlar
- **Sistem Ayarları**
  - Dil, e-posta, güvenlik, KVKK metinleri
- **Loglar & Denetim**

### Admin (Eğitim Yöneticisi)
- **Admin Dashboard**
- **Kurs Yönetimi**
  - Kurs Liste / Yeni / Düzenle
  - SCORM Paket yükleme
- **Sınav Yönetimi**
  - Sınav ayarları, soru yönetimi
- **Kullanıcı Yönetimi**
  - Firma kullanıcıları
  - Toplu kayıt
- **Sertifika Yönetimi**
  - Sertifika liste ve detay
- **Raporlar**
  - Eğitim ilerleme raporu
  - Sınav başarı raporu
- **Loglar**

### Firma Yetkilisi
- **Firma Dashboard**
- **Firma Kullanıcıları**
  - Liste / Davet / Durum
- **Eğitimler**
  - Firma eğitim atamaları
- **Sınavlar**
  - Kullanıcı bazlı sınav sonuçları
- **Sertifikalar**
  - Firma bazlı sertifika listesi
- **Raporlar**
  - Tamamlanma & başarı oranı

### Öğrenci / Kursiyer
- **Öğrenci Dashboard**
- **Eğitimlerim**
  - Kurs listesi ve ilerleme
- **Kurs Detayı**
  - SCORM oynatıcı, süre, tamamlanma
- **Sınavlarım**
  - Ön test / final sınav girişleri
- **Sertifikalarım**
  - PDF indir / doğrulama
- **Profilim**
  - Kişisel bilgiler, şifre değişikliği

---

## 7) Admin Panel Ekranları (UI Taslağı)

- **Dashboard**
  - KPI kartları: toplam kullanıcı, aktif kurs, tamamlanan eğitim, sertifika sayısı
  - Son aktiviteler ve log özetleri
- **Firmalar**
  - Firma listesi, detay, durum, yetkili kullanıcılar
- **Kullanıcılar**
  - Filtreleme (firma, rol, durum)
  - Toplu kullanıcı yükleme (CSV/Excel)
- **Kurslar**
  - Tehlike sınıfı bazlı liste
  - SCORM paket yönetimi
- **Sınavlar**
  - Ön test / son test yapılandırma
  - Soru bankası yönetimi
- **Sertifikalar**
  - Sertifika listesi, doğrulama bağlantısı
- **Raporlar**
  - Firma bazlı tamamlanma oranları
  - Kullanıcı bazlı sertifika geçmişi
- **Loglar**
  - Kim, ne zaman, ne yaptı?

---

## 8) Öğrenci Panel Ekranları (UI Taslağı)

- **Eğitimlerim**
  - Kurs kartları, ilerleme yüzdesi
- **Kurs Detay**
  - SCORM oynatıcı, kalan süre, durum
- **Sınavlarım**
  - Ön test / final sınav girişleri
- **Sertifikalarım**
  - PDF indirme ve QR doğrulama
- **Profilim**
  - Kişisel bilgiler, şifre değişikliği

---

## 9) Sertifika Örnek Tasarımı (Metinsel Şablon)

```
T.C. İŞ SAĞLIĞI VE GÜVENLİĞİ EĞİTİM SERTİFİKASI
------------------------------------------------
Ad Soyad          : [Ad Soyad]
TC Kimlik No      : [TC Kimlik No]
Eğitim Adı        : [Eğitim Adı]
Tehlike Sınıfı    : [Az Tehlikeli / Tehlikeli / Çok Tehlikeli]
Eğitim Süresi     : [X Saat]
Sertifika No      : [Sertifika No]
Düzenlenme Tarihi : [Tarih]

QR Kod ile doğrulama: https://lms.ornek.com/verify/[Sertifika No]
```

---

## 10) Teknik Güvenlik Gereksinimleri

- Güvenli oturum yönetimi (JWT veya sunucu tarafı session)
- XSS / CSRF koruması
- Rol bazlı erişim kontrolü
- KVKK uyumlu veri saklama
- Loglama ve denetim izi (audit trail)
- Modüler servis mimarisi (ölçeklenebilir yapı)

---

## 11) Tasarım İlkeleri (UI/UX)

- Kurumsal, sade, güven veren tasarım
- İSG temalı renk paleti (mavi / yeşil / gri tonları)
- Mobil & tablet uyumlu
- Kullanıcı dostu, adım adım akış
