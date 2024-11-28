FROM php:8.3-apache

RUN apt-get update && apt-get install -y wget htop nano zip unzip git

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer global require phpunit/phpunit -q
RUN ln -s /root/.composer/vendor/bin/phpunit /usr/local/bin/phpunit

RUN wget https://get.symfony.com/cli/installer -O - | bash
RUN echo 'export PATH="$HOME/.symfony5/bin:$PATH"' >> /root/.bashrc

RUN cd && \
    wget -O xdebug.zip https://github.com/xdebug/xdebug/archive/refs/heads/master.zip && \
    unzip xdebug.zip && \
    rm xdebug.zip && \
    cd xdebug-master/ && \
    chmod u+x rebuild.sh && \
    ./rebuild.sh && \
    make test && \
    echo "zend_extension=xdebug.so" >> /usr/local/etc/php/conf.d/99-xdebug.ini && \
    echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/99-xdebug.ini && \
    echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/99-xdebug.ini && \
    echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/99-xdebug.ini && \
    echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/99-xdebug.ini

RUN echo "export LANG=C.UTF-8" >> /root/.bashrc && \
    echo "export LANGUAGE=C.UTF-8" >> /root/.bashrc && \
    echo "export LC_ALL=C.UTF-8" >> /root/.bashrc

RUN rm -r /var/www/html && ln -s /var/app/public /var/www/html

RUN mkdir -p /var/ssl && \
    cd /var/ssl && \
    openssl genrsa -out server.key 2048 && \
    openssl req -new -key server.key -out server.csr -subj "/C=/ST=/L=/O=/OU=/CN=" && \
    openssl x509 -req -days 365 -in server.csr -signkey server.key -out server.crt && \
    cat server.crt server.key > server.pem && \
    cp /var/ssl/server.key /etc/ssl/private/server.key && \
    cp /var/ssl/server.crt /etc/ssl/certs/server.pem

RUN echo "ServerName localhost:80" >> /etc/apache2/apache2.conf
RUN sed -i 's/ssl-cert-snakeoil/server/' /etc/apache2/sites-available/default-ssl.conf
RUN a2enmod rewrite
RUN a2enmod headers
RUN a2enmod ssl
RUN a2ensite default-ssl

CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
