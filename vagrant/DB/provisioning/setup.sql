# Schema set up script

DROP DATABASE IF EXISTS nico_slx;
CREATE DATABASE IF NOT EXISTS nico_slx CHARACTER SET=utf8;

grant all on `nico_slx`.* to 'nico_slxuser'@'localhost' identified by 'oa78tl$kg7aLS-G*';

GRANT ALL PRIVILEGES ON `nico_slx`.* TO 'nico_slxuser'@'localhost';

FLUSH PRIVILEGES;
