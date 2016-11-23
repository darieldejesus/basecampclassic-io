#!/usr/bin/env bash

#########################################################
#                 POST VAGRANT UP TASKS                 #
#########################################################

# Add repository to install PHP 7
sudo add-apt-repository ppa:ondrej/php

# Update ubuntu repositories.
sudo apt-get update --yes
sudo apt-get upgrade --yes

# Install Apache
sudo apt-get install --yes apache2

# Changing Apache User/Group
# sudo sed -i 's/APACHE_RUN_USER=.*/APACHE_RUN_USER=vagrant/g' /etc/apache2/envvars
# sudo sed -i 's/APACHE_RUN_GROUP=.*/APACHE_RUN_GROUP=vagrant/g' /etc/apache2/envvars

# Install MySQL
sudo debconf-set-selections <<< "mysql-server mysql-server/root_password password password"
sudo debconf-set-selections <<< "mysql-server mysql-server/root_password_again password password"
sudo apt-get install --yes mysql-server
mysql -u root -ppassword -e "CREATE SCHEMA basecampio"

# Install PHP 5
sudo apt-get install --yes php7.0 libapache2-mod-php7.0 php7.0-mysql php7.0-mbstring php7.0-curl php7.0-xml

# Install PHPUnit
wget https://phar.phpunit.de/phpunit.phar
chmod +x phpunit.phar
sudo mv phpunit.phar /usr/local/bin/phpunit

# Install Git
sudo apt-get install --yes git

# Install required tool(s) by Composer
sudo apt-get install --yes unzip

# Install Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Run composer
cd /home/www/
composer install

# Setup Apache
sudo a2enmod rewrite

# Desable Default Apache vhosts
sudo a2dissite 000-default.conf default-ssl.conf

# Copy and load vhosts
sudo ln -s /home/www/server/default/basecampio.conf /etc/apache2/sites-enabled/

# Restart Apache2
sudo service apache2 restart

# Define .env laravel and required settings
cp /home/www/classes/App.conf.php.default /home/www/classes/App.conf.php
