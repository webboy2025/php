# استخدام صورة PHP 8.1 مع Apache
FROM php:8.1-apache

# تثبيت امتداد mysqli
RUN docker-php-ext-install mysqli

# نسخ الملفات من نفس مجلد Dockerfile (وهو www) إلى مجلد الويب داخل الحاوية
COPY . /var/www/html/

# إنشاء مجلد للصور المرفوعة وضبط الصلاحيات
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

# فتح المنفذ 80
EXPOSE 80

# تشغيل Apache
CMD ["apache2-foreground"]
