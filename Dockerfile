FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql

COPY --chown=www-data:www-data . /var/www/html/
RUN mkdir -p /var/www/html/.runtime/sessions \
    /var/www/html/assets/uploads/flowers \
    /var/www/html/assets/uploads/bloomguard \
    && chown -R www-data:www-data /var/www/html/.runtime /var/www/html/assets/uploads \
    && chmod -R u+rwX,g+rwX /var/www/html/.runtime /var/www/html/assets/uploads

COPY docker/render-entrypoint.sh /usr/local/bin/render-entrypoint
RUN chmod +x /usr/local/bin/render-entrypoint

ENTRYPOINT ["/usr/local/bin/render-entrypoint"]
