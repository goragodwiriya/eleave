# eLeave — ระบบลาออนไลน์

**eLeave** คือระบบบริหารการลาออนไลน์สำหรับองค์กร รองรับการยื่นคำขอลา การจัดการประเภทการลา และการรายงานยอดวันลาคงเหลือ พัฒนาด้วย PHP (Kotchasan Framework) ร่วมกับ JavaScript สมัยใหม่ (Now.js / Vite)

> Version: **7.0.0** | License: MIT

---

## คุณสมบัติหลัก

| ฟีเจอร์ | รายละเอียด |
|---|---|
| ยื่นคำขอลา | เลือกประเภทการลา วันเริ่มต้น-สิ้นสุด ช่วงวัน (เต็มวัน/เช้า/บ่าย) พร้อมแนบไฟล์เอกสาร |
| แสดงนโยบายและยอดคงเหลือแบบ Real-time | ดูโควต้าและยอดวันลาคงเหลือขณะกรอกฟอร์ม |
| จัดการประเภทการลา | เพิ่ม/แก้ไข/ปิดใช้งาน กำหนดโควต้าวันลาต่อปี |
| Dashboard | แสดงสถิติสรุป (cards) และกราฟรายแผนก |
| รายงานยอดวันลา | ดูยอดโควต้า ใช้ไป รออนุมัติ คงเหลือ ของพนักงานทุกคน กรองตามแผนก/ประเภทลา |
| สถิติส่วนบุคคล | ดูสถิติการลาของตนเองหรือพนักงานคนอื่น (สำหรับผู้อนุมัติ/แอดมิน) |
| การแจ้งเตือน | แจ้งเตือนผ่านอีเมลและ LINE หรือ Telegram เมื่อมีการยื่น/อนุมัติ/ปฏิเสธ |
| รองรับหลายภาษา | ภาษาไทย และ ภาษาอังกฤษ |
| ควบคุมสิทธิ์ | กำหนดสิทธิ์รายผู้ใช้ (สมาชิก / เจ้าหน้าที่ / ผู้ดูแลระบบ) |
| เข้าสู่ระบบ | รองรับ Username/Password, Google OAuth |

---

## ความต้องการของระบบ (System Requirements)

### Server
| รายการ | ความต้องการ |
|---|---|
| PHP | >= 7.4 |
| PHP Extensions | PDO MySQL, mbstring, zlib, JSON, XML, OpenSSL, GD, cURL |
| Web Server | Apache หรือ Nginx |
| Database | MySQL >= 5.7 หรือ MariaDB >= 10.3 |

### PHP Settings (แนะนำ)
| รายการ | ค่าที่ต้องการ |
|---|---|
| `safe_mode` | Off |
| `file_uploads` | On |
| `magic_quotes_gpc` | Off |
| `session.auto_start` | Off |
| Native ZIP support | On |
| OPcache | On |

---

## การติดตั้ง (Installation)

### ขั้นที่ 1 — อัปโหลดไฟล์

อัปโหลดไฟล์ทั้งหมดไปยัง Document Root ของ Web Server เช่น `/var/www/html/eleave/` หรือ `htdocs/eleave/`

```
eleave/
├── api.php
├── index.html
├── load.php
├── settings/        ← ต้องสามารถเขียนได้ (chmod 755)
├── datas/           ← ต้องสามารถเขียนได้ (chmod 755)
│   ├── cache/
│   ├── logs/
│   └── images/
├── modules/
├── templates/
└── install/
```

### ขั้นที่ 2 — สร้างฐานข้อมูล

สร้างฐานข้อมูล MySQL/MariaDB ว่างเปล่าก่อนติดตั้ง เช่น:

