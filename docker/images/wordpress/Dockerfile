FROM wordpress:cli

COPY wordpress-init /usr/local/bin/wordpress-init

ENTRYPOINT ["/bin/bash", "-c"]

CMD ["wordpress-init"]
