#!/usr/bin/env bash

apt-get update
debconf-set-selections <<< 'mysql-server mysql-server/root_password password root'
debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password root'
apt-get install -y apache2 mysql-server-5.5 php5 php-pear php5-mysql php5-curl php5-gd php5-dev libcurl3-openssl-dev make libpcre3-dev

sudo pecl install pecl_http
#sudo pecl install oauth
#sudo touch /etc/php5/conf.d/oauth.ini
#sudo echo "extension=oauth.so" > /etc/php5/conf.d/oauth.ini

rm -rf /var/www
ln -fs /vagrant /var/www
sudo cp /vagrant/vagrant/vhost /etc/apache2/sites-enabled/silex
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod expires
sudo /etc/init.d/apache2 restart

sudo mysql -uroot -proot < /vagrant/vagrant/DB/provisioning/setup.sql
sudo mysql -uroot -proot nico_slx < /vagrant/vagrant/DB/provisioning/databases/nico_slx/current.sql