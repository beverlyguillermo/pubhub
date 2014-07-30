define mysqld::db($dbname = '', $username = '', $password = '', $host = '') {

    # Leave out $name and $username
    # to default both to the title
    if $dbname == '' {
        $databasename = $title
    } else {
        $databasename = $dbname
    }
    if $username == '' {
        $user = $title
    } else {
        $user = $username
    }
    if $host == '' {
        $hostname = 'localhost'
    } else {
        $hostname = $host
    }

    mysqld::user { "create-${user}":
        username => $user,
        host     => $hostname,
        password => $password,
    }

    exec { "create-${databasename}-db":
        unless  => "mysql -u root --password='${mysql_root_password}' ${databasename}",
        command => "mysql -u root --password='${mysql_root_password}' -e \"CREATE DATABASE ${databasename};\"",
        require => Exec['set root password'],
    }

    mysqld::grant { "grant-permissions-{$user}-on-${databasename}":
        permissions => "ALL",
        database    => $databasename,
        tables      => "*",
        user        => $user,
        host        => $hostname,
        password    => $password,
        require     => Exec["create-${databasename}-db"]
    }
}