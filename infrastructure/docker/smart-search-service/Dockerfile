FROM danskernesdigitalebibliotek/php8.1-fpm-base AS APP_BUILDER
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ARG APP_VERSION="develop"
ENV APP_PATH=/var/www/html

# Move site into the container.
ADD https://github.com/danskernesdigitalebibliotek/smart-search-service/archive/${APP_VERSION}.tar.gz /tmp/app.tar
RUN tar -zxf /tmp/app.tar --strip-components=1 -C ${APP_PATH} \
    && rm /tmp/app.tar

# Add composer in from the official composer image (also alpine).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR ${APP_PATH}

## Install assets, which requires a HACK as redis is not available (should be removed later on).
RUN APP_ENV=github_actions composer install --no-dev -o --no-scripts --classmap-authoritative \
    && APP_ENV=github_actions composer docker-build \
    && rm -rf infrastructure \
    && composer clear-cache

####
## Build main application image.
####
FROM danskernesdigitalebibliotek/php8.1-fpm-base
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ENV APP_PATH=/var/www/html

# Install the application.
COPY --from=APP_BUILDER ${APP_PATH} ${APP_PATH}
RUN chown -R www-data:www-data ${APP_PATH}/var \
    && mkdir -p ${APP_PATH}/config/secrets \
    && chown -R www-data:www-data ${APP_PATH}/config/secrets \
    && chown -R www-data:www-data ${APP_PATH}/public

# Download prometheus php-fpm export.
COPY --from=hipages/php-fpm_exporter /php-fpm_exporter /usr/local/bin/php-fpm_exporter

# Copy configuration.
COPY etc/ /etc/

# Install configuration template handler
ADD https://github.com/kelseyhightower/confd/releases/download/v0.16.0/confd-0.16.0-linux-amd64 /usr/local/bin/confd
RUN chmod +x /usr/local/bin/confd

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR ${APP_PATH}

ENTRYPOINT [ "docker-entrypoint.sh" ]
