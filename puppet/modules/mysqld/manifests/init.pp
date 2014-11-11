class mysqld {

    package { [
            "mysql",
            "mysql-server",
            "perl-DBD-MySQL",
        ]:
        ensure  => installed,
        require => Package['php54'],
    }

    service { "mysqld":
        enable     => true,
        ensure     => running,
        subscribe  => Package["mysql"],
    }

    exec { 'set root password':
        unless  => "mysql -u root --password='${mysql_root_password}'",
        command => "mysqladmin -u root password '${mysql_root_password}'",
        require => Service['mysqld'],
    }

    file { "/etc/my.cnf":
        source => "puppet:///modules/mysqld/etc/my.cnf",
        owner  => "root",
        group  => "root",
        mode   => 644,
    }

    # create users
    mysqld::user { 'admin-localhost':
        username => "admin",
        host     => "localhost",
        password => $userpwd,
        require  => Exec['set root password'],
    }
    mysqld::grant { "admin-localhost":
        permissions => "ALL",
        database    => "*",
        tables      => "*",
        user        => "admin",
        host        => "localhost",
        password    => $userpwd,
        require     => Mysqld::User["admin-localhost"]
    }
    mysqld::user { 'admin-%':
        username => "admin",
        host     => "%",
        password => $userpwd,
        require  => Exec['set root password'],
    }
    mysqld::grant { "admin-%":
        permissions => "ALL",
        database    => "*",
        tables      => "*",
        user        => "admin",
        host        => "%",
        password    => $userpwd,
        require     => Mysqld::User["admin-%"]
    }

    # create databases
    mysqld::dbparse { $databases: }

}
