FROM nginx:alpine

# Install dockerfy
RUN apk add wget libc6-compat \
    && wget https://github.com/SocialCodeInc/dockerfy/releases/download/1.1.0/dockerfy-linux-amd64-1.1.0.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerfy-linux-amd64*.gz \
    && rm dockerfy-linux-amd64*.gz

COPY templates /templates

ENTRYPOINT ["dockerfy"]
CMD [ \
    "--verbose", \
    "--template", "/templates/{{ .Env.STACK }}.nginx.conf:/etc/nginx/nginx.conf", "--", \
    "--run", "cat", "/etc/nginx/nginx.conf", "--", \
    "nginx", "-g", "daemon off;" \
    ]