```sql
CREATE DATABASE eleave CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### ขั้นที่ 3 — รัน Installation Wizard

เปิดเบราว์เซอร์ไปที่:

```
http://your-domain.com/eleave/install/
```

Wizard จะแนะนำขั้นตอนดังนี้:

| ขั้นตอน | รายละเอียด |
|---|---|
| ตรวจสอบ Server | ตรวจสอบ PHP version และ Extensions ที่จำเป็น |
| ตรวจสอบไฟล์/โฟลเดอร์ | ตรวจสอบสิทธิ์เขียน `datas/` และ `settings/` |
| ตั้งค่าผู้ดูแลระบบ | กำหนด username และ password ของ Admin |
| ตั้งค่าฐานข้อมูล | ระบุ host, port, ชื่อ database, table prefix |
| ติดตั้งสำเร็จ | ระบบสร้างตารางและข้อมูลเริ่มต้นให้อัตโนมัติ |

### ขั้นที่ 4 — ลบโฟลเดอร์ install

หลังติดตั้งสำเร็จ ให้ลบหรือเปลี่ยนชื่อโฟลเดอร์ `install/` เพื่อความปลอดภัย:

```bash
rm -rf install/
```

---

## การอัปเกรด (Upgrade)

หากมีเวอร์ชันใหม่ให้อัปโหลดไฟล์ทับ แล้วเปิด:

```
http://your-domain.com/eleave/install
```

ระบบจะรัน migration script (`upgrade0.php`, `upgrade1.php`, `upgrade2.php`) โดยอัตโนมัติ

---

## โครงสร้างโปรเจ็กต์

```
eleave/
├── api.php                  # API entry point
├── load.php                 # Bootstrap loader
├── index.html               # SPA shell (Now.js)
├── vite.config.js           # Vite build configuration
├── package.json             # Node.js dependencies
│
├── modules/
│   └── eleave/
│       ├── controllers/     # API Controllers (PHP)
│       ├── models/          # Database Models (PHP)
│       └── views/           # View helpers (PHP)
│
├── templates/
│   └── eleave/              # HTML templates (SPA components)
│       ├── dashboard.html
│       ├── request.html
│       ├── review.html
│       ├── approvals.html
│       ├── balance.html
│       ├── statistics.html
│       ├── leave-types.html
│       └── settings.html
│
├── js/
│   ├── main.js              # JS entry
│   └── components/          # UI Components
│
├── Now/                     # Now.js Framework core
│   ├── Now.js               # Core framework
│   └── entry-*.js           # Feature bundles
│
├── Kotchasan/               # PHP Framework core
├── Gcms/                    # Application core (Auth, Config, API base)
├── settings/                # Runtime config (auto-generated)
│   ├── config.php
│   └── database.php
├── datas/                   # Runtime data (writable)
│   ├── cache/
│   ├── logs/
│   └── images/
└── install/                 # Installation wizard
```

---

## API Overview

ระบบใช้ REST API รูปแบบ:

```
GET/POST /api/<controller>/<action>
```

| Controller | Actions | สิทธิ์ |
|---|---|---|
| `dashboard` | `cards`, `graph`, `logs` | ผู้ใช้ที่ล็อกอิน |
| `request` | `get`, `policy`, `save`, `delete` | ผู้ใช้ที่ล็อกอิน |
| `review` | `get`, `approve`, `reject` | ผู้อนุมัติ |
| `approvals` | `table` | ผู้อนุมัติ |
| `balance` | `report` | ผู้ใช้/แอดมิน |
| `statistics` | `index`, `render` | ผู้ใช้/แอดมิน |
| `leavetype` | `get`, `save`, `delete` | แอดมิน |
| `leavetypes` | `table` | แอดมิน |
| `settings` | `get`, `save` | แอดมิน |

**Authentication:** Bearer Token (JWT) หรือ `X-Access-Token` header  
**CSRF:** POST requests ต้องส่ง CSRF token

---

## การตั้งค่า (Configuration)

ไฟล์ตั้งค่าหลักอยู่ที่ `settings/config.php` (สร้างโดย Wizard อัตโนมัติ) ค่าที่สำคัญ:

| ค่า | ความหมาย |
|---|---|
| `eleave_fiscal_year` | เดือนเริ่มต้นปีงบประมาณ (1 = มกราคม) |
| `eleave_approve_status` | สถานะผู้ใช้ที่อนุมัติแต่ละขั้น |
| `eleave_approve_department` | แผนกที่อนุมัติแต่ละขั้น |
| `eleave_file_types` | ประเภทไฟล์แนบที่อนุญาต |
| `eleave_upload_size` | ขนาดไฟล์แนบสูงสุด (bytes) |
| `line_api_key` | LINE Notify API key |
| `noreply_email` | อีเมลผู้ส่ง |
| `email_Host` | SMTP host |
| `google_client_id` | Google OAuth Client ID |

---

## การ Build JavaScript

ต้องการ Node.js >= 18

```bash
# ติดตั้ง dependencies
npm install

# Build ทั้งหมด
npm run build

# Build เฉพาะ core
npm run build:core

# Development mode
npm run dev
```

---

## License

MIT License — ดูรายละเอียดใน [LICENSE](LICENSE)

---

## Credits

- PHP Framework: [Kotchasan](https://www.kotchasan.com/) by Goragod Wiriya
- JavaScript Framework: Now.js (bundled) [Now.js](https://www.nowjs.net/) by Goragod Wiriya
- Email: [PHPMailer](https://github.com/PHPMailer/PHPMailer)
