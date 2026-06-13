FROM yiisoftware/yii2-php:8.1-apache

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist

COPY . .

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && php init --env=Production --overwrite=All \
    && sed -i -e 's|/app/web|/app/frontend/web|g' /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite \
    && mkdir -p backend/runtime frontend/runtime console/runtime common/runtime \
        backend/web/assets frontend/web/assets frontend/web/uploads \
        common/runtime/student-id-documents \
    && chmod -R 775 backend/runtime frontend/runtime console/runtime common/runtime \
    && chmod -R 775 backend/web/assets frontend/web/assets frontend/web/uploads \
    && chmod +x /app/docker/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/app/docker/entrypoint.sh"]
