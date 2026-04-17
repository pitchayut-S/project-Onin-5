FROM php:8.2-apache

# เปิดใช้งาน mod_rewrite เผื่อมีการใช้งาน URL งามๆ
RUN a2enmod rewrite

# ติดตั้ง PHP extensions ที่จำเป็น (โปรเจ็คคุณใช้ mysqli)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# ตั้งค่า Directory เริ่มต้น
WORKDIR /var/www/html

# ตั้งสิทธิ์ของไฟล์ให้ Apache อ่านเขียนได้ (สำคัญมากกับโฟลเดอร์รูปภาพ เช่น uploads)
RUN chown -R www-data:www-data /var/www/html
