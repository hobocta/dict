FROM php:8.3-apache

RUN apt-get update && apt-get install -y wget htop nano zip unzip git

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer global require phpunit/phpunit -q
RUN ln -s /root/.composer/vendor/bin/phpunit /usr/local/bin/phpunit

RUN wget https://get.symfony.com/cli/installer -O - | bash
RUN export PATH="$HOME/.symfony5/bin:$PATH"

RUN echo "export LANG=C.UTF-8" >> /root/.bashrc && \
    echo "export LANGUAGE=C.UTF-8" >> /root/.bashrc && \
    echo "export LC_ALL=C.UTF-8" >> /root/.bashrc

COPY .gitconfig /root/.gitconfig

RUN rm -r /var/www/html && ln -s /var/app/public /var/www/html

COPY var/ssl/server.key /etc/ssl/private/server.key
COPY var/ssl/server.crt /etc/ssl/certs/server.pem

RUN echo "ServerName localhost:80" >> /etc/apache2/apache2.conf
RUN sed -i 's/ssl-cert-snakeoil/server/' /etc/apache2/sites-available/default-ssl.conf
RUN a2enmod rewrite
RUN a2enmod headers
RUN a2enmod ssl
RUN a2ensite default-ssl

CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
