FROM php:8.2-apache

# تثبيت الملحقات
RUN docker-php-ext-install pdo_mysql mysqli

# تمكين mod_rewrite
RUN a2enmod rewrite

# نسخ ملفات المشروع
COPY . /var/www/html/

# صلاحيات مجلد الرفع
RUN chmod -R 755 /var/www/html/uploads

# تشغيل Apache على المنفذ اللي بتحدده Railway
ENV PORT=10000
EXPOSE $PORT

# تعديل منفذ Apache عشان يشتغل على PORT
CMD ["sh", "-c", "sed -i 's/80/'$PORT'/g' /etc/apache2/ports.conf && apache2-foreground"]