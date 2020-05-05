FROM php:7.2.2-apache
RUN  sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
RUN ln -s /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/rewrite.load

RUN docker-php-ext-install pdo_mysql mbstring

RUN mkdir -p /var/www/html/logs
WORKDIR /var/www/html/
ENV PORT 3000
CMD sed -i "s/80/$PORT/g" /etc/apache2/ports.conf && docker-php-entrypoint apache2-foreground