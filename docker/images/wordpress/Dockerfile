FROM wordpress:cli

COPY wordpress-init /usr/local/bin/wordpress-init
COPY wp-config-overrides.php /

ENTRYPOINT ["/bin/bash", "-c"]

CMD ["wordpress-init"]
