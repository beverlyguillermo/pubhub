class httpd {

    package { ["httpd", "mod_ssl", "postfix"]:
        ensure => present,
    }

    service { "httpd":
        enable     => true,
        ensure     => running,
        hasrestart => true,
        subscribe  => [Package["httpd", "mod_ssl"], File["/etc/httpd/conf/httpd.conf"]],
    }

    service { "postfix":
        enable      => true,
        ensure     => running,
        hasrestart => true,
        subscribe  => [
            Package["httpd"],
            File["/etc/postfix/main.cf"]
        ],
    }

    exec { "/etc/init.d/httpd reload": 
        command      => "/etc/init.d/httpd reload", 
        refreshonly  => true, 
    }

    file { "/var/www/sites":
        ensure => directory,
    }

    file { ["/usr/local/apache", "/usr/local/apache/htdocs"]:
        ensure => "directory",
    }

    file { [ "/var/cache/apache", "/var/cache/apache/wsdl_cache", "/var/tmp/php" ]:
        ensure  => "directory",
        owner   => "apache",
        group   => "apache",
        require => Package["httpd"], # httpd package creates apache user
    }

    file { "/var/log/httpd":
        ensure => "directory",
    }

    file { "/etc/httpd":
        ensure => "directory",
    }

    file { [ "/etc/httpd/conf", "/etc/httpd/conf.d", "/etc/httpd/vhosts"]:
        ensure => "directory",
        recurse => true,
    }

    file { "/etc/httpd/vhosts.d" :
        ensure => "directory",
        recurse => true,
        notify => Service["httpd"],
    }

    file { "/etc/httpd/conf/httpd.conf":
        source => "puppet:///modules/httpd/etc/httpd/conf/httpd.conf",
        owner  => "root",
        group  => "root",
        mode   => 644,
    }

    file { "/etc/postfix/main.cf":
        source => "puppet:///modules/httpd/etc/postfix/main.cf",
        owner  => "root",
        group  => "root",
        mode   => 644,
    }

    file { "/etc/httpd/conf.d/ssl.conf":
        source => "puppet:///modules/httpd/etc/httpd/conf.d/ssl.conf",
        owner  => "root",
        group  => "root",
        mode   => 644,
    }

    file { "/etc/hosts":
        source => "puppet:///modules/httpd/etc/hosts",
        owner  => "root",
        group  => "root",
        mode   => 644,
    }

}
