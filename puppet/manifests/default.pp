# This file includes various modules that can be
# installed for various situations described below.

Exec { path => '/usr/local/bin:/bin:/usr/bin:/usr/local/sbin:/usr/sbin:/sbin' }

$mysql_root_password = "pwd333"
$userpwd = "password"
$databases = ["hubmanager:password"]

file { "/etc/httpd/vhosts.d/25-local.hub.jhu.edu.conf":
    source => "puppet:///modules/httpd/etc/httpd/vhosts.d/25-local.hub.jhu.edu.conf",
    owner  => "root",
    group  => "root",
    mode   => 755,
    notify => Service["httpd"],
}

file { "/var/log/httpd/local.hub.jhu.edu_error.log":
    require => File['/etc/httpd/vhosts.d/25-local.hub.jhu.edu.conf'],
    ensure => "file",
    owner => "root",
    group => "loggers",
    mode => 664,
}

include common
include git
include gems
include yum
include centos
include httpd
include php
include mysqld
include nodejs
