# Usa a imagem oficial do PHP com Apache
FROM php:8.2-apache

# Instala as extensões mysqli e pdo_mysql para o PHP se conectar ao MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilita o mod_rewrite do Apache para URLs amigáveis (opcional, mas útil)
RUN a2enmod rewrite
