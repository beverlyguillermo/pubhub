define mysqld::grant($permissions, $database, $tables, $user, $host, $password) {

    exec { "grant-${user}-${host}-perms":
        command => "mysql -u root --password='${mysql_root_password}' -e \"GRANT ${permissions} ON ${database}.${tables} to '${user}'@'${host}' IDENTIFIED BY '${password}';\"",
        require => Exec['set root password'],
    }

}