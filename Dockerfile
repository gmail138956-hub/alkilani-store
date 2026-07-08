FROM php:8.2-apache

# تثبيت الملحقات المطلوبة
RUN docker-php-ext-install pdo_mysql mysqli

# تمكين mod_rewrite
RUN a2enmod rewrite

# نسخ ملفات المشروع إلى مجلد Apache
COPY . /var/www/html/

# إعطاء صلاحيات لمجلد رفع الصور
RUN chmod -R 755 /var/www/html/uploads

# تعيين المنفذ
EXPOSE 10000

# تشغيل Apache
CMD ["apache2-foreground"]