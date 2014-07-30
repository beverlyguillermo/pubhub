
# Default $PATH
Exec { path => '/usr/local/bin:/bin:/usr/bin:/usr/local/sbin:/usr/sbin:/sbin' }

$mysql_root_password = "pwd333"

include centos
include gems
include git
include httpd
include php
include mysqld
include nodejs
# include wpcli

yumrepo { 'epel': 
    baseurl  => "http://dl.fedoraproject.org/pub/epel/6/x86_64",
    descr    => "Extra Packages for Enterprise Linux",
    enabled  => 1,
    gpgcheck => 0,
    before   => Yumrepo['ius'],
}

yumrepo { 'ius':
    baseurl  => "http://dl.iuscommunity.org/pub/ius/stable/CentOS/6/x86_64",
    descr    => "IUS Community Release Repo",
    enabled  => 1,
    gpgcheck => 0,
}

exec { "set timezone":
  command => "/bin/ln -sf /usr/share/zoneinfo/America/New_York /etc/localtime",
  refreshonly => true
}

#################################
# Originally included packages
# Not sure if need?
#################################

package { "libssh2":
    ensure => present,
}

# package { "java-1.6.0-openjdk":
#     ensure => present
# }


# Allow apache to write to Hub directories/files
exec { "Add apache user to vagrant group":
    command => "sudo usermod -a -G vagrant apache",
    unless => "groups apache | grep vagrant",
    require => Package["httpd"],
    before => Package["php54"],
}

###########################
# VIRTUAL HOSTS
###########################

# Default virtual host, should change contents
httpd::vhost { 'default':
    port    => 80,
    docroot => "/var/www/html",
}

file { '/var/www/html/info.php' :
    ensure  => present,
    content => "<?php phpinfo();",
    require => Package['httpd'],
}

file { '/var/www/html/.htaccess' :
    ensure  => present,
    content => "SetEnv APPLICATION_ENV \"development\"",
    require => Package['httpd'],
}

httpd::vhost { 'local.hub.jhu.edu':
    port    => 80,
    docroot => "/var/www/html/hub/public",
    require => Package['httpd']
}

# httpd::vhost { 'local.api.hub.jhu.edu':
#     port    => 80,
#     docroot => "/var/www/html/hub/api/public",
#     require => Package['httpd']
# }



###########################
# MySQL DBs and Users
###########################

mysqld::user { 'admin-localhost':
    username => "admin",
    host     => "localhost",
    password => "password",
    require  => Exec['set root password'],
}
mysqld::grant { "admin-localhost":
    permissions => "ALL",
    database    => "*",
    tables      => "*",
    user        => "admin",
    host        => "localhost",
    password    => "password",
    require     => Mysqld::User["admin-localhost"]
}
mysqld::user { 'admin-%':
    username => "admin",
    host     => "%",
    password => "password",
    require  => Exec['set root password'],
}
mysqld::grant { "admin-%":
    permissions => "ALL",
    database    => "*",
    tables      => "*",
    user        => "admin",
    host        => "%",
    password    => "password",
    require     => Mysqld::User["admin-%"]
}

# mysqld::db { 'hub': password => 'password' }
mysqld::db { 'hubmanager': password => 'password' }