-- -------------------------------------------------------------------------
-- install/database.sql — ข้อมูลตั้งต้นของ eleave
--
-- ตารางแกนของ Gcms (user, category, logs, login_attempt, number, migration,
-- user_meta, user_session, language) อยู่ใน install/core.sql
-- ตารางของโมดูล eleave อยู่ใน modules/eleave/install/database.sql
-- ไฟล์นี้จึงเหลือเฉพาะข้อมูลตั้งต้นที่ลงในตารางแกน ซึ่งไม่มีโมดูลไหนเป็นเจ้าของ
-- -------------------------------------------------------------------------

INSERT INTO `{prefix}_category` (`type`, `category_id`, `topic`, `color`, `is_active`) VALUES
('department', '1', 'บริหาร', NULL, 1),
('department', '2', 'จัดซื้อจัดจ้าง', NULL, 1),
('department', '3', 'บุคคล', NULL, 1);
