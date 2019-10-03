FROM ubuntu:latest

ADD ./docker/extract/extract.php /usr/sbin/
ADD ./docker/cron/extract /etc/cron.d/
ADD ./docker/supervisor/supervisord.conf /etc/
ADD ./docker/supervisor/services /etc/supervisord.d/


RUN apt-get update && \
  apt-get -y dist-upgrade && \
  apt-get install -y --no-install-recommends \ 
	cron \
	php-cli \
	php-json  \
    supervisor && \
    chmod +x /usr/sbin/extract.php && \
    apt-get clean -y && \
    apt-get autoclean -y && \
    apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/* /var/lib/log/* /tmp/* /var/tmp/*

VOLUME /etc/ssl/acme/src
VOLUME /etc/ssl/acme/dst

CMD ["supervisord", "--nodaemon", "--configuration", "/etc/supervisord.conf"]
