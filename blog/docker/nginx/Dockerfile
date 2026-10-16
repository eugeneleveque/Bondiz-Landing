FROM nginx:alpine

COPY ./public /app/public

# Sur un serveur au umask strict (027), les fichiers du clone sont en 600 :
# Nginx ne pourrait pas les lire (404 / 403). On les rend lisibles par tous.
RUN chmod -R a+rX /app/public

COPY ./docker/nginx/default.conf /etc/nginx/conf.d/default.conf
