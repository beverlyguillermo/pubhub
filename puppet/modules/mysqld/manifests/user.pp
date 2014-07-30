define mysqld::user($username, $host, $password) {

    exec { "create-${username}-${host}-user":
        unless  => "mysql -u ${username} --password='${password}'",
        command => "mysql -u root --password='${mysql_root_password}' -e \"CREATE USER '${username}'@'${host}' IDENTIFIED BY '${password}'\"",
        require => Exec['set root password'],
    }

}

