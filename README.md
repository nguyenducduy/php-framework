# php-framework
PHP Starter based on Phalcon Framework

### INSTALL
    + yum install https://dl.fedoraproject.org/pub/epel/epel-release-latest-6.noarch.rpm
    + yum install http://rpms.remirepo.net/enterprise/remi-release-6.rpm
    + yum install yum-utils
    + yum-config-manager --enable remi-php72
    + yum install php php-devel php-mcrypt php-cli php-gd php-curl php-mysql php-ldap php-zip php-fileinfo gcc libtool pcre-devel
    + git clone --depth=1 "git://github.com/phalcon/cphalcon.git"
        - cd cphalcon/build
        - sudo ./install
        - echo "extension=phalcon.so" > /etc/php.d/30-phalcon.ini
