FROM nginx:alpine

# Copy nginx configuration
COPY nginx.conf /etc/nginx/nginx.conf

# Create nginx user
RUN addgroup -g 1000 -S nginx \
    && adduser -S -D -H -u 1000 -h /var/cache/nginx -s /sbin/nologin -G nginx -g nginx nginx

# Create necessary directories
RUN mkdir -p /var/www/public \
    && chown -R nginx:nginx /var/www

EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"] 